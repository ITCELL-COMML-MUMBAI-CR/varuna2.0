<?php
/**
 * API for Server-Side DataTables for the Staff List
 * Current Time: Monday, June 16, 2025 at 12:31 PM IST
 * Location: Kalyan, Maharashtra, India
 */
require_once __DIR__ . '/../../src/init.php';
require_once __DIR__ . '/../../config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) { http_response_code(403); exit(); }

// Parameters from DataTables
$draw = $_POST['draw'] ?? $_GET['draw'] ?? 1;
$start = $_POST['start'] ?? $_GET['start'] ?? 0;
$length = $_POST['length'] ?? $_GET['length'] ?? 10;
$searchValue = $_POST['search']['value'] ?? $_GET['search'] ?? '';
$contractId = $_POST['contract_id'] ?? $_GET['contract_id'] ?? 0;

if (!$contractId) {
    echo json_encode(["draw" => intval($draw), "recordsTotal" => 0, "recordsFiltered" => 0, "data" => []]);
    exit();
}

// Base WHERE clause
$whereClause = "contract_id = :contract_id";
$params = [':contract_id' => $contractId];

// For SCI role, add section filter
if ($_SESSION['role'] === 'SCI' && !empty($_SESSION['section'])) {
    $whereClause .= " AND EXISTS (SELECT 1 FROM contracts c WHERE c.id = varuna_staff.contract_id AND c.section_code = :section)";
    $params[':section'] = $_SESSION['section'];
}

// --- Total Records Query ---
$totalRecordsStmt = $pdo->prepare("SELECT COUNT(id) as total FROM varuna_staff WHERE " . $whereClause);
$totalRecordsStmt->execute($params);
$totalRecords = $totalRecordsStmt->fetchColumn();

// --- Build Search Query ---
if (!empty($searchValue)) {
    $whereClause .= " AND (name LIKE :search OR designation LIKE :search OR id LIKE :search OR adhar_card_number LIKE :search)";
    $params[':search'] = "%$searchValue%";
}

// --- Total Filtered Records Query ---
$totalFilteredQuery = "SELECT COUNT(id) as total FROM varuna_staff WHERE " . $whereClause;
$totalFilteredStmt = $pdo->prepare($totalFilteredQuery);
$totalFilteredStmt->execute($params);
$totalFiltered = $totalFilteredStmt->fetchColumn();

// --- Final Data Query with Ordering and Pagination ---
$dataQuery = "SELECT id, name, designation, contact, adhar_card_number, status FROM varuna_staff WHERE " . $whereClause . " ORDER BY name ASC LIMIT :start, :length";

// Get status counts
$statusCountQuery = "SELECT 
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_count,
    SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_count,
    SUM(CASE WHEN status = 'terminated' THEN 1 ELSE 0 END) as terminated_count
    FROM varuna_staff 
    WHERE " . $whereClause;
$statusCountStmt = $pdo->prepare($statusCountQuery);
foreach ($params as $key => $value) {
    if ($key !== ':start' && $key !== ':length') {
        $statusCountStmt->bindValue($key, $value);
    }
}
$statusCountStmt->execute();
$statusCounts = $statusCountStmt->fetch(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare($dataQuery);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue(':start', $start, PDO::PARAM_INT);
$stmt->bindValue(':length', $length, PDO::PARAM_INT);
$stmt->execute();
$staff_list = $stmt->fetchAll();

// Format data for DataTables
$data = [];
foreach ($staff_list as $staff) {
    $data[] = [
        "id" => '<a href="' . BASE_URL . 'staff_details.php?id=' . htmlspecialchars($staff['id']) . '" target="_blank" class="staff-id-link">' . htmlspecialchars($staff['id']) . '</a>',
        "name" => htmlspecialchars($staff['name']),
        "designation" => htmlspecialchars($staff['designation']),
        "contact" => htmlspecialchars($staff['contact']),
        "adhar_card_number" => htmlspecialchars($staff['adhar_card_number'] ?? 'N/A'),
        "status" => '<span class="status-' . strtolower(htmlspecialchars($staff['status'])) . '">' . htmlspecialchars($staff['status']) . '</span>'
    ];
}

$response = [
    "draw" => intval($draw),
    "recordsTotal" => intval($totalRecords),
    "recordsFiltered" => intval($totalFiltered),
    "data" => $data,
    "statusCounts" => $statusCounts
];

echo json_encode($response);