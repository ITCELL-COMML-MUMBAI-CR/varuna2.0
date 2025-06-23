<?php
require_once __DIR__ . '/../../../src/init.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'ADMIN') { /* ... access denied ... */ }

$table = $_GET['table'] ?? '';
$id = $_GET['id'] ?? 0;

// Whitelist of editable tables and their primary key columns
$allowed_tables = [
    'varuna_contract_types' => 'ContractType',
    'varuna_staff_designation' => 'id',
    'varuna_users' => 'id',
    'varuna_licensee' => 'id', // <-- Add this line
    'contracts' => 'id'         // <-- Add this line
];

if (!array_key_exists($table, $allowed_tables)) {
    echo json_encode(['success' => false, 'message' => 'Invalid table specified.']);
    exit();
}

$id_column = $allowed_tables[$table];
$stmt = $pdo->prepare("SELECT * FROM `$table` WHERE `$id_column` = ?");
$stmt->execute([$id]);
$record = $stmt->fetch();

echo json_encode(['success' => !!$record, 'data' => $record]);