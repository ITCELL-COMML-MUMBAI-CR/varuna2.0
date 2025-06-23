<?php
require_once __DIR__ . '/../../src/init.php';
header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !in_array($_SESSION['role'], ['ADMIN', 'SCI'])) throw new Exception('Access Denied', 403);
    validate_csrf_token($_POST['csrf_token'] ?? '');
    $contract_id = $_POST['contract_id'] ?? 0;
    if (empty($contract_id)) throw new Exception('Contract ID is required.', 400);

    $pdo->beginTransaction();

    // Terminate all staff under this contract
    $staff_stmt = $pdo->prepare("UPDATE varuna_staff SET status = 'Terminated' WHERE contract_id = ?");
    $staff_stmt->execute([$contract_id]);

    // Terminate the contract
    $contract_stmt = $pdo->prepare("UPDATE contracts SET status = 'Terminated' WHERE id = ?");
    $contract_stmt->execute([$contract_id]);

    $pdo->commit();
    log_activity($pdo, 'CONTRACT_TERMINATE', ['details' => "Terminated contract ID: $contract_id and all associated staff."]);
    echo json_encode(['success' => true, 'message' => 'Contract and all associated staff have been terminated.', 'new_csrf_token' => generate_csrf_token()]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code($e->getCode() > 0 ? $e->getCode() : 500);
    echo json_encode(['success' => false, 'message' => $e->getMessage(), 'new_csrf_token' => generate_csrf_token()]);
}