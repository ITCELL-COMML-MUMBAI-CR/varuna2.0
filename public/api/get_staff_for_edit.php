<?php
require_once __DIR__ . '/../../src/init.php';
require_once __DIR__ . '/../../config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false]);
    exit();
}

$staff_id = $_GET['id'] ?? '';
$response = ['success' => false];

if ($staff_id) {
    // Fetch staff data
    $stmt = $pdo->prepare("SELECT s.*, c.contract_type FROM varuna_staff s JOIN contracts c ON s.contract_id = c.id WHERE s.id = ?");
    $stmt->execute([$staff_id]);
    $staff_data = $stmt->fetch();

    if ($staff_data) {
        // Fetch document requirements
        $doc_stmt = $pdo->prepare("SELECT Police, Medical, TA, PPO FROM varuna_contract_types WHERE ContractType = ?");
        $doc_stmt->execute([trim($staff_data['contract_type'])]);
        $doc_reqs = $doc_stmt->fetch();
        
        // --- NEW: Fetch all active contracts for the dropdown ---
        $contracts_stmt = $pdo->query("SELECT id, contract_name, station_code FROM contracts WHERE status = 'Regular' ORDER BY contract_name ASC");
        $all_contracts = $contracts_stmt->fetchAll();

        $response = [
            'success' => true,
            'staff' => $staff_data,
            'doc_reqs' => $doc_reqs ?: [],
            'all_contracts' => $all_contracts
        ];
    }
}

echo json_encode($response);