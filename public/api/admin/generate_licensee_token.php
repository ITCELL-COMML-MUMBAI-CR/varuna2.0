<?php
/**
 * API to generate a secure, one-time access token for a licensee.
 */
require_once __DIR__ . '/../../../src/init.php';
header('Content-Type: application/json');

$response = ['success' => false, 'message' => 'An error occurred.'];

try {
    // Security: Only logged-in users (e.g., ADMIN or SCI) can generate tokens.
    if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['ADMIN', 'SCI'])) {
        throw new Exception('Access Denied.', 403);
    }
    
    validate_csrf_token($_POST['csrf_token'] ?? '');

    $licensee_id = $_POST['licensee_id'] ?? 0;
    if (empty($licensee_id)) {
        throw new Exception('Licensee ID is required.', 400);
    }

    // Ensure the licensee is not terminated before generating a token
    $status_stmt = $pdo->prepare("SELECT status FROM varuna_licensee WHERE id = ? LIMIT 1");
    $status_stmt->execute([$licensee_id]);
    $licensee_status = $status_stmt->fetchColumn();

    if ($licensee_status === false) {
        throw new Exception('Licensee not found.', 404);
    }

    if (strtolower($licensee_status) === 'terminated') {
        throw new Exception('Cannot generate access link for a terminated licensee.', 400);
    }

    // Generate a cryptographically secure random token.
    $token = bin2hex(random_bytes(32)); 
    
    // Set an expiration date for the token (e.g., 30 days from now).
    $expires_at = (new DateTimeImmutable())->add(new DateInterval('P30D'))->format('Y-m-d H:i:s');
    
    // Deactivate any old tokens for this licensee to ensure only the latest link works.
    $deactivate_stmt = $pdo->prepare("UPDATE varuna_access_tokens SET is_active = 0 WHERE licensee_id = ?");
    $deactivate_stmt->execute([$licensee_id]);

    // Insert the new token into the database.
    $stmt = $pdo->prepare(
        "INSERT INTO varuna_access_tokens (licensee_id, token, expires_at, created_by_user_id) VALUES (?, ?, ?, ?)"
    );
    $stmt->execute([$licensee_id, $token, $expires_at, $_SESSION['user_id']]);
    
    // Construct the full link for the SCI to share.
    $link = BASE_URL . 'portal?token=' . $token;

    log_activity($pdo, 'LICENSEE_TOKEN_GENERATE', ['details' => "Generated access link for licensee ID: $licensee_id"]);
    
    $response = [
        'success' => true,
        'message' => 'Access link generated successfully! It is valid for 30 days.',
        'link' => $link,
        'new_csrf_token' => generate_csrf_token()
    ];

} catch (Exception $e) {
    http_response_code($e->getCode() > 0 ? $e->getCode() : 500);
    $response['message'] = $e->getMessage();
    $response['new_csrf_token'] = generate_csrf_token();
}

echo json_encode($response);