<?php
/**
 * Portal API: Fetches staff for a given contract, ensuring ownership.
 */
require_once __DIR__ . '/../../../src/init.php';
header('Content-Type: application/json');

try {
    // Security: Portal session check.
    if (!isset($_SESSION['is_licensee_portal'], $_SESSION['licensee_id'])) {
        throw new Exception('Not authenticated for portal access.', 403);
    }
    
    $licensee_id = $_SESSION['licensee_id'];
    $contract_id = $_GET['contract_id'] ?? 0;

    if (empty($contract_id)) {
        throw new Exception('Contract ID is required.', 400);
    }

    // This query now uses a JOIN to ensure we only fetch staff from contracts
    // owned by the currently logged-in licensee. This is a critical security check.
    $stmt = $pdo->prepare(
        "SELECT s.id, s.name, s.designation, s.contact, s.status
         FROM varuna_staff s
         JOIN contracts c ON s.contract_id = c.id
         WHERE s.contract_id = ? AND c.licensee_id = ?"
    );
    $stmt->execute([$contract_id, $licensee_id]);
    $staff_list = $stmt->fetchAll();

    echo json_encode(['data' => $staff_list]);

} catch (Exception $e) {
    http_response_code($e->getCode() > 0 ? $e->getCode() : 500);
    echo json_encode(['success' => false, 'message' => $e->getMessage(), 'data' => []]);
}