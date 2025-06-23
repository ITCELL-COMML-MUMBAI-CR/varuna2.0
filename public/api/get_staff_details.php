<?php
require_once __DIR__ . '/../../src/init.php';
require_once __DIR__ . '/../../config.php';
header('Content-Type: application/json');

$staff_id = $_GET['id'] ?? '';

if (empty($staff_id) || !isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false]);
    exit();
}

$stmt = $pdo->prepare("SELECT * FROM varuna_staff WHERE id = ?");
$stmt->execute([$staff_id]);
$staff = $stmt->fetch();

echo json_encode(['success' => !!$staff, 'staff' => $staff]);