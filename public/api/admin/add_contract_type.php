<?php
/**
 * VARUNA System - API to Add a new Contract Type
 * Current Time: Wednesday, June 18, 2025 at 5:05 PM IST
 * Location: Kalyan, Maharashtra, India
 */
require_once __DIR__ . '/../../../src/init.php';
header('Content-Type: application/json');

$response = ['success' => false, 'message' => 'An unknown error occurred.'];

try {
    // Security: Ensure user is a logged-in ADMIN
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['user_id']) || $_SESSION['role'] !== 'ADMIN') {
        throw new Exception('Access Denied.', 403);
    }

    // CSRF Validation
    validate_csrf_token($_POST['csrf_token'] ?? '');

    // Data Validation
    if (empty($_POST['ContractType']) || empty($_POST['TrainStation']) || empty($_POST['Section'])) {
        throw new Exception('Contract Name, Type, and Department Section are required.');
    }

    // Database Insertion
    $sql = "INSERT INTO varuna_contract_types (ContractType, TrainStation, Section, Police, Medical, TA, PPO, FSSAI, FireSafety, PestControl, RailNeerAvailability, WaterSafety) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $_POST['ContractType'], $_POST['TrainStation'], $_POST['Section'], $_POST['Police'], $_POST['Medical'],
        $_POST['TA'], $_POST['PPO'], $_POST['FSSAI'], $_POST['FireSafety'], $_POST['PestControl'],
        $_POST['RailNeerAvailability'], $_POST['WaterSafety']
    ]);

    log_activity($pdo, 'ADMIN_ADD_CONTRACT_TYPE', ['details' => "Added new type: {$_POST['ContractType']}"]);
    
    $response = [
        'success' => true, 
        'message' => 'Contract Type added successfully!', 
        'new_csrf_token' => generate_csrf_token()
    ];

} catch (PDOException $e) {
    // Handle specific database errors, like duplicates
    $response['message'] = 'Database error: This contract type may already exist.';
    $response['new_csrf_token'] = generate_csrf_token();
    log_activity($pdo, 'ADMIN_ADD_CONTRACT_TYPE_FAIL', ['details' => $e->getMessage()]);
} catch (Exception $e) {
    // Handle other errors like CSRF or permission issues
    if ($e->getCode() == 403) { http_response_code(403); } 
    else { http_response_code(400); }
    $response['message'] = $e->getMessage();
    $response['new_csrf_token'] = generate_csrf_token();
}

echo json_encode($response);