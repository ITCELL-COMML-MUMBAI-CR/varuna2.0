<?php
require_once __DIR__ . '/../../../src/init.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'ADMIN') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

// Get all users with their sections and department sections
$users = $pdo->query("SELECT id, username, role, section, department_section FROM varuna_users ORDER BY username ASC")->fetchAll(PDO::FETCH_ASSOC);

// Get all unique sections currently in use
$sections = $pdo->query("SELECT `name` FROM `Section` ORDER BY `name` ASC")->fetchAll(PDO::FETCH_COLUMN);

// Get all unique department sections currently in use
$department_sections = $pdo->query("SELECT DISTINCT `Section` FROM `varuna_contract_types` ORDER BY `Section` ASC")->fetchAll(PDO::FETCH_COLUMN);

echo json_encode([
    'success' => true,
    'users' => $users,
    'sections' => $sections,
    'department_sections' => $department_sections
]);