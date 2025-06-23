<?php
/**
 * API for Server-Side DataTables for the Staff List
 * Current Time: Monday, June 16, 2025 at 12:31 PM IST
 * Location: Kalyan, Maharashtra, India
 */
require_once __DIR__ . '/../../src/init.php';
require_once __DIR__ . '/../../config.php';
header('Content-Type: application/json');

// Parameters from DataTables
$draw = $_POST['draw'] ?? 1;
$start = $_POST['start'] ?? 0;
$length = $_POST['length'] ?? 10;
$searchValue = $_POST['search']['value'] ?? '';
$contractId = $_POST['contract_id'] ?? 0;

if (!$contractId) {
    echo json_encode(["draw" => intval($draw), "recordsTotal" => 0, "recordsFiltered" => 0, "data" => []]);
    exit();
}

// --- Total Records Query ---
// This counts all approved staff for the contract, before any filtering.
$totalRecordsStmt = $pdo->prepare("SELECT COUNT(id) as total FROM varuna_staff WHERE contract_id = ? AND status = 'approved'");
$totalRecordsStmt->execute([$contractId]);
$totalRecords = $totalRecordsStmt->fetchColumn();

// --- Build Filtered/Paginated Query ---
$baseQuery = " FROM varuna_staff WHERE contract_id = :contract_id AND status = 'approved'";
$searchQuery = "";

if (!empty($searchValue)) {
    $searchQuery = " AND (name LIKE :search OR designation LIKE :search OR id LIKE :search OR adhar_card_number LIKE :search)";
}

// --- Total Filtered Records Query ---
// This counts records that match the search term.
$totalFilteredQuery = "SELECT COUNT(id) as total" . $baseQuery . $searchQuery;
$totalFilteredStmt = $pdo->prepare($totalFilteredQuery);
$totalFilteredStmt->bindValue(':contract_id', $contractId, PDO::PARAM_INT);
if (!empty($searchValue)) {
    $totalFilteredStmt->bindValue(':search', "%$searchValue%");
}
$totalFilteredStmt->execute();
$totalFiltered = $totalFilteredStmt->fetchColumn();

// --- Final Data Query with Ordering and Pagination ---
$dataQuery = "SELECT id, name, designation, contact, adhar_card_number, status" . $baseQuery . $searchQuery . " ORDER BY name ASC LIMIT :start, :length";
$stmt = $pdo->prepare($dataQuery);
$stmt->bindValue(':contract_id', $contractId, PDO::PARAM_INT);
if (!empty($searchValue)) {
    $stmt->bindValue(':search', "%$searchValue%");
}
$stmt->bindValue(':start', $start, PDO::PARAM_INT);
$stmt->bindValue(':length', $length, PDO::PARAM_INT);
$stmt->execute();
$staff_list = $stmt->fetchAll();

// Format data for DataTables
$data = [];
foreach ($staff_list as $staff) {
    $data[] = [
        "id" => '<a href="#" class="staff-details-link" data-staff-id="' . htmlspecialchars($staff['id']) . '">' . htmlspecialchars($staff['id']) . '</a>',
        "name" => htmlspecialchars($staff['name']),
        "designation" => htmlspecialchars($staff['designation']),
        "contact" => htmlspecialchars($staff['contact']),
        "adhar_card_number" => htmlspecialchars($staff['adhar_card_number'] ?? 'N/A'),
        "status" => '<span class="status-' . htmlspecialchars($staff['status']) . '">' . htmlspecialchars($staff['status']) . '</span>'
    ];
}

$response = [
    "draw" => intval($draw),
    "recordsTotal" => intval($totalRecords),
    "recordsFiltered" => intval($totalFiltered),
    "data" => $data
];

echo json_encode($response);