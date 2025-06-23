<?php
/**
 * VARUNA System - API to fetch approved staff IDs for bulk printing.
 * Current Time: Thursday, June 19, 2025 at 2:55 PM IST
 * Location: Kalyan, Maharashtra, India
 */
require_once __DIR__ . '/../../src/init.php';
header('Content-Type: application/json');

// This feature is visible to all, so we only check if the user is logged in.
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access Denied.']);
    exit();
}

$filter_by = $_GET['filter_by'] ?? '';
$filter_value = $_GET['filter_value'] ?? '';

if (empty($filter_by) || empty($filter_value)) {
    echo json_encode(['success' => false, 'message' => 'Filter type and value are required.']);
    exit();
}

// Base query to select approved staff IDs
$sql = "SELECT s.id FROM varuna_staff s LEFT JOIN contracts c ON s.contract_id = c.id WHERE s.status = 'approved'";
$params = [];

// Dynamically add the correct filter condition
switch ($filter_by) {
    case 'licensee':
        $sql .= " AND c.licensee_id = ?";
        $params[] = $filter_value;
        break;
    case 'contract':
        $sql .= " AND c.id = ?";
        $params[] = $filter_value;
        break;
    case 'station':
        $sql .= " AND c.station_code LIKE ?";
        $params[] = "%$filter_value%";
        break;
    case 'section':
        $sql .= " AND c.section_code = ?";
        $params[] = $filter_value;
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid filter type.']);
        exit();
}

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    // Fetch all staff IDs into a simple array
    $staff_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

    echo json_encode(['success' => true, 'staff_ids' => $staff_ids]);

} catch (PDOException $e) {
    error_log("Bulk Print API Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'A database error occurred while fetching staff list.']);
}