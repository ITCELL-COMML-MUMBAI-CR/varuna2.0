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
    $staff_stmt = $pdo->prepare("
        SELECT c.id as contract_id, 
               c.contract_name,
               c.contract_type,
               c.station_code,
               COUNT(vs.id) as staff_count,
               SUM(CASE WHEN vs.status = 'pending' THEN 1 ELSE 0 END) as pending_staff,
               SUM(CASE WHEN vs.status = 'approved' THEN 1 ELSE 0 END) as approved_staff,
               SUM(CASE WHEN vs.status = 'terminated' THEN 1 ELSE 0 END) as terminated_staff
        FROM contracts c
        LEFT JOIN varuna_staff vs ON c.id = vs.contract_id
        WHERE c.licensee_id = ?
        GROUP BY c.id, c.contract_name, c.contract_type, c.station_code
        ORDER BY c.contract_name ASC
    ");
    $staff_stmt->execute([$licensee_id]);
    $contracts = $staff_stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => $contracts
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}