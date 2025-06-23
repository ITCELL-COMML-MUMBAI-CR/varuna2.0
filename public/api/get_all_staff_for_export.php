<?php
/**
 * API to fetch ALL staff details for PDF export, respecting the current filters.
 * This API ignores pagination.
 */
require_once __DIR__ . '/../../src/init.php';
header('Content-Type: application/json');

try {
    if (!isset($_SESSION['user_id'])) {
        throw new Exception("Authentication required.", 403);
    }
    $default_image_data_url = null;
    $default_image_path = __DIR__ . '/../images/default_profile.png';
    if (file_exists($default_image_path)) {
        $default_image_type = mime_content_type($default_image_path);
        $default_image_content = file_get_contents($default_image_path);
        $default_base64_image = base64_encode($default_image_content);
        $default_image_data_url = "data:$default_image_type;base64,$default_base64_image";
    }

    // Get filter parameters from the POST request
    $filter_licensee = $_POST['filter_licensee'] ?? '';
    $filter_contract = $_POST['filter_contract'] ?? '';
    $filter_station = $_POST['filter_station'] ?? '';
    $filter_section = $_POST['filter_section'] ?? '';
    // Although the viewer page doesn't have a search box, we include this for consistency
    $searchValue = $_POST['search'] ?? '';

    // Base query with all necessary joins
    $base_sql = "FROM varuna_staff s
                 LEFT JOIN contracts c ON s.contract_id = c.id
                 LEFT JOIN varuna_licensee l ON c.licensee_id = l.id";
    
    // Build the WHERE clause based on filters
    $where_conditions = [];
    $params = [];

    // Session-based security filter for SCIs
    if ($_SESSION['role'] === 'SCI') {
        if (!empty($_SESSION['section'])) {
            $where_conditions[] = "c.section_code = ?";
            $params[] = $_SESSION['section'];
        } elseif (!empty($_SESSION['department_section'])) {
            $where_conditions[] = "c.contract_type IN (SELECT vct.ContractType FROM varuna_contract_types vct WHERE vct.Section = ?)";
            $params[] = $_SESSION['department_section'];
        }
    }
    
    // Custom filters from the UI
    if (!empty($filter_licensee)) { $where_conditions[] = "c.licensee_id = ?"; $params[] = $filter_licensee; }
    if (!empty($filter_contract)) { $where_conditions[] = "s.contract_id = ?"; $params[] = $filter_contract; }
    if (!empty($filter_station)) { $where_conditions[] = "c.station_code = ?"; $params[] = $filter_station; }
    if (!empty($filter_section)) { $where_conditions[] = "c.section_code = ?"; $params[] = $filter_section; }
    if (!empty($searchValue)) {
        $where_conditions[] = "(s.name LIKE ? OR s.id LIKE ?)";
        $search_param = "%{$searchValue}%";
        array_push($params, $search_param, $search_param);
    }

    $where_sql = "";
    if (!empty($where_conditions)) {
        $where_sql = " WHERE " . implode(" AND ", $where_conditions);
    }

    // Fetch ALL matching data without LIMIT or OFFSET
    $data_sql = "SELECT 
                    s.id, s.profile_image, s.name, s.designation, s.contact, s.status,
                    c.contract_name, c.station_code, l.name as licensee_name "
                 . $base_sql . $where_sql . " ORDER BY s.name ASC";
    
    $data_stmt = $pdo->prepare($data_sql);
    $data_stmt->execute($params);
    $staff_list = $data_stmt->fetchAll();

    foreach ($staff_list as $key => $staff) {
        $image_data_url = null;
        if (!empty($staff['profile_image'])) {
            $image_path = __DIR__ . '/../../public/uploads/staff/' . $staff['profile_image'];
            
            if (file_exists($image_path)) {
                $image_type = mime_content_type($image_path);
                $image_content = file_get_contents($image_path);
                $base64_image = base64_encode($image_content);
                $image_data_url = "data:$image_type;base64,$base64_image";
            }
        }
        if ($image_data_url === null) {
            $image_data_url = $default_image_data_url;
        }
        // Add the base64 data (or null) to the array for each staff member
        $staff_list[$key]['image_data'] = $image_data_url;
    }

    // Return the full list
    echo json_encode(['success' => true, 'data' => $staff_list]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "error" => $e->getMessage(), "data" => []]);
}