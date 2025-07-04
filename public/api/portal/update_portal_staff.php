<?php
/**
 * Portal API: Handles form submission for editing a staff member.
 */
require_once __DIR__ . '/../../../src/init.php';
header('Content-Type: application/json');

try {
    // 1. Security check for portal session and POST method
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['is_licensee_portal'], $_SESSION['licensee_id'])) {
        throw new Exception('Access Denied.', 403);
    }
    validate_csrf_token($_POST['csrf_token'] ?? '');

    $staff_id = $_POST['staff_id'] ?? '';
    if (empty($staff_id)) {
        throw new Exception('Staff ID is missing.', 400);
    }

    // 2. CRITICAL Security Check: Ensure the staff member being updated belongs to the logged-in licensee.
    $check_stmt = $pdo->prepare("SELECT s.id FROM varuna_staff s JOIN contracts c ON s.contract_id = c.id WHERE s.id = ? AND c.licensee_id = ?");
    $check_stmt->execute([$staff_id, $_SESSION['licensee_id']]);
    if ($check_stmt->fetch() === false) {
        throw new Exception('Permission denied to edit this staff member.', 403);
    }

    $pdo->beginTransaction();

    // 1. File Upload Processing
    $upload_dir = __DIR__ . '/../../../public/uploads/staff/';
    $uploaded_files = [];
    $doc_types = ['police', 'medical', 'ta', 'ppo', 'profile', 'signature', 'adhar_card'];

    foreach ($doc_types as $doc_type) {
        $field_name = $doc_type . '_image';
        if (isset($_FILES[$field_name]) && $_FILES[$field_name]['error'] == UPLOAD_ERR_OK) {
            $newFileName = $staff_id . '_' . $doc_type . '.' . pathinfo($_FILES[$field_name]['name'], PATHINFO_EXTENSION);
            
            $result = process_image_upload($_FILES[$field_name], $upload_dir, $newFileName);
            if (is_array($result)) { throw new Exception('File Upload Error: ' . implode(', ', $result)); }
            $uploaded_files[$field_name] = $result;
        }
    }

    // 2. Dynamic SQL Query Building
    $sql_parts = [];
    $data_to_bind = [];

    // Basic fields
    $sql_parts = ['name = :name', 'designation = :designation', 'contact = :contact', 'adhar_card_number = :adhar_card_number', 'status = \'pending\''];
    $data_to_bind = [
        'name' => $_POST['name'],
        'designation' => $_POST['designation'],
        'contact' => $_POST['contact'],
        'adhar_card_number' => !empty($_POST['adhar_card_number']) ? $_POST['adhar_card_number'] : null
    ];

    // Add file fields to query if they were uploaded
    foreach ($uploaded_files as $key => $filename) {
        $sql_parts[] = "`$key` = :$key";
        $data_to_bind[$key] = $filename;
    }

    // Add date fields
    $date_fields = ['police_issue_date', 'police_expiry_date', 'medical_issue_date', 'medical_expiry_date', 'ta_expiry_date'];
    foreach ($date_fields as $field) {
        if (isset($_POST[$field])) {
            $sql_parts[] = "`$field` = :$field";
            $data_to_bind[$field] = !empty($_POST[$field]) ? $_POST[$field] : null;
        }
    }

    $sql = "UPDATE varuna_staff SET " . implode(', ', $sql_parts) . " WHERE id = :staff_id";
    $data_to_bind['staff_id'] = $staff_id;

    $stmt = $pdo->prepare($sql);
    $stmt->execute($data_to_bind);

    // Delete any old rejection remarks
    $remark_stmt = $pdo->prepare("DELETE FROM varuna_remarks WHERE staff_id = ?");
    $remark_stmt->execute([$staff_id]);

    $pdo->commit();

    log_activity($pdo, 'PORTAL_STAFF_EDIT', ['details' => "Licensee edited staff ID: $staff_id"]);
    echo json_encode(['success' => true, 'message' => 'Staff details updated and sent for approval.', 'new_csrf_token' => generate_csrf_token()]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage(), 'new_csrf_token' => generate_csrf_token()]);
}