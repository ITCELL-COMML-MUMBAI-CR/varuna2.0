<?php
/**
 * API to permanently delete a contract and all associated staff (CASCADE DELETE)
 * This is a PERMANENT operation and cannot be undone.
 */
require_once __DIR__ . '/../../../src/init.php';
header('Content-Type: application/json');

try {
    // Security: Only Admins can perform this action
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !in_array($_SESSION['role'],['ADMIN','SCI'])) {
        throw new Exception('Access Denied. Only Admins and SCI can perform cascade deletion.', 403);
    }
    
    validate_csrf_token($_POST['csrf_token'] ?? '');
    
    $contract_id = $_POST['contract_id'] ?? '';
    $confirmation_phrase = $_POST['confirmation_phrase'] ?? '';
    $expected_phrase = $_POST['expected_phrase'] ?? '';
    
    if (empty($contract_id)) {
        throw new Exception('Contract ID is required.', 400);
    }
    
    // Verify confirmation phrase
    if (empty($confirmation_phrase) || empty($expected_phrase)) {
        throw new Exception('Confirmation phrase is required.', 400);
    }
    
    if ($confirmation_phrase !== $expected_phrase) {
        throw new Exception('Confirmation phrase does not match. Deletion cancelled.', 400);
    }
    
    // Check if contract exists and get details, including document filenames
    $contract_check = $pdo->prepare("
        SELECT c.contract_name, c.licensee_id, l.name as licensee_name, 
               c.fssai_image, c.fire_safety_image, c.pest_control_image, c.water_safety_image
        FROM contracts c 
        LEFT JOIN varuna_licensee l ON c.licensee_id = l.id 
        WHERE c.id = ?
    ");
    $contract_check->execute([$contract_id]);
    $contract = $contract_check->fetch(PDO::FETCH_ASSOC);
    
    if (!$contract) {
        throw new Exception('Contract not found.', 404);
    }
    
    $pdo->beginTransaction();
    
    // Get staff count before deletion for logging
    $staff_count_stmt = $pdo->prepare("SELECT COUNT(*) FROM varuna_staff WHERE contract_id = ?");
    $staff_count_stmt->execute([$contract_id]);
    $staff_count = $staff_count_stmt->fetchColumn();
    
    // Step 1: Delete all staff under this contract
    if ($staff_count > 0) {
        $delete_staff_stmt = $pdo->prepare("DELETE FROM varuna_staff WHERE contract_id = ?");
        $delete_staff_stmt->execute([$contract_id]);
    }
    
    // Step 2: Delete associated contract documents from the filesystem
    $docs_to_delete = array_filter([
        $contract['fssai_image'],
        $contract['fire_safety_image'],
        $contract['pest_control_image'],
        $contract['water_safety_image']
    ]);
    
    $deleted_docs_count = 0;
    $upload_dir = __DIR__ . '/../../../public/uploads/contracts/';

    foreach ($docs_to_delete as $doc_filename) {
        $file_path = $upload_dir . $doc_filename;
        if (!empty($doc_filename) && file_exists($file_path)) {
            if (unlink($file_path)) {
                $deleted_docs_count++;
            }
        }
    }
    
    // Step 3: Delete the contract itself
    $delete_contract_stmt = $pdo->prepare("DELETE FROM contracts WHERE id = ?");
    $delete_contract_stmt->execute([$contract_id]);
    
    $pdo->commit();
    
    // Log the cascade deletion
    log_activity($pdo, 'CONTRACT_CASCADE_DELETE', [
        'details' => "PERMANENT CASCADE DELETE: Contract '{$contract['contract_name']}' (ID: $contract_id) " .
                    "under licensee '{$contract['licensee_name']}' and ALL associated data. " .
                    "Deleted: $staff_count staff members and $deleted_docs_count document(s)."
    ]);
    
    echo json_encode([
        'success' => true, 
        'message' => "Contract '{$contract['contract_name']}' and all associated staff has been permanently deleted. " .
                    "($staff_count staff members, $deleted_docs_count documents)",
        'new_csrf_token' => generate_csrf_token(),
        'deleted_counts' => [
            'staff' => $staff_count,
            'documents' => $deleted_docs_count
        ]
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    http_response_code($e->getCode() > 0 ? $e->getCode() : 500);
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage(), 
        'new_csrf_token' => generate_csrf_token()
    ]);
} 