<?php
require_once __DIR__ . '/../../../src/init.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['user_id']) || $_SESSION['role'] !== 'ADMIN') { /* ... */ }
validate_csrf_token($_POST['csrf_token'] ?? '');

$table = $_POST['table_name'] ?? '';
$id = $_POST['id_value'] ?? 0;
$id_column = $_POST['id_column'] ?? 'id';

$allowed_tables = ['varuna_contract_types', 'varuna_staff_designation', 'varuna_users', 'varuna_licensee', 'contracts'];

if (!in_array($table, $allowed_tables)) {
     echo json_encode(['success' => false, 'message' => 'Invalid operation.']);
     exit();
}

try {
    $stmt = $pdo->prepare("DELETE FROM `$table` WHERE `$id_column` = ?");
    $stmt->execute([$id]);
    
    log_activity($pdo, 'ADMIN_DELETE_RECORD', ['details' => "Deleted record with ID $id from table $table."]);
    echo json_encode(['success' => true, 'message' => 'Record deleted successfully!', 'new_csrf_token' => generate_csrf_token()]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Could not delete record. It may be in use by other parts of the system.']);
}