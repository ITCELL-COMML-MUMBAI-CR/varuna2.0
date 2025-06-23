<?php
/**
 * VARUNA System - API to Bulk Reject Staff
 * Handles rejecting multiple staff members with a single remark.
 */
require_once __DIR__ . '/../../src/init.php';
header('Content-Type: application/json');

$response = ['success' => false, 'message' => 'An unknown error occurred.'];

try {
    // 1. Security Checks
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['user_id']) || $_SESSION['role'] !== 'SCI') {
        throw new Exception('Access Denied.', 403);
    }

    // 2. CSRF Token Validation
    validate_csrf_token($_POST['csrf_token'] ?? '');

    // 3. Input Validation
    $staff_ids = json_decode($_POST['staff_ids'] ?? '[]');
    $remark = trim($_POST['remark'] ?? '');

    if (empty($staff_ids) || !is_array($staff_ids)) {
        throw new Exception('No staff IDs provided or invalid format.', 400);
    }
    if (empty($remark)) {
        throw new Exception('A remark is required for rejection.', 400);
    }
    
    $sanitized_ids = array_filter($staff_ids, 'is_string');
    if (empty($sanitized_ids)) {
        throw new Exception('No valid staff IDs to process.', 400);
    }
    
    // 4. Database Operation within a Transaction
    $pdo->beginTransaction();

    $placeholders = implode(',', array_fill(0, count($sanitized_ids), '?'));
    
    // Update staff status to 'rejected'
    $update_stmt = $pdo->prepare("UPDATE varuna_staff SET status = 'rejected' WHERE id IN ($placeholders)");
    $update_stmt->execute($sanitized_ids);
    
    $affected_rows = $update_stmt->rowCount();

    // Insert a remark for EACH staff member who was rejected
    $remark_stmt = $pdo->prepare("INSERT INTO varuna_remarks (staff_id, remark_by_user_id, remark) VALUES (?, ?, ?)");
    foreach ($sanitized_ids as $staff_id) {
        $remark_stmt->execute([$staff_id, $_SESSION['user_id'], $remark]);
    }

    $pdo->commit();
    
    // 5. Logging and Success Response
    log_activity($pdo, 'STAFF_BULK_REJECT', [
        'details' => "Bulk rejected $affected_rows staff members with remark: '$remark'. IDs: " . implode(', ', $sanitized_ids)
    ]);
    
    $response = [
        'success' => true,
        'message' => "$affected_rows staff member(s) have been rejected successfully.",
        'new_csrf_token' => generate_csrf_token()
    ];

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code($e->getCode() > 0 ? $e->getCode() : 500);
    $response['message'] = $e->getMessage();
    $response['new_csrf_token'] = generate_csrf_token();
}

echo json_encode($response);