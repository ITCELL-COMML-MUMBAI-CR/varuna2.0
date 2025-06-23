<?php
require_once __DIR__ . '/../../src/init.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) { http_response_code(403); exit(); }

// No filter needed here as all users can see all licensees
$stmt = $pdo->query("SELECT id, name, mobile_number, status FROM varuna_licensee");
echo json_encode(['data' => $stmt->fetchAll()]);