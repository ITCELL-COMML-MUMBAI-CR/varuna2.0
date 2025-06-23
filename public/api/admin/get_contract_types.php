<?php
require_once __DIR__ . '/../../../src/init.php';
// Add security checks for ADMIN role
$stmt = $pdo->query("SELECT ContractType, TrainStation, Section FROM varuna_contract_types");
echo json_encode(['data' => $stmt->fetchAll()]);