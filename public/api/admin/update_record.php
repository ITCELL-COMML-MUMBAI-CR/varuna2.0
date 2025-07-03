<?php
/**
 * VARUNA System - API to Update an Existing Record
 * Current Time: Thursday, June 19, 2025 at 12:05 PM IST
 * Location: Kalyan, Maharashtra, India
 */
require_once __DIR__ . '/../../../src/init.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['ADMIN','SCI'])) {
    http_response_code(403);
    echo json_encode(['success'=>false,'message'=>'Access Denied.','new_csrf_token'=>generate_csrf_token()]);
    exit();
}
validate_csrf_token($_POST['csrf_token'] ?? '');

$table = $_POST['table_name'] ?? '';
$id = $_POST['id_value'] ?? '';
$id_column = $_POST['id_column'] ?? '';

// Whitelist of all tables and their specific editable columns
$allowed_tables = [
    'varuna_staff_designation' => ['designation_name'],
    'varuna_users' => ['username', 'role', 'section', 'department_section'],
    'varuna_contract_types' => [
        'TrainStation', 'Section', 'Police', 'Medical', 'TA', 'PPO', 
        'FSSAI', 'FireSafety', 'PestControl', 'RailNeerAvailability', 'WaterSafety'
    ],
    'varuna_licensee' => ['name', 'mobile_number', 'status'],
    'contracts' => [
        'contract_name', 'location', 'stalls', 'license_fee', 'period', 'status'
    ]
];

if (!array_key_exists($table, $allowed_tables) || empty($id)) {
    echo json_encode(['success' => false, 'message' => 'Invalid update request.', 'new_csrf_token' => generate_csrf_token()]);
    exit();
}

try {
    $sql_parts = [];
    $params = [];
    // Build the "SET" part of the SQL query dynamically and safely
    foreach ($allowed_tables[$table] as $column) {
        if (isset($_POST[$column])) {
            $sql_parts[] = "`$column` = ?";
            $params[] = $_POST[$column];
        }
    }

    if (empty($sql_parts)) { throw new Exception("No valid fields to update."); }

    $params[] = $id; // Add the ID for the WHERE clause at the end
    $sql = "UPDATE `$table` SET " . implode(', ', $sql_parts) . " WHERE `$id_column` = ?";
    
    // If we are updating a licensee and changing its status, we may need to cascade the change.
    $is_licensee_status_update = ($table === 'varuna_licensee' && isset($_POST['status']));

    if ($is_licensee_status_update) { $pdo->beginTransaction(); }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    // --- Cascade status changes for licensees ---
    if ($is_licensee_status_update) {
        $new_status = strtolower(trim($_POST['status']));
        $licensee_id = $id;

        if ($new_status === 'regular') {
            // 1) Set all contracts to Regular
            $contract_stmt = $pdo->prepare("UPDATE contracts SET status = 'Regular' WHERE licensee_id = ?");
            $contract_stmt->execute([$licensee_id]);

            // 2) Set all staff under those contracts to Pending
            $staff_stmt = $pdo->prepare("UPDATE varuna_staff s JOIN contracts c ON s.contract_id = c.id SET s.status = 'Pending' WHERE c.licensee_id = ?");
            $staff_stmt->execute([$licensee_id]);
        } elseif ($new_status === 'terminated') {
            // 1) Terminate contracts
            $contract_stmt = $pdo->prepare("UPDATE contracts SET status = 'Terminated' WHERE licensee_id = ?");
            $contract_stmt->execute([$licensee_id]);

            // 2) Terminate staff
            $staff_stmt = $pdo->prepare("UPDATE varuna_staff s JOIN contracts c ON s.contract_id = c.id SET s.status = 'Terminated' WHERE c.licensee_id = ?");
            $staff_stmt->execute([$licensee_id]);

            // 3) Deactivate any active portal tokens
            $token_stmt = $pdo->prepare("UPDATE varuna_access_tokens SET is_active = 0 WHERE licensee_id = ?");
            $token_stmt->execute([$licensee_id]);
        }

        $pdo->commit();
    }

    log_activity($pdo, 'ADMIN_UPDATE_RECORD', ['details' => "Updated record with ID '$id' in table '$table'."]);
    echo json_encode(['success' => true, 'message' => 'Record updated successfully!', 'new_csrf_token' => generate_csrf_token()]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    log_activity($pdo, 'ADMIN_UPDATE_FAIL', ['details' => $e->getMessage()]);
    echo json_encode(['success' => false, 'message' => 'An error occurred during the update.', 'new_csrf_token' => generate_csrf_token()]);
}