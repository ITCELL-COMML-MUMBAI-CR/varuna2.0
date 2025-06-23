<?php
require_once __DIR__ . '/../../src/init.php';
// This API doesn't need the full router, just the config for DB access
require_once __DIR__ . '/../../config.php';

header('Content-Type: application/json');

$sectionCode = $_GET['section'] ?? '';

if (empty($sectionCode)) {
    echo json_encode(['success' => false, 'message' => 'Section code is required.']);
    exit();
}

try {
    $stmt = $pdo->prepare("SELECT Code, Name FROM Station WHERE Section_Code = :section_code ORDER BY Name ASC");
    $stmt->bindParam(':section_code', $sectionCode);
    $stmt->execute();
    $stations = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'stations' => $stations]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error.']);
}