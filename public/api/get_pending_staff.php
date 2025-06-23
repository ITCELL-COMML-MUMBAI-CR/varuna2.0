<?php
/**
 * API to get pending staff for an SCI's section (Robust Version)
 * Current Time: Monday, June 16, 2025 at 1:07 PM IST
 * Location: Kalyan, Maharashtra, India
 */
require_once __DIR__ . '/../../src/init.php';
require_once __DIR__ . '/../../config.php';
header('Content-Type: application/json');

// Always start with a default response structure
$response = ['data' => []];

try {
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'SCI') {
        throw new Exception('Access denied.', 403);
    }

    $sci_geo_section = $_SESSION['section'] ?? null;
    $sci_dept_section = $_SESSION['department_section'] ?? null;
    $params = [];
    
    // Base query
    $query = "SELECT s.id, s.name, s.designation, c.contract_name, c.station_code 
              FROM varuna_staff s 
              JOIN contracts c ON s.contract_id = c.id ";

    // Dynamically build the rest of the query
    if (!empty($sci_geo_section)) {
        // --- Logic for GEOGRAPHICAL SCI ---
        $query .= "WHERE s.status = 'pending' AND c.section_code = ?";
        $params[] = $sci_geo_section;
    } elseif (!empty($sci_dept_section)) {
        // --- Logic for DEPARTMENTAL (TRAIN) SCI ---
        $query .= "JOIN varuna_contract_types vct ON c.contract_type = vct.ContractType
                   WHERE s.status = 'pending' AND vct.Section = ?";
        $params[] = $sci_dept_section;
    } else {
        // If user has no section assigned, return empty
        throw new Exception('User section not configured.');
    }

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $response['data'] = $stmt->fetchAll();


} catch (Exception $e) {
    // If any error occurs, we catch it and send a proper JSON error response
    // This prevents the "Invalid JSON" error in DataTables.
    http_response_code($e->getCode() > 0 ? $e->getCode() : 500);
    $response['error'] = $e->getMessage();
}

echo json_encode($response);