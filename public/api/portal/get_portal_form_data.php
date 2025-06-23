<?php
/**
 * Portal API: Fetches data needed to build staff forms (designations, doc reqs).
 */
require_once __DIR__ . '/../../../src/init.php';
header('Content-Type: application/json');

try {
    // Security: Portal session check
    if (!isset($_SESSION['is_licensee_portal'], $_SESSION['licensee_id'])) {
        throw new Exception('Not authenticated for portal access.', 403);
    }
    
    $licensee_id = $_SESSION['licensee_id'];
    $contract_id = $_GET['contract_id'] ?? 0;

    if (empty($contract_id)) {
        throw new Exception('Contract ID is required.', 400);
    }

    // Security Check: Verify the contract belongs to the licensee
    $contract_check_stmt = $pdo->prepare("SELECT contract_type FROM contracts WHERE id = ? AND licensee_id = ?");
    $contract_check_stmt->execute([$contract_id, $licensee_id]);
    $contract = $contract_check_stmt->fetch();

    if (!$contract) {
        throw new Exception('Contract not found or access denied.', 404);
    }
    
    // Fetch document requirements for the contract's type
    $doc_stmt = $pdo->prepare("SELECT Police, Medical, TA, PPO FROM varuna_contract_types WHERE ContractType = ?");
    $doc_stmt->execute([$contract['contract_type']]);
    $doc_reqs = $doc_stmt->fetch();

    // Fetch all available staff designations
    $desg_stmt = $pdo->query("SELECT designation_name FROM varuna_staff_designation ORDER BY designation_name ASC");
    $designations = $desg_stmt->fetchAll(PDO::FETCH_COLUMN);

    echo json_encode([
        'success' => true,
        'doc_reqs' => $doc_reqs ?: [],
        'designations' => $designations ?: []
    ]);

} catch (Exception $e) {
    http_response_code($e->getCode() > 0 ? $e->getCode() : 500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}