<?php
require_once __DIR__ . '/../../src/init.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) { http_response_code(403); exit(); }

$base_query = "SELECT c.id, c.contract_name, c.contract_type, c.station_code, c.section_code, 
               l.name as licensee_name, c.status,
               COUNT(s.id) as staff_count,
               SUM(CASE WHEN s.status = 'pending' THEN 1 ELSE 0 END) as pending_staff,
               SUM(CASE WHEN s.status = 'approved' THEN 1 ELSE 0 END) as approved_staff,
               SUM(CASE WHEN s.status = 'terminated' THEN 1 ELSE 0 END) as terminated_staff
               FROM contracts c 
               LEFT JOIN varuna_licensee l ON c.licensee_id = l.id
               LEFT JOIN varuna_staff s ON c.id = s.contract_id";
$params = [];
$where_clauses = [];

// Handle filters
if (isset($_GET['section'])) {
    $where_clauses[] = "c.section_code = ?";
    $params[] = $_GET['section'];
}

if (isset($_GET['station'])) {
    $where_clauses[] = "c.station_code = ?";
    $params[] = $_GET['station'];
}

if (isset($_GET['contract_type'])) {
    $where_clauses[] = "c.contract_type = ?";
    $params[] = $_GET['contract_type'];
}

// Apply section filter for SCI role
if ($_SESSION['role'] === 'SCI' && !empty($_SESSION['section'])) {
    $where_clauses[] = "c.section_code = ?";
    $params[] = $_SESSION['section'];
}

// Combine where clauses if any exist
if (!empty($where_clauses)) {
    $base_query .= " WHERE " . implode(" AND ", $where_clauses);
}

$base_query .= " GROUP BY c.id, c.contract_name, c.contract_type, c.station_code, c.section_code, l.name, c.status";

$stmt = $pdo->prepare($base_query);
$stmt->execute($params);
echo json_encode(['data' => $stmt->fetchAll()]);