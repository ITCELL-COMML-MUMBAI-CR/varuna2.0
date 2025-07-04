<?php
require_once __DIR__ . '/../../../src/init.php';

// Only admins can perform this action
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'ADMIN') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Forbidden: You do not have permission to perform this action.']);
    exit();
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit();
}

validate_csrf_token($_POST['csrf_token'] ?? '');

$userId = $_POST['user_id'] ?? null;
$newPassword = $_POST['new_password'] ?? null;

if (empty($userId) || empty($newPassword)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'User ID and new password are required.']);
    exit();
}

try {
    $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("UPDATE varuna_users SET password = ? WHERE id = ?");
    
    if ($stmt->execute([$newPasswordHash, $userId])) {
        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => true, 'message' => 'Password updated successfully.']);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'User not found or password could not be updated.']);
        }
    } else {
        throw new Exception("Database query failed.");
    }
} catch (Exception $e) {
    error_log("Admin password change error for user {$userId}: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'An internal server error occurred. Please try again.']);
} 