<?php
/**
 * Starts a secure session.
 */
function start_secure_session() {
    // If a session is already active, there's nothing for us to do.
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    // Set parameters only if no session has been started yet.
    if (session_status() === PHP_SESSION_NONE) {
        $session_name = 'varuna_session_id';
        $secure = false; // Set to true if you are using HTTPS on production
        $httponly = true;

        session_set_cookie_params(0, '/', '', $secure, $httponly);
        session_name($session_name);
        session_start();
    }
}

/**
 * Regenerates the session ID to prevent session fixation.
 */
function regenerate_session() {
    session_regenerate_id(true);
}

/**
 * Generates and stores a CSRF token if one doesn't exist.
 * @return string The CSRF token.
 */
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}



/**
 * Validates the submitted CSRF token.
 * Throws an exception if validation fails, which can be caught by APIs.
 * @param string $submitted_token The token from the form submission.
 */
function validate_csrf_token($submitted_token) {
    if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $submitted_token)) {
        // Instead of die(), throw an exception that our API's try-catch block can handle.
        throw new Exception('CSRF validation failed. Please refresh and try again.');
    }
    // Token is valid, unset it to prevent reuse
    unset($_SESSION['csrf_token']);
}