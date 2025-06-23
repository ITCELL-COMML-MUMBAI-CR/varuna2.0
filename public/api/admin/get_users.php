<?php
require_once __DIR__ . '/../../../src/init.php';
// Add security checks for IT CELL role
$stmt = $pdo->query("SELECT id, username, role, section, department_section FROM varuna_users");
echo json_encode(['data' => $stmt->fetchAll()]);