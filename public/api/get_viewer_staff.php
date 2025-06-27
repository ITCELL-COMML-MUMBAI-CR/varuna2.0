<?php
/**
 * API to fetch comprehensive staff details for the Viewer page.
 * Supports server-side processing for DataTables, including filtering and searching.
 */
require_once __DIR__ . '/../../src/init.php';
header('Content-Type: application/json');

try {
    if (!isset($_SESSION['user_id'])) {
        throw new Exception("Authentication required.", 403);
    }

    // DataTables server-side processing parameters
    $draw = $_POST['draw'] ?? 1;
    $start = $_POST['start'] ?? 0;
    $length = $_POST['length'] ?? 10;
    $searchValue = $_POST['search']['value'] ?? '';

    // Custom filter parameters
    $filter_licensee = $_POST['filter_licensee'] ?? '';
    $filter_contract = $_POST['filter_contract'] ?? '';
    $filter_station = $_POST['filter_station'] ?? '';
    $filter_section = $_POST['filter_section'] ?? '';

    // Base query with all necessary joins
    $base_sql = "FROM varuna_staff s
                 LEFT JOIN contracts c ON s.contract_id = c.id
                 LEFT JOIN varuna_licensee l ON c.licensee_id = l.id";
    
    // Build the WHERE clause
    $where_conditions = [];
    $params = [];

    // --- Session-based security filter for SCIs ---
    if ($_SESSION['role'] === 'SCI') {
        if (!empty($_SESSION['section'])) {
            $where_conditions[] = "c.section_code = ?";
            $params[] = $_SESSION['section'];
        } elseif (!empty($_SESSION['department_section'])) {
            $where_conditions[] = "c.contract_type IN (SELECT vct.ContractType FROM varuna_contract_types vct WHERE vct.Section = ?)";
            $params[] = $_SESSION['department_section'];
        } else {
            $where_conditions[] = "1=0"; // No section assigned, show no data
        }
    }
    
    // --- Custom filters from the UI ---
    if (!empty($filter_licensee)) { $where_conditions[] = "c.licensee_id = ?"; $params[] = $filter_licensee; }
    if (!empty($filter_contract)) { $where_conditions[] = "s.contract_id = ?"; $params[] = $filter_contract; }
    if (!empty($filter_station)) { $where_conditions[] = "c.station_code = ?"; $params[] = $filter_station; }
    if (!empty($filter_section)) { $where_conditions[] = "c.section_code = ?"; $params[] = $filter_section; }
    
    // --- Search filter ---
    if (!empty($searchValue)) {
        $where_conditions[] = "(s.name LIKE ? OR s.designation LIKE ? OR l.name LIKE ? OR c.contract_name LIKE ? OR s.id LIKE ?)";
        $search_param = "%{$searchValue}%";
        array_push($params, $search_param, $search_param, $search_param, $search_param, $search_param);
    }

    $where_sql = "";
    if (!empty($where_conditions)) {
        $where_sql = " WHERE " . implode(" AND ", $where_conditions);
    }

    // Get total records count (without filtering)
    $totalRecords_stmt = $pdo->query("SELECT COUNT(s.id) " . $base_sql);
    $totalRecords = $totalRecords_stmt->fetchColumn();
    
    // Get total records count (with filtering)
    $totalFiltered_stmt = $pdo->prepare("SELECT COUNT(s.id) " . $base_sql . $where_sql);
    $totalFiltered_stmt->execute($params);
    $totalFiltered = $totalFiltered_stmt->fetchColumn();

    // Fetch the data for the current page
    $data_sql = "SELECT 
                    s.id, s.profile_image, s.name, s.designation, s.contact, s.adhar_card_number, s.status,
                    s.police_image, s.police_expiry_date, s.medical_image, s.medical_expiry_date,
                    c.contract_name, c.station_code, l.name as licensee_name "
                 . $base_sql . $where_sql . " ORDER BY s.name ASC LIMIT ? OFFSET ?";
    
    $params[] = (int)$length;
    $params[] = (int)$start;

    $data_stmt = $pdo->prepare($data_sql);
    foreach ($params as $key => $val) {
        $data_stmt->bindValue($key + 1, $val, is_int($val) ? PDO::PARAM_INT : PDO::PARAM_STR);
    }
    $data_stmt->execute();
    $staff_list = $data_stmt->fetchAll();

    // Get status counts
    $statusCountQuery = "SELECT 
        SUM(CASE WHEN s.status = 'pending' THEN 1 ELSE 0 END) as pending_count,
        SUM(CASE WHEN s.status = 'approved' THEN 1 ELSE 0 END) as approved_count,
        SUM(CASE WHEN s.status = 'terminated' THEN 1 ELSE 0 END) as terminated_count
        FROM varuna_staff s " . $base_sql . $where_sql;
    
    $statusCountStmt = $pdo->prepare($statusCountQuery);
    $statusCountStmt->execute($params);
    $statusCounts = $statusCountStmt->fetch(PDO::FETCH_ASSOC);

    // Prepare the final response for DataTables
    echo json_encode([
        "draw" => intval($draw),
        "recordsTotal" => intval($totalRecords),
        "recordsFiltered" => intval($totalFiltered),
        "data" => $staff_list,
        "statusCounts" => $statusCounts
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage(), "data" => []]);
}