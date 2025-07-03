<?php
/**
 * Controller for the Licensee Portal.
 * Validates the access token and sets up a limited session.
 */

if (!defined('VARUNA_ENTRY_POINT')) { die('Direct access not allowed.'); }

global $pdo;

try {
    // Clear any existing session data to prevent unauthorized access
    session_unset();
    
    $token = $_GET['token'] ?? '';

    // Validate token format (should be 64 characters hexadecimal)
    if (empty($token) || !preg_match('/^[a-f0-9]{64}$/', $token)) {
        require_once __DIR__ . '/../views/errors/invalid_link_view.php';
        exit();
    }

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
        require_once __DIR__ . '/../views/errors/invalid_link_view.php';
        exit();
    }

    // Check if token is active
    if (!$token_data['is_active']) {
        require_once __DIR__ . '/../views/errors/invalid_link_view.php';
        exit();
    }

    // Check if licensee exists and is not terminated
    if (!$token_data['licensee_id'] || !$token_data['licensee_name'] || strtolower($token_data['licensee_status']) === 'terminated') {
        require_once __DIR__ . '/../views/errors/invalid_link_view.php';
        exit();
    }

    // Check if token has expired
    $current_date = new DateTime();
    $expiry_date = new DateTime($token_data['expires_at']);
    
    if ($current_date > $expiry_date) {
        require_once __DIR__ . '/../views/errors/expired_portal_link.php';
        exit();
    }

    // --- Success! The token is valid. ---

    // 1. Create a clean, secure session for the licensee.
    regenerate_session();

    // 2. Set specific session variables to identify this as a portal session.
    $_SESSION['is_licensee_portal'] = true;
    $_SESSION['licensee_id'] = $token_data['licensee_id'];
    $_SESSION['licensee_name'] = $token_data['licensee_name'];

    // 3. Unset any regular user variables to prevent privilege escalation.
    unset($_SESSION['user_id'], $_SESSION['username'], $_SESSION['role'], $_SESSION['section']);

    // 4. Load the main portal dashboard view.
    require_once __DIR__ . '/../views/portal_dashboard_view.php';
    exit();

} catch (Exception $e) {
    error_log("Portal access error: " . $e->getMessage());
    require_once __DIR__ . '/../views/errors/invalid_link_view.php';
    exit();
}