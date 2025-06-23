<?php
/**
 * Portal API: Fetches all contracts for the logged-in licensee.
 */
require_once __DIR__ . '/../../../src/init.php';
header('Content-Type: application/json');

try {
    // Security: This API is only for licensee portal sessions.
    if (!isset($_SESSION['is_licensee_portal'], $_SESSION['licensee_id'])) {
        throw new Exception('Not authenticated for portal access.', 403);
    }

    $licensee_id = $_SESSION['licensee_id'];

    $stmt = $pdo->prepare(
        "SELECT id, contract_name, contract_type, location, status, station_code 
         FROM contracts 
         WHERE licensee_id = ? 
         ORDER BY contract_name ASC"
    );
    $stmt->execute([$licensee_id]);
    $contracts = $stmt->fetchAll();

    echo json_encode(['success' => true, 'contracts' => $contracts]);

} catch (Exception $e) {
    http_response_code($e->getCode() > 0 ? $e->getCode() : 500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}