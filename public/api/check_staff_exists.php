<?php
require_once __DIR__ . '/../../src/init.php';
require_once __DIR__ . '/../../config.php';
header('Content-Type: application/json');

$name = $_GET['name'] ?? '';
$adhar = $_GET['adhar'] ?? '';

if (empty($name) && empty($adhar)) {
    echo json_encode(['exists' => false]);
    exit();
}

if (!empty($name)) {
    $stmt = $pdo->prepare("SELECT id FROM varuna_staff WHERE name = ?");
    $stmt->execute([$name]);
} else {
    $stmt = $pdo->prepare("SELECT id FROM varuna_staff WHERE adhar_card_number = ?");
    $stmt->execute([$adhar]);
}

echo json_encode(['exists' => $stmt->fetch() ? true : false]);