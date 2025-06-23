<?php
/**
 * API to get terminated staff for an SCI's section.
 */
require_once __DIR__ . '/../../src/init.php';
header('Content-Type: application/json');

try {
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'SCI') {
        throw new Exception('Access denied.', 403);
    }

    $sci_geo_section = $_SESSION['section'] ?? null;
    $sci_dept_section = $_SESSION['department_section'] ?? null;
    $params = [];
    
    $query = "SELECT s.id, s.name, s.designation, c.contract_name
              FROM varuna_staff s 
              JOIN contracts c ON s.contract_id = c.id ";

    $where_clause = " WHERE s.status = 'terminated' ";

    if ($sci_geo_section) {
        $where_clause .= " AND c.section_code = ?";
        $params[] = $sci_geo_section;
    } elseif ($sci_dept_section) {
        $where_clause .= " AND c.contract_type IN (SELECT vct.ContractType FROM varuna_contract_types vct WHERE vct.Section = ?)";
        $params[] = $sci_dept_section;
    } else {
        echo json_encode(['data' => []]); // No section, no data
        exit();
    }

    $stmt = $pdo->prepare($query . $where_clause);
    $stmt->execute($params);
    echo json_encode(['data' => $stmt->fetchAll()]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage(), 'data' => []]);
}