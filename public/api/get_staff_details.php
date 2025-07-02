<?php
require_once __DIR__ . '/../../src/init.php';
header('Content-Type: application/json');

$staff_id = $_GET['staff_id'] ?? '';

if (empty($staff_id)) {
    echo json_encode(['success' => false, 'message' => 'Staff ID is required.']);
    exit();
}
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not authenticated.']);
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
    // Basic date formatting, ensure these fields exist in your table
    $date_fields = ['police_issue_date', 'police_expiry_date', 'medical_issue_date', 'medical_expiry_date', 'ta_expiry_date', 'dob'];
    foreach ($date_fields as $field) {
        if (!empty($staff[$field]) && $staff[$field] !== '0000-00-00') {
            $staff[$field] = date('d-m-Y', strtotime($staff[$field]));
        } else {
            $staff[$field] = 'N/A'; // Or null, or empty string
        }
    }
    
    echo json_encode(['success' => true, 'staff' => $staff]);
} else {
    echo json_encode(['success' => false, 'message' => 'Staff not found.']);
}