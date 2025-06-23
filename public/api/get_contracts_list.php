<?php
require_once __DIR__ . '/../../src/init.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) { http_response_code(403); exit(); }

$base_query = "SELECT c.id, c.contract_name, c.contract_type, c.station_code, l.name as licensee_name, c.status 
               FROM contracts c LEFT JOIN varuna_licensee l ON c.licensee_id = l.id";
$params = [];

// Apply filter unless the user is from IT CELL
if ($_SESSION['section'] !== 'IT CELL') {
    $base_query .= " WHERE c.section_code = ?";
    $params[] = $_SESSION['section'];
}

$stmt = $pdo->prepare($base_query);
$stmt->execute($params);
echo json_encode(['data' => $stmt->fetchAll()]);