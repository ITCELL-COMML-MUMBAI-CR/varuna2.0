<?php
require_once __DIR__ . '/../../src/init.php';

// Set content type to JSON
header('Content-Type: application/json');

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit();
}

// Basic CSRF protection
try {
    if (!isset($_POST['csrf_token'])) {
        throw new Exception('CSRF token missing.');
    }
    validate_csrf_token($_POST['csrf_token']);
} catch (Exception $e) {
    http_response_code(403); // Forbidden
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token.', 'new_csrf_token' => generate_csrf_token()]);
    exit();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401); // Unauthorized
    echo json_encode(['success' => false, 'message' => 'You must be logged in to change your password.']);
    exit();
}

// Get POST data
$currentPassword = $_POST['current_password'] ?? '';
$newPassword = $_POST['new_password'] ?? '';
$userId = $_SESSION['user_id'];

// Basic validation
if (empty($currentPassword) || empty($newPassword)) {
    http_response_code(400); // Bad Request
    echo json_encode(['success' => false, 'message' => 'Current and new passwords are required.']);
    exit();
}

// Password change logic
try {
    // Get user from database
    $stmt = $pdo->prepare("SELECT password FROM varuna_users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    if (!$user) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'User not found.', 'new_csrf_token' => generate_csrf_token()]);
        exit();
    }

    // Verify current password
    if (!password_verify($currentPassword, $user['password'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Incorrect current password.', 'new_csrf_token' => generate_csrf_token()]);
        exit();
    }

    // Hash new password
    $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);

    // Update password in the database
    $stmt = $pdo->prepare("UPDATE varuna_users SET password = ? WHERE id = ?");
    if ($stmt->execute([$newPasswordHash, $userId])) {
        // Log out the user after successful password change
        session_destroy();
        echo json_encode([
            'success' => true, 
            'message' => 'Password updated successfully. Please login again.',
            'logout' => true,
            'redirect_url' => BASE_URL . 'login'
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to update password.', 'new_csrf_token' => generate_csrf_token()]);
    }
} catch (Exception $e) {
    error_log("Password change error for user {$userId}: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'An error occurred. Please try again.', 'new_csrf_token' => generate_csrf_token()]);
} 