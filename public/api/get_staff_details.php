<?php
require_once __DIR__ . '/../../src/init.php';
require_once __DIR__ . '/../../config.php';
header('Content-Type: application/json');

$staff_id = $_GET['staff_id'] ?? '';

if (empty($staff_id) || !isset($_SESSION['user_id'])) {
    echo json_encode([
        'draw' => 1,
        'recordsTotal' => 0,
        'recordsFiltered' => 0,
        'data' => []
    ]);
    exit();
}

$stmt = $pdo->prepare("
    SELECT 
        s.*,
        c.contract_name,
        c.contract_type,
        c.station_code,
        l.name as licensee_name,
        l.mobile_number as licensee_mobile
    FROM varuna_staff s
    INNER JOIN contracts c ON s.contract_id = c.id
    INNER JOIN varuna_licensee l ON c.licensee_id = l.id
    WHERE s.id = ?
");
$stmt->execute([$staff_id]);
$staff = $stmt->fetch(PDO::FETCH_ASSOC);

if ($staff) {
    // Format dates for display
    $staff['police_issue_date'] = $staff['police_issue_date'] ? date('Y-m-d', strtotime($staff['police_issue_date'])) : null;
    $staff['police_expiry_date'] = $staff['police_expiry_date'] ? date('Y-m-d', strtotime($staff['police_expiry_date'])) : null;
    $staff['medical_issue_date'] = $staff['medical_issue_date'] ? date('Y-m-d', strtotime($staff['medical_issue_date'])) : null;
    $staff['medical_expiry_date'] = $staff['medical_expiry_date'] ? date('Y-m-d', strtotime($staff['medical_expiry_date'])) : null;
    $staff['ta_expiry_date'] = $staff['ta_expiry_date'] ? date('Y-m-d', strtotime($staff['ta_expiry_date'])) : null;
}

echo json_encode([
    'draw' => 1,
    'recordsTotal' => $staff ? 1 : 0,
    'recordsFiltered' => $staff ? 1 : 0,
    'data' => $staff ? [$staff] : []
]);