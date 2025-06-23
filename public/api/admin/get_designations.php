<?php
require_once __DIR__ . '/../../../src/init.php';
// Add security checks for ADMIN role
$stmt = $pdo->query("SELECT id, designation_name FROM varuna_staff_designation");
echo json_encode(['data' => $stmt->fetchAll()]);