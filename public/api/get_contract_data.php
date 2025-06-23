<?php
/**
 * API to get all data related to a contract
 * Current Time: Friday, June 13, 2025 at 12:47 PM IST
 * Location: Kalyan, Maharashtra, India
 */
require_once __DIR__ . '/../../src/init.php';
require_once __DIR__ . '/../../config.php';
header('Content-Type: application/json');

$response = ['success' => false, 'message' => 'Could not find contract details.'];
$contract_id = $_GET['id'] ?? 0;

if (!$contract_id || !isset($_SESSION['user_id'])) {
    echo json_encode($response);
    exit();
}

try {
    // 1. Get Contract Details using LEFT JOIN to be more robust
    // This will return the contract even if licensee_id is NULL
    $stmt = $pdo->prepare("
        SELECT c.*, l.name as licensee_name 
        FROM contracts c 
        LEFT JOIN varuna_licensee l ON c.licensee_id = l.id 
        WHERE c.id = ?
    ");
    $stmt->execute([$contract_id]);
    $contract = $stmt->fetch();

    if ($contract) {
        // 2. Get Document Requirements based on Contract Type
        $stmt_docs = $pdo->prepare("SELECT Police, Medical, TA, PPO FROM varuna_contract_types WHERE ContractType = ?");
        $stmt_docs->execute([trim($contract['contract_type'])]);
        $doc_reqs = $stmt_docs->fetch();

        // 3. Get Existing Staff List for this Contract
        $stmt_staff = $pdo->prepare("SELECT id, name, designation, adhar_card_number, status FROM varuna_staff WHERE contract_id = ? ORDER BY name ASC");
        $stmt_staff->execute([$contract_id]);
        $staff_list = $stmt_staff->fetchAll();
        
        $response = [
            'success' => true,
            'contract' => $contract,
            'doc_reqs' => $doc_reqs ?: [], // Ensure it's an array even if not found
            'staff_list' => $staff_list
        ];
    }

} catch (PDOException $e) {
    // If there's a database error, log it and send a generic failure message
    // log_activity($pdo, 'API_ERROR', ['details' => $e->getMessage()]); // Optional: log the error
    $response['message'] = 'A database error occurred.';
}

echo json_encode($response);