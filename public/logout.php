<?php
require_once __DIR__ . '/../config.php';

// $pdo is available from config.php
if (isset($_SESSION['user_id'])) {
    $logData = ['user_id' => $_SESSION['user_id'], 'username' => $_SESSION['username']];
    log_activity($pdo, 'LOGOUT_SUCCESS', $logData);
}

// Unset all of the session variables
$_SESSION = array();

// Destroy the session
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
session_destroy();
header("location: " . BASE_URL . "login");
exit;