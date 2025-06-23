<?php
/**
 * VARUNA System - API to Add a New User
 */
require_once __DIR__ . '/../../../src/init.php';
header('Content-Type: application/json');

try {
    // Security checks
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['user_id']) || $_SESSION['section'] !== 'IT CELL') {
        throw new Exception('Access Denied.', 403);
    }
    validate_csrf_token($_POST['csrf_token'] ?? '');

    // Data Validation
    if (empty($_POST['username']) || empty($_POST['password']) || empty($_POST['role'])) {
        throw new Exception('Username, Password, and Role are required.');
    }

    // Hash the password for secure storage
    $password_hash = password_hash($_POST['password'], PASSWORD_DEFAULT);

    // Prepare and execute the database insertion
    $sql = "INSERT INTO varuna_users (username, password, role, designation, section, department_section) 
            VALUES (:username, :password, :role, :designation, :section, :department_section)";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':username' => $_POST['username'],
        ':password' => $password_hash,
        ':role' => $_POST['role'],
        ':designation' => $_POST['designation'] ?? null,
        ':section' => !empty($_POST['section']) ? $_POST['section'] : null,
        ':department_section' => !empty($_POST['department_section']) ? $_POST['department_section'] : null,
    ]);

    $new_user_id = $pdo->lastInsertId();
    log_activity($pdo, 'ADMIN_ADD_USER', ['details' => "Created new user '{$_POST['username']}' with ID: $new_user_id"]);
    
    echo json_encode(['success' => true, 'message' => 'User created successfully!', 'new_csrf_token' => generate_csrf_token()]);

} catch (PDOException $e) {
    // Handle database errors, like a duplicate username
    $message = (strpos($e->getMessage(), 'Duplicate entry') !== false) ? 'This username already exists.' : 'A database error occurred.';
    log_activity($pdo, 'ADMIN_ADD_USER_FAIL', ['details' => $e->getMessage()]);
    echo json_encode(['success' => false, 'message' => $message, 'new_csrf_token' => generate_csrf_token()]);
} catch (Exception $e) {
    // Handle other errors like CSRF or permissions
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage(), 'new_csrf_token' => generate_csrf_token()]);
}