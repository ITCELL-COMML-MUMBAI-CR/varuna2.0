<?php
/**
 * API to permanently delete a staff member and their associated files.
 */
require_once __DIR__ . '/../../src/init.php';
header('Content-Type: application/json');

try {
    // Security: Only logged-in Admins or SCIs can delete staff.
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['ADMIN', 'SCI'])) {
        throw new Exception('Access Denied.', 403);
    }
    
    validate_csrf_token($_POST['csrf_token'] ?? '');

    $staff_id = $_POST['staff_id'] ?? '';
    if (empty($staff_id)) {
        throw new Exception('Staff ID is missing.', 400);
    }

    $pdo->beginTransaction();

    // 1. Fetch the staff record to get filenames for deletion.
    $stmt = $pdo->prepare("SELECT profile_image, signature_image, police_image, medical_image, ta_image, ppo_image, adhar_card_image FROM varuna_staff WHERE id = ?");
    $stmt->execute([$staff_id]);
    $files_to_delete = $stmt->fetch();

    if ($files_to_delete) {
        $upload_dir = __DIR__ . '/../../public/uploads/staff/';
        // 2. Delete each associated file from the server.
        foreach ($files_to_delete as $filename) {
            if (!empty($filename) && file_exists($upload_dir . $filename)) {
                unlink($upload_dir . $filename);
            }
        }
    }

    // 3. Delete the staff record from the database.
    $delete_stmt = $pdo->prepare("DELETE FROM varuna_staff WHERE id = ?");
    $delete_stmt->execute([$staff_id]);

    $pdo->commit();

    log_activity($pdo, 'STAFF_DELETE_SUCCESS', ['details' => "Permanently deleted staff ID: $staff_id"]);
    echo json_encode(['success' => true, 'message' => "Staff member $staff_id has been deleted successfully."]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}