<?php
require_once __DIR__ . '/../../src/init.php';
require_once __DIR__ . '/../../config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false]);
    exit();
}

$designations = $pdo->query("SELECT designation_name FROM varuna_staff_designation ORDER BY designation_name ASC")->fetchAll(PDO::FETCH_COLUMN);

echo json_encode(['success' => true, 'designations' => $designations]);