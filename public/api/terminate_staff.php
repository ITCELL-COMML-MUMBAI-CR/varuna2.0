<?php
/**
 * API to manually terminate a single staff member.
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

    // Update the staff member's status to 'Terminated'.
    $stmt = $pdo->prepare("UPDATE varuna_staff SET status = 'terminated' WHERE id = ?");
    $stmt->execute([$staff_id]);

    log_activity($pdo, 'STAFF_TERMINATE', ['details' => "Manually terminated staff ID: $staff_id"]);
    echo json_encode(['success' => true, 'message' => "Staff member $staff_id has been terminated.", 'new_csrf_token' => generate_csrf_token()]);

} catch (Exception $e) {
    http_response_code($e->getCode() > 0 ? $e->getCode() : 500);
    echo json_encode(['success' => false, 'message' => $e->getMessage(), 'new_csrf_token' => generate_csrf_token()]);
}