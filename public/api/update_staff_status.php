<?php
require_once __DIR__ . '/../../src/init.php';
require_once __DIR__ . '/../../config.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['user_id']) || $_SESSION['role'] !== 'SCI') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit();
}
// NOW, VALIDATE THE CSRF TOKEN
validate_csrf_token($_POST['csrf_token'] ?? '');

$staff_id = $_POST['staff_id'] ?? '';
$new_status = $_POST['status'] ?? ''; // 'approved' or 'rejected'
$remark = $_POST['remark'] ?? '';
$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

if (empty($staff_id) || !in_array($new_status, ['approved', 'rejected'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid data provided.']);
    exit();
}
if ($new_status === 'rejected' && empty($remark)) {
    echo json_encode(['success' => false, 'message' => 'Remark is required for rejection.']);
    exit();
}

try {
    $pdo->beginTransaction();

    // Update staff status
    $stmt = $pdo->prepare("UPDATE varuna_staff SET status = ? WHERE id = ?");
    $stmt->execute([$new_status, $staff_id]);

    $log_details = "Staff ID $staff_id status updated to $new_status by $username.";

    // If rejected, insert a remark
    if ($new_status === 'rejected') {
        $remark_stmt = $pdo->prepare("INSERT INTO varuna_remarks (staff_id, remark_by_user_id, remark) VALUES (?, ?, ?)");
        $remark_stmt->execute([$staff_id, $user_id, $remark]);
        $log_details .= " Remark: $remark";
    }
    
    // If approved, delete any previous rejection remarks
    if ($new_status === 'approved') {
        $remark_stmt = $pdo->prepare("DELETE FROM varuna_remarks WHERE staff_id = ?");
        $remark_stmt->execute([$staff_id]);
    }

    $pdo->commit();

    log_activity($pdo, 'STAFF_STATUS_UPDATE', ['details' => $log_details]);

    // --- NEW: Custom success message logic ---
    $successMessage = ($new_status === 'approved') 
        ? "Staff has been approved." 
        : "Staff has been rejected.";

    echo json_encode([
        'success' => true, 
        'message' => $successMessage,
        'new_csrf_token' => generate_csrf_token()
    ]);

} catch (Exception $e) {
    $pdo->rollBack();
    log_activity($pdo, 'STAFF_STATUS_FAIL', ['details' => $e->getMessage()]);
    echo json_encode(['success' => false, 'message' => 'A database error occurred.']);
}