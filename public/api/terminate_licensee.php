<?php
require_once __DIR__ . '/../../src/init.php';
header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !in_array($_SESSION['role'], ['ADMIN', 'SCI'])) throw new Exception('Access Denied', 403);
    validate_csrf_token($_POST['csrf_token'] ?? '');
    $licensee_id = $_POST['licensee_id'] ?? 0;
    if (empty($licensee_id)) throw new Exception('Licensee ID is required.', 400);

    $pdo->beginTransaction();

    // Find all contracts under this licensee
    $contracts_stmt = $pdo->prepare("SELECT id FROM contracts WHERE licensee_id = ?");
    $contracts_stmt->execute([$licensee_id]);
    $contract_ids = $contracts_stmt->fetchAll(PDO::FETCH_COLUMN);

    if (!empty($contract_ids)) {
        // Terminate all staff under those contracts
        $placeholders = implode(',', array_fill(0, count($contract_ids), '?'));
        $staff_stmt = $pdo->prepare("UPDATE varuna_staff SET status = 'Terminated' WHERE contract_id IN ($placeholders)");
        $staff_stmt->execute($contract_ids);

        // Terminate all contracts
        $contract_update_stmt = $pdo->prepare("UPDATE contracts SET status = 'Terminated' WHERE licensee_id = ?");
        $contract_update_stmt->execute([$licensee_id]);
    }
    
    // Terminate the licensee
    $licensee_stmt = $pdo->prepare("UPDATE varuna_licensee SET status = 'Terminated' WHERE id = ?");
    $licensee_stmt->execute([$licensee_id]);
    
    $pdo->commit();
    log_activity($pdo, 'LICENSEE_TERMINATE', ['details' => "Terminated licensee ID: $licensee_id and all associated contracts/staff."]);
    echo json_encode(['success' => true, 'message' => 'Licensee and all associated records have been terminated.', 'new_csrf_token' => generate_csrf_token()]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code($e->getCode() > 0 ? $e->getCode() : 500);
    echo json_encode(['success' => false, 'message' => $e->getMessage(), 'new_csrf_token' => generate_csrf_token()]);
}