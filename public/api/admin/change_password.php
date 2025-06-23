<?php
/**
 * VARUNA System - API to Change a User's Password
 */
require_once __DIR__ . '/../../../src/init.php';
header('Content-Type: application/json');

$response = ['success' => false, 'message' => 'An unknown error occurred.'];

try {
    // Security: Ensure user is ADMIN from IT CELL
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['user_id']) || $_SESSION['section'] !== 'IT CELL') {
        throw new Exception('Access Denied.', 403);
    }

    validate_csrf_token($_POST['csrf_token'] ?? '');

    if (empty($_POST['user_id']) || empty($_POST['new_password'])) {
        throw new Exception('User and a new password are required.');
    }

    $new_password_hash = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("UPDATE varuna_users SET password = ? WHERE id = ?");
    $stmt->execute([$new_password_hash, $_POST['user_id']]);
    
    $user_stmt = $pdo->prepare("SELECT username FROM varuna_users WHERE id = ?");
    $user_stmt->execute([$_POST['user_id']]);
    $target_username = $user_stmt->fetchColumn();

    log_activity($pdo, 'ADMIN_PASS_CHANGE', ['details' => "Changed password for user: $target_username"]);
    
    $response = [
        'success' => true, 
        'message' => "Password for $target_username updated successfully!", 
        'new_csrf_token' => generate_csrf_token()
    ];

} catch (PDOException $e) {
    $response['message'] = 'Database error occurred.';
    $response['new_csrf_token'] = generate_csrf_token();
    log_activity($pdo, 'ADMIN_PASS_CHANGE_FAIL', ['details' => $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(400);
    $response['message'] = $e->getMessage();
    $response['new_csrf_token'] = generate_csrf_token();
}

echo json_encode($response);