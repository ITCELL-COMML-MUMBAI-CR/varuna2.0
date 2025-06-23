<?php
/**
 * Controller for the Licensee Portal.
 * Validates the access token and sets up a limited session.
 */

if (!defined('VARUNA_ENTRY_POINT')) { die('Direct access not allowed.'); }

global $pdo;

$token = $_GET['token'] ?? '';

if (empty($token)) {
    // No token provided, show an error.
    require_once __DIR__ . '/../views/errors/invalid_link_view.php';
    exit();
}

// Prepare to validate the token against the database.
$stmt = $pdo->prepare(
    "SELECT l.id as licensee_id, l.name as licensee_name, t.expires_at, t.is_active 
     FROM varuna_access_tokens t
     JOIN varuna_licensee l ON t.licensee_id = l.id
     WHERE t.token = ? LIMIT 1"
);
$stmt->execute([$token]);
$token_data = $stmt->fetch();

// Check for all failure conditions.
if (!$token_data || !$token_data['is_active'] || new DateTime() > new DateTime($token_data['expires_at'])) {
    // Token is not found, not active, or has expired.
    require_once __DIR__ . '/../views/errors/invalid_link_view.php';
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