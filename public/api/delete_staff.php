<?php
/**
 * API to delete a staff member permanently.
 */
require_once __DIR__ . '/../../src/init.php';
header('Content-Type: application/json');

try {
    // Security: Only Admins or SCIs can perform this action.
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !in_array($_SESSION['role'], ['ADMIN', 'SCI'])) {
        throw new Exception('Access Denied', 403);
    }
    validate_csrf_token($_POST['csrf_token'] ?? '');
    
    $staff_id = $_POST['staff_id'] ?? '';
    if (empty($staff_id)) {
        throw new Exception('Staff ID is required.', 400);
    }

    // Check if the staff exists and get their status
    $check_stmt = $pdo->prepare("SELECT status FROM varuna_staff WHERE id = ?");
    $check_stmt->execute([$staff_id]);
    $staff = $check_stmt->fetch();

    if (!$staff) {
        throw new Exception('Staff not found.', 404);
    }

    // Allow deletion of both pending and terminated staff
    if (!in_array($staff['status'], ['pending', 'terminated'])) {
        throw new Exception('Only pending or terminated staff can be deleted.', 400);
    }

    // Delete the staff member
    $stmt = $pdo->prepare("DELETE FROM varuna_staff WHERE id = ?");
    $stmt->execute([$staff_id]);

    log_activity($pdo, 'STAFF_DELETE', ['details' => "Deleted staff ID: $staff_id"]);
    echo json_encode(['success' => true, 'message' => "Staff member $staff_id has been deleted.", 'new_csrf_token' => generate_csrf_token()]);

} catch (Exception $e) {
    http_response_code($e->getCode() > 0 ? $e->getCode() : 500);
    echo json_encode(['success' => false, 'message' => $e->getMessage(), 'new_csrf_token' => generate_csrf_token()]);
}