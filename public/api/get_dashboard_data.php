<?php
/**
 * API to fetch all aggregated data for the main dashboard.
 * REFACTORED: Uses separate, complete queries for different user roles 
 * to prevent SQL syntax errors and improve readability.
 */
require_once __DIR__ . '/../../src/init.php';
header('Content-Type: application/json');

try {
    // Ensure user is authenticated
    if (!isset($_SESSION['user_id'])) {
        throw new Exception("Authentication required.", 403);
    }

    $role = $_SESSION['role'];
    $params = [];

    // Initialize SQL query strings
    $total_licensees_sql = '';
    $total_contracts_sql = '';
    $total_staff_sql = '';
    $staff_status_sql = '';
    $licensee_breakdown_sql = '';

    // --- Logic Branch based on User Role ---
    if ($role === 'SCI') {
        // --- Queries for SCI Role (Filtered Data) ---
        
        $where_clause_contracts = "";
        $where_clause_staff = "";

        // Build the primary WHERE clause for contracts based on SCI's section
        if (!empty($_SESSION['section'])) {
            $where_clause_contracts = " WHERE c.section_code = ? ";
            $params[] = $_SESSION['section'];
        } elseif (!empty($_SESSION['department_section'])) {
            // This clause finds contracts belonging to a broader department/section
            $where_clause_contracts = " WHERE c.contract_type IN (SELECT vct.ContractType FROM varuna_contract_types vct WHERE vct.Section = ?) ";
            $params[] = $_SESSION['department_section'];
        } else {
            // If SCI has no assigned section, they see no data.
            $where_clause_contracts = " WHERE 1=0 "; 
        }

        // The staff filter depends on the contracts the SCI can see
        $where_clause_staff = " WHERE s.contract_id IN (SELECT c.id FROM contracts c " . $where_clause_contracts . ")";

        // Define the full SQL queries for the SCI role
        $total_licensees_sql = "SELECT COUNT(DISTINCT c.licensee_id) FROM contracts c" . $where_clause_contracts;
        $total_contracts_sql = "SELECT COUNT(c.id) FROM contracts c" . $where_clause_contracts;
        $total_staff_sql = "SELECT COUNT(s.id) FROM varuna_staff s" . $where_clause_staff;
        $staff_status_sql = "SELECT status, COUNT(*) as count FROM varuna_staff s " . $where_clause_staff . " GROUP BY status";
        
        $licensee_breakdown_sql = "
            SELECT 
                l.id as licensee_id, 
                l.name as licensee_name, 
                COUNT(DISTINCT c.id) as contract_count,
                COUNT(s.id) as staff_count
            FROM varuna_licensee l
            LEFT JOIN contracts c ON l.id = c.licensee_id
            LEFT JOIN varuna_staff s ON c.id = s.contract_id
            " . $where_clause_contracts . "
            GROUP BY l.id, l.name
            ORDER BY l.name ASC";

    } else {
        // --- Queries for ADMIN/VIEWER Roles (Unfiltered Data) ---

        $total_licensees_sql = "SELECT COUNT(id) FROM varuna_licensee";
        $total_contracts_sql = "SELECT COUNT(id) FROM contracts";
        $total_staff_sql = "SELECT COUNT(id) FROM varuna_staff";
        $staff_status_sql = "SELECT status, COUNT(*) as count FROM varuna_staff GROUP BY status";
        
        $licensee_breakdown_sql = "
            SELECT 
                l.id as licensee_id, 
                l.name as licensee_name, 
                COUNT(DISTINCT c.id) as contract_count,
                COUNT(s.id) as staff_count
            FROM varuna_licensee l
            LEFT JOIN contracts c ON l.id = c.licensee_id
            LEFT JOIN varuna_staff s ON c.id = s.contract_id
            GROUP BY l.id, l.name
            ORDER BY l.name ASC";
    }

    // --- Execute Queries and Fetch Data ---

    // Stats Cards
    $stmt = $pdo->prepare($total_licensees_sql);
    $stmt->execute($params);
    $licensee_count = $stmt->fetchColumn();

    $stmt = $pdo->prepare($total_contracts_sql);
    $stmt->execute($params);
    $contract_count = $stmt->fetchColumn();
    
    // For staff count, ADMIN/VIEWER use empty params, SCI uses the generated one.
    $staff_params = ($role === 'SCI') ? $params : [];
    $stmt = $pdo->prepare($total_staff_sql);
    $stmt->execute($staff_params);
    $staff_count = $stmt->fetchColumn();

    // Staff Status Pie Chart
    $stmt = $pdo->prepare($staff_status_sql);
    $stmt->execute($staff_params);
    $staff_status_data = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    // Licensee Breakdown Table
    $stmt = $pdo->prepare($licensee_breakdown_sql);
    $stmt->execute($params);
    $licensee_breakdown = $stmt->fetchAll(PDO::FETCH_ASSOC);


    // --- Assemble and Return the Final JSON Response ---
    echo json_encode([
        'success' => true,
        'stats' => [
            'licensees' => $licensee_count ?: 0,
            'contracts' => $contract_count ?: 0,
            'staff' => $staff_count ?: 0
        ],
        'staff_status_chart' => [
            'labels' => !empty($staff_status_data) ? array_keys($staff_status_data) : [],
            'data' => !empty($staff_status_data) ? array_values($staff_status_data) : []
        ],
        'licensee_breakdown' => $licensee_breakdown
    ]);

} catch (Exception $e) {
    // Generic error handling
    http_response_code($e->getCode() ?: 500);
    echo json_encode(["success" => false, "error" => $e->getMessage()]);
}
