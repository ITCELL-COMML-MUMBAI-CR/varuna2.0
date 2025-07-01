<?php
/**
 * API to fetch all aggregated data for the main dashboard.
 * REFACTORED: Uses a single, dynamic query-building logic for all user roles 
 * to improve maintainability, reduce redundancy, and fix role-based data visibility bugs.
 */
require_once __DIR__ . '/../../src/init.php';
header('Content-Type: application/json');

try {
    if (!isset($_SESSION['user_id'])) {
        throw new Exception("Authentication required.", 403);
    }

    $role = $_SESSION['role'] ?? 'VIEWER';
    $params = [];
    $contract_where_clause = '';
    $staff_where_clause = '';
    $staff_and_clause = '';

    // --- Dynamically build WHERE clauses based on user role ---
    if ($role === 'SCI') {
        // Handle sci_cp users
        if ($_SESSION['designation'] === 'CCI CP') {
            // For sci_cp users, include both TRAIN section and their department section if available
            $conditions = ["c.section_code = 'TRAIN'"]; // Default access to TRAIN section
            if (!empty($_SESSION['department_section'])) {
                $conditions[] = "c.contract_type IN (SELECT vct.ContractType FROM varuna_contract_types vct WHERE vct.Section = ?)";
                $params[] = $_SESSION['department_section'];
            }
            $contract_where_clause = "WHERE (" . implode(" OR ", $conditions) . ")";
            // Staff clause for sci_cp needs to match the same conditions
            $staff_where_clause = "WHERE s.contract_id IN (SELECT c.id FROM contracts c WHERE " . implode(" OR ", $conditions) . ")";
        }
        // Regular SCI with section assigned
        else if (!empty($_SESSION['section'])) {
            $contract_where_clause = "WHERE c.section_code = ?";
            $params[] = $_SESSION['section'];
            $staff_where_clause = "WHERE s.contract_id IN (SELECT c.id FROM contracts c WHERE c.section_code = ?)";
        } 
        // SCI with department section only
        else if (!empty($_SESSION['department_section'])) {
            $contract_where_clause = "WHERE c.contract_type IN (SELECT vct.ContractType FROM varuna_contract_types vct WHERE vct.Section = ?)";
            $params[] = $_SESSION['department_section'];
            $staff_where_clause = "WHERE s.contract_id IN (SELECT c.id FROM contracts c WHERE c.contract_type IN (SELECT vct.ContractType FROM varuna_contract_types vct WHERE vct.Section = ?))";
        } 
        else {
            // SCI with no section assigned sees no data
            $contract_where_clause = "WHERE 1=0";
            $staff_where_clause = "WHERE 1=0";
        }

        // Generate complementary AND clause for queries that already have a WHERE
        if (!empty($staff_where_clause)) {
            $staff_and_clause = preg_replace('/^WHERE/i', 'AND', $staff_where_clause, 1);
        }
    }

    // --- Define Base SQL Queries ---
    $base_from_clause = "FROM contracts c
        LEFT JOIN varuna_licensee l ON c.licensee_id = l.id
        LEFT JOIN varuna_staff s ON c.id = s.contract_id ";

    // --- Execute All Queries ---

    // 1. Stats Cards
    $licensee_sql = "SELECT COUNT(DISTINCT c.licensee_id) FROM contracts c {$contract_where_clause}";
    $stmt = $pdo->prepare($licensee_sql);
    $stmt->execute($params);
    $licensee_count = $stmt->fetchColumn();

    $contract_sql = "SELECT COUNT(DISTINCT c.id) FROM contracts c {$contract_where_clause}";
    $stmt = $pdo->prepare($contract_sql);
    $stmt->execute($params);
    $contract_count = $stmt->fetchColumn();
    
    $staff_sql = "SELECT COUNT(s.id) FROM varuna_staff s {$staff_where_clause}";
    $stmt = $pdo->prepare($staff_sql);
    $stmt->execute($params);
    $staff_count = $stmt->fetchColumn();

    // 2. Staff Status Pie Chart
    $staff_status_sql = "SELECT status, COUNT(*) as count FROM varuna_staff s {$staff_where_clause} GROUP BY status";
    $stmt = $pdo->prepare($staff_status_sql);
    $stmt->execute($params);
    $staff_status_data = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    // 3. Licensee Breakdown
    $licensee_sql = "
        SELECT 
            l.id as licensee_id, 
            l.name as licensee_name,
            l.mobile_number,
            l.status, 
            COUNT(DISTINCT c.id) as contract_count,
            COUNT(DISTINCT s.id) as staff_count,
            SUM(CASE WHEN s.status = 'pending' THEN 1 ELSE 0 END) as pending_staff,
            SUM(CASE WHEN s.status = 'approved' THEN 1 ELSE 0 END) as approved_staff,
            SUM(CASE WHEN s.status = 'terminated' THEN 1 ELSE 0 END) as terminated_staff
        {$base_from_clause}
        {$contract_where_clause}
        GROUP BY l.id, l.name, l.mobile_number, l.status
        ORDER BY l.name ASC";
    $stmt = $pdo->prepare($licensee_sql);
    $stmt->execute($params);
    $licensee_breakdown = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 4. Section-wise Breakdown
    $section_sql = "
        SELECT 
            c.section_code,
            COUNT(DISTINCT l.id) as licensee_count,
            COUNT(DISTINCT c.id) as contract_count,
            COUNT(DISTINCT s.id) as staff_count,
            SUM(CASE WHEN s.status = 'pending' THEN 1 ELSE 0 END) as pending_staff,
            SUM(CASE WHEN s.status = 'approved' THEN 1 ELSE 0 END) as approved_staff,
            SUM(CASE WHEN s.status = 'terminated' THEN 1 ELSE 0 END) as terminated_staff
        {$base_from_clause}
        {$contract_where_clause}
        GROUP BY c.section_code
        ORDER BY c.section_code ASC";
    $stmt = $pdo->prepare($section_sql);
    $stmt->execute($params);
    $section_breakdown = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 5. Station-wise Breakdown
    $station_sql = "
        SELECT 
            c.station_code,
            COUNT(DISTINCT l.id) as licensee_count,
            COUNT(DISTINCT c.id) as contract_count,
            COUNT(DISTINCT s.id) as staff_count,
            SUM(CASE WHEN s.status = 'pending' THEN 1 ELSE 0 END) as pending_staff,
            SUM(CASE WHEN s.status = 'approved' THEN 1 ELSE 0 END) as approved_staff,
            SUM(CASE WHEN s.status = 'terminated' THEN 1 ELSE 0 END) as terminated_staff
        {$base_from_clause}
        {$contract_where_clause}
        GROUP BY c.station_code
        ORDER BY c.station_code ASC";
    $stmt = $pdo->prepare($station_sql);
    $stmt->execute($params);
    $station_breakdown = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 6. Contract Type Breakdown
    $contract_type_sql = "
        SELECT 
            c.contract_type,
            COUNT(DISTINCT l.id) as licensee_count,
            COUNT(DISTINCT c.id) as contract_count,
            COUNT(DISTINCT s.id) as staff_count,
            SUM(CASE WHEN s.status = 'pending' THEN 1 ELSE 0 END) as pending_staff,
            SUM(CASE WHEN s.status = 'approved' THEN 1 ELSE 0 END) as approved_staff,
            SUM(CASE WHEN s.status = 'terminated' THEN 1 ELSE 0 END) as terminated_staff
        {$base_from_clause}
        {$contract_where_clause}
        GROUP BY c.contract_type
        ORDER BY c.contract_type ASC";
    $stmt = $pdo->prepare($contract_type_sql);
    $stmt->execute($params);
    $contract_type_breakdown = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 7. Near Expiry Documents
    $expiring_docs_sql = "
        SELECT 
            s.id as staff_id,
            s.name as staff_name,
            s.designation,
            l.name as licensee_name,
            l.mobile_number as licensee_mobile,
            c.contract_name,
            c.contract_type,
            c.station_code,
            CASE 
                WHEN s.police_expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 'Police Verification'
                WHEN s.medical_expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 'Medical Certificate'
                WHEN s.ta_expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 'TA Document'
            END as expiring_document,
            CASE 
                WHEN s.police_expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN s.police_expiry_date
                WHEN s.medical_expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN s.medical_expiry_date
                WHEN s.ta_expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN s.ta_expiry_date
            END as expiry_date
        FROM varuna_staff s
        INNER JOIN contracts c ON s.contract_id = c.id
        INNER JOIN varuna_licensee l ON c.licensee_id = l.id
        WHERE s.status = 'approved'
        AND (
            s.police_expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
            OR s.medical_expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
            OR s.ta_expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
        )
        {$staff_and_clause}
        ORDER BY expiry_date ASC";
    $stmt = $pdo->prepare($expiring_docs_sql);
    $stmt->execute($params);
    $expiring_documents = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // --- Assemble and Return the Final JSON Response ---
    echo json_encode([
        'success' => true,
        'stats' => [
            'licensees' => $licensee_count ?: 0,
            'contracts' => $contract_count ?: 0,
            'staff' => $staff_count ?: 0,
        ],
        'staff_status_chart' => [
            'labels' => !empty($staff_status_data) ? array_map('ucfirst', array_keys($staff_status_data)) : [],
            'data'   => !empty($staff_status_data) ? array_values($staff_status_data) : [],
        ],
        'licensee_breakdown'      => $licensee_breakdown,
        'section_breakdown'       => $section_breakdown,
        'station_breakdown'       => $station_breakdown,
        'contract_type_breakdown' => $contract_type_breakdown,
        'expiring_documents'      => $expiring_documents,
    ]);

} catch (Exception $e) {
    // Return a generic error message
    http_response_code($e->getCode() ?: 500);
    echo json_encode([
        'success' => false,
        'error'   => 'An error occurred while fetching dashboard data. Please try again later.',
        'debug_info' => $e->getMessage(),// For development purposes
        'contract_type_sql' => $contract_type_sql
    ]);
}
