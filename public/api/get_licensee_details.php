<?php
/**
 * API to fetch a detailed breakdown of a single licensee,
 * including their contracts and the staff under each contract.
 */
require_once __DIR__ . '/../../src/init.php';
header('Content-Type: application/json');

try {
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Authentication required.', 403);
    }

    $licensee_id = $_GET['licensee_id'] ?? 0;
    if (empty($licensee_id)) {
        throw new Exception('Licensee ID is required.', 400);
    }

    // Get the main licensee name
    $licensee_stmt = $pdo->prepare("SELECT name FROM varuna_licensee WHERE id = ?");
    $licensee_stmt->execute([$licensee_id]);
    $licensee_name = $licensee_stmt->fetchColumn();

    if (!$licensee_name) {
        throw new Exception('Licensee not found.', 404);
    }

    // Get all contracts for this licensee
    $contracts_stmt = $pdo->prepare("SELECT id, contract_name, status FROM contracts WHERE licensee_id = ? ORDER BY contract_name ASC");
    $contracts_stmt->execute([$licensee_id]);
    $contracts = $contracts_stmt->fetchAll();

    $response_contracts = [];

    // For each contract, get its staff
    $staff_stmt = $pdo->prepare("SELECT id, name, status FROM varuna_staff WHERE contract_id = ? ORDER BY name ASC");
    foreach ($contracts as $contract) {
        $staff_stmt->execute([$contract['id']]);
        $staff = $staff_stmt->fetchAll();
        
        $contract['staff'] = $staff;
        $response_contracts[] = $contract;
    }

    echo json_encode([
        'success' => true,
        'licensee_name' => $licensee_name,
        'contracts' => $response_contracts
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}