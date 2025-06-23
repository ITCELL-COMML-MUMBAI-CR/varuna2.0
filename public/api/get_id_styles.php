<?php
/**
 * API to get the saved ID card styles for a given contract type.
 * Current Time: Tuesday, June 17, 2025 at 1:40 PM IST
 * Location: Kalyan, Maharashtra, India
 */

require_once __DIR__ . '/../../src/init.php';
header('Content-Type: application/json');

$response = ['success' => false, 'styles' => null];
$contract_type = $_GET['contract_type'] ?? '';

if (empty($contract_type) || !isset($_SESSION['user_id']) || $_SESSION['role'] !== 'ADMIN') {
    echo json_encode($response);
    exit();
}

try {
    $stmt = $pdo->prepare("SELECT * FROM varuna_id_styles WHERE contract_type = ?");
    $stmt->execute([$contract_type]);
    $styles = $stmt->fetch();
    if ($styles) {
        $response = ['success' => true, 'styles' => $styles];
    } else {
        // Return a default style set if none exists
        $response = ['success' => true, 'styles' => [
            'bg_color' => '#FFE4C4', 'vendor_name_color' => '#00BFFF', 'station_train_color' => '#f52c2c',
            'nav_logo_bg_color' => '#4682B4', 'nav_logo_font_color' => '#FFFFFF', 'licensee_name_color' => '#CF5C36',
            'instructions_color' => '#CF5C36', 'default_font_color' => '#000000', 'border_color' => '#00BFFF'
        ]];
    }
} catch (PDOException $e) {
    $response['message'] = 'Database error.';
}

echo json_encode($response);