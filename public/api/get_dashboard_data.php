<?php
/**
 * API to fetch all aggregated data for the main dashboard.
 * FIX: Ensures VIEWERS and ADMINS get unfiltered data.
 */
require_once __DIR__ . '/../../src/init.php';
header('Content-Type: application/json');

try {
    if (!isset($_SESSION['user_id'])) {
        throw new Exception("Authentication required.", 403);
    }

    // --- 1. Define WHERE clauses based on user role ---
    $where_clause_contracts = "";
    $where_clause_staff = "";
    $params = [];

    // --- FIX: Apply filters ONLY for SCI role. Admin and Viewer see all. ---
    if ($_SESSION['role'] === 'SCI') {
        if (!empty($_SESSION['section'])) {
            $where_clause_contracts = " WHERE c.section_code = ? ";
            $params[] = $_SESSION['section'];
        } elseif (!empty($_SESSION['department_section'])) {
            $where_clause_contracts = " WHERE c.contract_type IN (SELECT vct.ContractType FROM varuna_contract_types vct WHERE vct.Section = ?) ";
            $params[] = $_SESSION['department_section'];
        } else {
            $where_clause_contracts = " WHERE 1=0 "; // No section, no data
        }
        // Staff filter depends on the contract filter
        $where_clause_staff = " WHERE s.contract_id IN (SELECT c.id FROM contracts c " . $where_clause_contracts . ")";
    }

    // --- 2. Fetch Main Stats Cards Data ---
    $total_licensees_sql = "SELECT COUNT(id) FROM varuna_licensee" . ($params ? " WHERE id IN (SELECT c.licensee_id FROM contracts c " . $where_clause_contracts . ")" : "");
    $stmt = $pdo->prepare($total_licensees_sql);
    $stmt->execute($params);
    $licensee_count = $stmt->fetchColumn();

    $total_contracts_sql = "SELECT COUNT(c.id) FROM contracts c" . $where_clause_contracts;
    $stmt = $pdo->prepare($total_contracts_sql);
    $stmt->execute($params);
    $contract_count = $stmt->fetchColumn();

    $total_staff_sql = "SELECT COUNT(s.id) FROM varuna_staff s" . $where_clause_staff;
    $stmt = $pdo->prepare($total_staff_sql);
    $stmt->execute($params);
    $staff_count = $stmt->fetchColumn();


    // --- 3. Fetch Data for Staff Status Pie Chart ---
    $staff_status_sql = "SELECT status, COUNT(*) as count FROM varuna_staff s " . $where_clause_staff . " GROUP BY status";
    $stmt = $pdo->prepare($staff_status_sql);
    $stmt->execute($params);
    $staff_status_data = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    // --- 4. Fetch Data for the Licensee Breakdown Table ---
    $licensee_breakdown_sql = "
        SELECT 
            l.id as licensee_id, 
            l.name as licensee_name, 
            COUNT(DISTINCT c.id) as contract_count,
            COUNT(s.id) as staff_count
        FROM varuna_licensee l
        LEFT JOIN contracts c ON l.id = c.licensee_id " . $where_clause_contracts . "
        LEFT JOIN varuna_staff s ON c.id = s.contract_id
        GROUP BY l.id, l.name
        ORDER BY l.name ASC";
    $stmt = $pdo->prepare($licensee_breakdown_sql);
    $stmt->execute($params);
    $licensee_breakdown = $stmt->fetchAll();


    // --- 5. Assemble and Return the Response ---
    echo json_encode([
        'success' => true,
        'stats' => [
            'licensees' => $licensee_count,
            'contracts' => $contract_count,
            'staff' => $staff_count
        ],
        'staff_status_chart' => [
            'labels' => array_keys($staff_status_data),
            'data' => array_values($staff_status_data)
        ],
        'licensee_breakdown' => $licensee_breakdown
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "error" => $e->getMessage()]);
}