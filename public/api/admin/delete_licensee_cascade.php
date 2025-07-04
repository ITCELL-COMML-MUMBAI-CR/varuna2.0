<?php
/**
 * API to permanently delete a licensee and all associated contracts and staff (CASCADE DELETE)
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
    
    $licensee_id = $_POST['licensee_id'] ?? '';
    $confirmation_phrase = $_POST['confirmation_phrase'] ?? '';
    $expected_phrase = $_POST['expected_phrase'] ?? '';
    
    if (empty($licensee_id)) {
        throw new Exception('Licensee ID is required.', 400);
    }
    
    // Verify confirmation phrase
    if (empty($confirmation_phrase) || empty($expected_phrase)) {
        throw new Exception('Confirmation phrase is required.', 400);
    }
    
    if ($confirmation_phrase !== $expected_phrase) {
        throw new Exception('Confirmation phrase does not match. Deletion cancelled.', 400);
    }
    
    // Check if licensee exists
    $licensee_check = $pdo->prepare("SELECT name FROM varuna_licensee WHERE id = ?");
    $licensee_check->execute([$licensee_id]);
    $licensee = $licensee_check->fetch();
    
    if (!$licensee) {
        throw new Exception('Licensee not found.', 404);
    }
    
    $pdo->beginTransaction();
    
    // Get detailed counts before deletion for logging
    $contract_count_stmt = $pdo->prepare("SELECT COUNT(*) FROM contracts WHERE licensee_id = ?");
    $contract_count_stmt->execute([$licensee_id]);
    $contract_count = $contract_count_stmt->fetchColumn();
    
    $staff_count_stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM varuna_staff vs 
        JOIN contracts c ON vs.contract_id = c.id 
        WHERE c.licensee_id = ?
    ");
    $staff_count_stmt->execute([$licensee_id]);
    $staff_count = $staff_count_stmt->fetchColumn();
    
    // Step 1: Delete all staff under contracts of this licensee
    if ($staff_count > 0) {
        $delete_staff_stmt = $pdo->prepare("
            DELETE vs FROM varuna_staff vs 
            JOIN contracts c ON vs.contract_id = c.id 
            WHERE c.licensee_id = ?
        ");
        $delete_staff_stmt->execute([$licensee_id]);
    }
    
    // Step 2: Delete all contracts under this licensee
    if ($contract_count > 0) {
        $delete_contracts_stmt = $pdo->prepare("DELETE FROM contracts WHERE licensee_id = ?");
        $delete_contracts_stmt->execute([$licensee_id]);
    }
    
    // Step 3: Delete any access tokens for this licensee
    $delete_tokens_stmt = $pdo->prepare("DELETE FROM varuna_access_tokens WHERE licensee_id = ?");
    $delete_tokens_stmt->execute([$licensee_id]);
    
    // Step 4: Delete the licensee itself
    $delete_licensee_stmt = $pdo->prepare("DELETE FROM varuna_licensee WHERE id = ?");
    $delete_licensee_stmt->execute([$licensee_id]);
    
    $pdo->commit();
    
    // Log the cascade deletion
    log_activity($pdo, 'LICENSEE_CASCADE_DELETE', [
        'details' => "PERMANENT CASCADE DELETE: Licensee '{$licensee['name']}' (ID: $licensee_id) and ALL associated data. " .
                    "Deleted: $contract_count contracts, $staff_count staff members."
    ]);
    
    echo json_encode([
        'success' => true, 
        'message' => "Licensee '{$licensee['name']}' and all associated data has been permanently deleted. " .
                    "($contract_count contracts, $staff_count staff)",
        'new_csrf_token' => generate_csrf_token(),
        'deleted_counts' => [
            'contracts' => $contract_count,
            'staff' => $staff_count
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