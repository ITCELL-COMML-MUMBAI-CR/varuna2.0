<?php
/**
 * VARUNA System - API to Update an Existing Contract (including document replacement)
 * Allows updating text fields as well as replacing or uploading new contract-level documents.
 */
require_once __DIR__ . '/../../../src/init.php';
header('Content-Type: application/json');

try {
    // --- 1. SECURITY & VALIDATION ---
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['user_id']) || $_SESSION['role'] !== 'ADMIN') {
        throw new Exception('Access Denied', 403);
    }
    validate_csrf_token($_POST['csrf_token'] ?? '');

    $contract_id = $_POST['id_value'] ?? '';
    if (empty($contract_id)) {
        throw new Exception('Contract ID missing.', 400);
    }

    // --- 2. BEGIN TRANSACTION ---
    $pdo->beginTransaction();

    // --- 3. HANDLE FILE UPLOADS ---
    $upload_dir_rel = 'uploads/contracts/';
    $upload_dir_abs = __DIR__ . '/../../../public/' . $upload_dir_rel;
    if (!is_dir($upload_dir_abs)) {
        mkdir($upload_dir_abs, 0755, true);
    }

    $uploaded_files = [];
    // Mapping of field name => doc suffix used in filename
    $doc_fields = [
        'fssai_image'        => 'fssai',
        'fire_safety_image'  => 'firesafety',
        'pest_control_image' => 'pestcontrol',
        'water_safety_image' => 'watersafety'
    ];

    foreach ($doc_fields as $form_field => $doc_suffix) {
        if (isset($_FILES[$form_field]) && $_FILES[$form_field]['error'] === UPLOAD_ERR_OK) {
            $newFileName = $contract_id . '_' . $doc_suffix . '.' . pathinfo($_FILES[$form_field]['name'], PATHINFO_EXTENSION);
            $result = process_image_upload($_FILES[$form_field], $upload_dir_abs, $newFileName);
            if (is_array($result)) {
                throw new Exception('File Upload Error: ' . implode(', ', $result), 400);
            }
            // Store relative path for DB
            $uploaded_files[$form_field] = $result;
        }
    }

    // --- 4. BUILD DYNAMIC UPDATE QUERY ---
    $allowed_text_fields = [
        'contract_name',
        'location',
        'stalls',
        'license_fee',
        'period',
        'status',
        'rail_neer_stock'
    ];

    $sql_parts = [];
    $params = [];

    // Text / numeric fields
    foreach ($allowed_text_fields as $field) {
        if (isset($_POST[$field])) {
            $sql_parts[] = "`$field` = ?";
            $params[] = $_POST[$field] !== '' ? $_POST[$field] : null;
        }
    }

    // File fields
    foreach ($uploaded_files as $db_column => $filename) {
        $sql_parts[] = "`$db_column` = ?";
        $params[] = $filename;
    }

    if (empty($sql_parts)) {
        throw new Exception('No valid data supplied for update.', 400);
    }

    $params[] = $contract_id; // WHERE clause value

    $sql = "UPDATE contracts SET " . implode(', ', $sql_parts) . " WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    // --- 5. COMMIT & RESPOND ---
    $pdo->commit();
    log_activity($pdo, 'CONTRACT_UPDATE', ['details' => "Updated contract ID $contract_id"]);

    echo json_encode([
        'success'        => true,
        'message'        => 'Contract updated successfully!',
        'new_csrf_token' => generate_csrf_token()
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code($e->getCode() ?: 500);
    echo json_encode([
        'success'        => false,
        'message'        => $e->getMessage(),
        'new_csrf_token' => generate_csrf_token()
    ]);
} 