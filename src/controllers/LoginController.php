<?php
/**
 * Controller for User Login
 * Current Time: Monday, June 16, 2025 at 1:15 PM IST
 * Location: Kalyan, Maharashtra, India
 */

if (!defined('VARUNA_ENTRY_POINT')) { die('Direct access not allowed.'); }

global $pdo;

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    validate_csrf_token($_POST['csrf_token'] ?? '');

    $username = $_POST['username'];
    $password = $_POST['password'];

    // 1. UPDATED QUERY: Add 'section' to the columns being selected
    $stmt = $pdo->prepare("SELECT id, username, password, role, section, designation FROM varuna_users WHERE username = :username AND status = 'active'");
    $stmt->bindParam(':username', $username);
    $stmt->execute();

    if ($user = $stmt->fetch()) {
        if (password_verify($password, $user['password'])) {
            // SUCCESS
            regenerate_session();
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['designation'] = $user['designation'];
            
            // 2. ADD THIS LINE: Store the user's section in the session
            $_SESSION['section'] = $user['section'];

            $logData = ['user_id' => $user['id'], 'username' => $user['username']];
            log_activity($pdo, 'LOGIN_SUCCESS', $logData);

            header("Location: " . BASE_URL . "dashboard");
            exit();
        }
    }
    
    // FAIL
    $logData = ['username' => $username, 'details' => 'Invalid credentials or inactive user.'];
    log_activity($pdo, 'LOGIN_FAIL', $logData);
    $login_error = "Invalid username or password.";
}