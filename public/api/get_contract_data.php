<?php
/**
 * API to get all data related to a contract
 * Current Time: Tuesday, June 24, 2025 at 4:25 PM IST
 * Location: Kalyan, Maharashtra, India
 */
require_once __DIR__ . '/../../src/init.php';
header('Content-Type: application/json');

$response = ['success' => false, 'message' => 'Could not find contract details.'];
$contract_id = $_GET['id'] ?? 0;

// Basic validation
if (!$contract_id || !isset($_SESSION['user_id'])) {
    http_response_code(400);
    $response['message'] = 'A valid contract ID is required.';
    echo json_encode($response);
    exit();
}

try {
    // 1. Get Contract Details using LEFT JOIN to be more robust
    $stmt = $pdo->prepare("
        SELECT c.*, l.name as licensee_name 
        FROM contracts c 
        LEFT JOIN varuna_licensee l ON c.licensee_id = l.id 
        WHERE c.id = ?
    ");
    $stmt->execute([$contract_id]);
    $contract = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($contract) {
        // 2. Get Document Requirements based on Contract Type
        // =================== SQL QUERY UPDATED ===================
        // Added 'FSSAI', 'FireSafety', 'PestControl', 'RailNeerAvailability', and 'WaterSafety' to the SELECT statement.
        $stmt_docs = $pdo->prepare("SELECT Police, Medical, TA, PPO, AadharCard, FSSAI, FireSafety, PestControl, RailNeerAvailability, WaterSafety FROM varuna_contract_types WHERE ContractType = ?");
        // =========================================================
        $stmt_docs->execute([trim($contract['contract_type'])]);
        $doc_reqs = $stmt_docs->fetch(PDO::FETCH_ASSOC);

        // 3. Get Existing Staff List for this Contract
        $stmt_staff = $pdo->prepare("SELECT id, name, designation, adhar_card_number, status FROM varuna_staff WHERE contract_id = ? ORDER BY name ASC");
        $stmt_staff->execute([$contract_id]);
        $staff_list = $stmt_staff->fetchAll(PDO::FETCH_ASSOC);
        
        $response = [
            'success' => true,
            'contract' => $contract,
            'doc_reqs' => $doc_reqs ?: [], // Ensure it's an array even if not found
            'staff_list' => $staff_list
        ];
    } else {
        http_response_code(404);
        $response['message'] = 'Contract not found.';
    }

} catch (PDOException $e) {
    http_response_code(500);
    // For production, log the detailed error and show a generic message
    error_log("API Error in get_contract_data.php: " . $e->getMessage());
    $response['message'] = 'A database error occurred.';
}

echo json_encode($response);
