<?php
/**
 * VARUNA System - API to Add a new Staff Designation
 */
require_once __DIR__ . '/../../../src/init.php';
header('Content-Type: application/json');

$response = ['success' => false, 'message' => 'An unknown error occurred.'];

try {
    // Security: Ensure user is ADMIN from IT CELL
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['user_id']) || $_SESSION['section'] !== 'IT CELL') {
        throw new Exception('Access Denied.', 403);
    }
    
    validate_csrf_token($_POST['csrf_token'] ?? '');

    if (empty($_POST['designation_name'])) {
        throw new Exception('Designation Name is required.');
    }

    $stmt = $pdo->prepare("INSERT INTO varuna_staff_designation (designation_name) VALUES (?)");
    $stmt->execute([$_POST['designation_name']]);

    log_activity($pdo, 'ADMIN_ADD_DESIGNATION', ['details' => "Added new designation: {$_POST['designation_name']}"]);
    
    $response = [
        'success' => true, 
        'message' => 'Designation added successfully!', 
        'new_csrf_token' => generate_csrf_token()
    ];

} catch (PDOException $e) {
    $response['message'] = 'Database error: This designation may already exist.';
    $response['new_csrf_token'] = generate_csrf_token();
    log_activity($pdo, 'ADMIN_ADD_DESIGNATION_FAIL', ['details' => $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(400);
    $response['message'] = $e->getMessage();
    $response['new_csrf_token'] = generate_csrf_token();
}

echo json_encode($response);