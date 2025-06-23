<?php
require_once __DIR__ . '/../../src/init.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) { 
    http_response_code(403); 
    exit(); 
}

// Base query to fetch all approved staff
$base_query = "SELECT s.id, s.name, s.designation, c.contract_name, c.station_code 
               FROM varuna_staff s 
               JOIN contracts c ON s.contract_id = c.id
               WHERE s.status = 'approved'";
$params = [];

// FIX: Apply filters ONLY for the SCI role. Admins and Viewers will see everything.
if ($_SESSION['role'] === 'SCI') {
    // Check if user is a geographical SCI
    if (!empty($_SESSION['section'])) {
        $base_query .= " AND c.section_code = ?";
        $params[] = $_SESSION['section'];
    } 
    // Check if user is a departmental SCI
    elseif (!empty($_SESSION['department_section'])) {
        $base_query .= " AND c.contract_type IN (SELECT ContractType FROM varuna_contract_types WHERE Section = ?)";
        $params[] = $_SESSION['department_section'];
    }
    // If an SCI has no assigned section, they see nothing.
    else {
        $base_query .= " AND 1 = 0";
    }
}

$stmt = $pdo->prepare($base_query);
$stmt->execute($params);
$staff_list = $stmt->fetchAll();

echo json_encode(['data' => $staff_list]);