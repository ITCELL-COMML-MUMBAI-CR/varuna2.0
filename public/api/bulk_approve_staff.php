<?php
/**
 * VARUNA System - API to Bulk Approve Staff
 * Handles approving multiple staff members in a single request.
 */
require_once __DIR__ . '/../../src/init.php';
header('Content-Type: application/json');

$response = ['success' => false, 'message' => 'An unknown error occurred.'];

try {
    // 1. Security Checks: Must be a POST request from a logged-in SCI
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['user_id']) || $_SESSION['role'] !== 'SCI') {
        throw new Exception('Access Denied.', 403);
    }

    // 2. CSRF Token Validation
    validate_csrf_token($_POST['csrf_token'] ?? '');

    // 3. Input Validation
    $staff_ids = json_decode($_POST['staff_ids'] ?? '[]');
    if (empty($staff_ids) || !is_array($staff_ids)) {
        throw new Exception('No staff IDs provided or invalid format.', 400);
    }
    
    // Sanitize all IDs to ensure they are valid
    $sanitized_ids = array_filter($staff_ids, function($id) {
        return !empty($id) && is_string($id);
    });
    
    if (empty($sanitized_ids)) {
        throw new Exception('No valid staff IDs to process.', 400);
    }
    
    // 4. Database Operation within a Transaction
    // --- BUSINESS RULE GUARD ---
    $placeholders_guard = implode(',', array_fill(0, count($sanitized_ids), '?'));
    $guard_stmt = $pdo->prepare("SELECT s.id FROM varuna_staff s JOIN contracts c ON s.contract_id = c.id JOIN varuna_licensee l ON c.licensee_id = l.id WHERE s.id IN ($placeholders_guard) AND (LOWER(c.status) = 'terminated' OR LOWER(l.status) = 'terminated' OR LOWER(s.status) = 'terminated') LIMIT 1");
    $guard_stmt->execute($sanitized_ids);
    if ($guard_stmt->fetch()) {
        throw new Exception('Cannot approve staff while their contract or licensee is terminated.', 400);
    }

    $pdo->beginTransaction();

    // Create the correct number of placeholders for the IN clause
    $placeholders = implode(',', array_fill(0, count($sanitized_ids), '?'));
    
    // Update staff status to 'approved'
    $update_stmt = $pdo->prepare("UPDATE varuna_staff SET status = 'approved' WHERE id IN ($placeholders)");
    $update_stmt->execute($sanitized_ids);
    
    $affected_rows = $update_stmt->rowCount();

    // Also, delete any previous rejection remarks for these staff members
    $remark_stmt = $pdo->prepare("DELETE FROM varuna_remarks WHERE staff_id IN ($placeholders)");
    $remark_stmt->execute($sanitized_ids);

    $pdo->commit();
    
    // 5. Logging and Success Response
    foreach ($sanitized_ids as $staff_id) {
        log_activity($pdo, 'STAFF_STATUS_UPDATE', [
            'details' => "Staff ID $staff_id status updated to approved via bulk operation."
        ]);
    }
    
    $response = [
        'success' => true,
        'message' => "$affected_rows staff member(s) have been approved successfully.",
        'new_csrf_token' => generate_csrf_token()
    ];

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code($e->getCode() > 0 ? $e->getCode() : 500);
    $response['message'] = $e->getMessage();
    $response['new_csrf_token'] = generate_csrf_token(); // Also send a new token on failure
}

echo json_encode($response);