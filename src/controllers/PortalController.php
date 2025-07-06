<?php
/**
 * Controller for the Licensee Portal.
 * Validates the access token and sets up a limited session.
 */

if (!defined('VARUNA_ENTRY_POINT')) { die('Direct access not allowed.'); }

global $pdo;

try {
    // Log access attempt with token and request details
    error_log("Portal access attempt - Token: " . ($_GET['token'] ?? 'NOT_PROVIDED') . 
              " | IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN') . 
              " | User Agent: " . ($_SERVER['HTTP_USER_AGENT'] ?? 'UNKNOWN'));

    // Clear any existing session data to prevent unauthorized access
    session_unset();
    
    $token = $_GET['token'] ?? '';

    // Validate token format (should be 64 characters hexadecimal)
    if (empty($token) || !preg_match('/^[a-f0-9]{64}$/', $token)) {
        error_log("Portal access denied - Invalid token format: " . $token);
        require_once __DIR__ . '/../views/errors/invalid_link_view.php';
        exit();
    }

    // Log database query attempt
    error_log("Portal access - Attempting database validation for token: " . $token);

    // Prepare to validate the token against the database.
    $stmt = $pdo->prepare(
        "SELECT l.id as licensee_id, l.name as licensee_name, l.status as licensee_status, t.expires_at, t.is_active 
         FROM varuna_access_tokens t
         LEFT JOIN varuna_licensee l ON t.licensee_id = l.id
         WHERE t.token = ? LIMIT 1"
    );
    $stmt->execute([$token]);
    $token_data = $stmt->fetch(PDO::FETCH_ASSOC);

    // Check if token exists and is valid
    if (!$token_data) {
        error_log("Portal access denied - Token not found in database: " . $token);
        require_once __DIR__ . '/../views/errors/invalid_link_view.php';
        exit();
    }

    // Log token data for debugging
    error_log("Portal access - Token data found: " . json_encode([
        'licensee_id' => $token_data['licensee_id'] ?? 'NULL',
        'licensee_status' => $token_data['licensee_status'] ?? 'NULL',
        'is_active' => $token_data['is_active'] ?? 'NULL',
        'expires_at' => $token_data['expires_at'] ?? 'NULL'
    ]));

    // Check if token is active
    if (!$token_data['is_active']) {
        error_log("Portal access denied - Token is inactive for licensee ID: " . ($token_data['licensee_id'] ?? 'UNKNOWN'));
        require_once __DIR__ . '/../views/errors/invalid_link_view.php';
        exit();
    }

    // Check if licensee exists and is not terminated
    if (!$token_data['licensee_id'] || !$token_data['licensee_name'] || strtolower($token_data['licensee_status']) === 'terminated') {
        error_log("Portal access denied - Invalid licensee data: " . json_encode([
            'licensee_id' => $token_data['licensee_id'] ?? 'NULL',
            'licensee_name' => $token_data['licensee_name'] ?? 'NULL',
            'licensee_status' => $token_data['licensee_status'] ?? 'NULL'
        ]));
        require_once __DIR__ . '/../views/errors/invalid_link_view.php';
        exit();
    }

    // Check if token has expired
    $current_date = new DateTime();
    $expiry_date = new DateTime($token_data['expires_at']);
    
    if ($current_date > $expiry_date) {
        error_log("Portal access denied - Token expired for licensee ID: " . $token_data['licensee_id'] . 
                  " | Expired at: " . $token_data['expires_at']);
        require_once __DIR__ . '/../views/errors/expired_portal_link.php';
        exit();
    }

    // --- Success! The token is valid. ---
    error_log("Portal access granted - Licensee ID: " . $token_data['licensee_id'] . 
              " | Name: " . $token_data['licensee_name']);

    // 1. Create a clean, secure session for the licensee.
    regenerate_session();

    // 2. Set specific session variables to identify this as a portal session.
    $_SESSION['is_licensee_portal'] = true;
    $_SESSION['licensee_id'] = $token_data['licensee_id'];
    $_SESSION['licensee_name'] = $token_data['licensee_name'];

    // 3. Unset any regular user variables to prevent privilege escalation.
    unset($_SESSION['user_id'], $_SESSION['username'], $_SESSION['role'], $_SESSION['section']);

    // Log successful session setup
    error_log("Portal session created successfully for licensee ID: " . $token_data['licensee_id']);

    // 4. Load the main portal dashboard view.
    require_once __DIR__ . '/../views/portal_dashboard_view.php';
    exit();

} catch (Exception $e) {
    error_log("Portal access error - Exception: " . $e->getMessage() . 
              "\nStack trace: " . $e->getTraceAsString());
    require_once __DIR__ . '/../views/errors/invalid_link_view.php';
    exit();
}