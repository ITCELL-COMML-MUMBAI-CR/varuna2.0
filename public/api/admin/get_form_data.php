<?php
require_once __DIR__ . '/../../../src/init.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'ADMIN') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}
$sections = $pdo->query("SELECT `name` FROM `Section` ORDER BY `name` ASC")->fetchAll(PDO::FETCH_COLUMN);
$department_sections = $pdo->query("SELECT DISTINCT `Section` FROM `varuna_contract_types` ORDER BY `Section` ASC")->fetchAll(PDO::FETCH_COLUMN);

echo json_encode([
    'success' => true,
    'sections' => $sections,
    'department_sections' => $department_sections
]);