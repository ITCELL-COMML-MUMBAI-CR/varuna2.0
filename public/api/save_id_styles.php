<?php
/**
 * VARUNA System - API to Save ID Card Styles
 * Current Time: Wednesday, June 18, 2025 at 1:35 PM IST
 * Location: Kalyan, Maharashtra, India
 */

// 1. Initialize the application environment
require_once __DIR__ . '/../../src/init.php';
header('Content-Type: application/json');

// 2. Security Checks: Ensure the request is a POST and the user is an Admin
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['user_id']) || $_SESSION['role'] !== 'ADMIN') {
    http_response_code(403); // Forbidden
    echo json_encode(['success' => false, 'message' => 'Access Denied.']);
    exit();
}

// 3. CSRF Token Validation
try {
    validate_csrf_token($_POST['csrf_token'] ?? '');
} catch (Exception $e) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    exit();
}

// 4. Data Validation: Check if all required fields are present
$required_fields = [
    'contract_type', 'bg_color', 'vendor_name_color', 'station_train_color',
    'nav_logo_bg_color', 'nav_logo_font_color', 'licensee_name_color',
    'instructions_color', 'default_font_color', 'border_color'
];

foreach ($required_fields as $field) {
    if (empty($_POST[$field])) {
        http_response_code(400); // Bad Request
        echo json_encode(['success' => false, 'message' => "Missing required field: $field"]);
        exit();
    }
}

// 5. Database Operation
try {
    // This SQL command will INSERT a new row if the contract_type doesn't exist,
    // or UPDATE the existing row if it does. It's safe and efficient.
    $sql = "INSERT INTO varuna_id_styles 
                (contract_type, bg_color, vendor_name_color, station_train_color, nav_logo_bg_color, nav_logo_font_color, licensee_name_color, instructions_color, default_font_color, border_color)
            VALUES 
                (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                bg_color = VALUES(bg_color), 
                vendor_name_color = VALUES(vendor_name_color), 
                station_train_color = VALUES(station_train_color), 
                nav_logo_bg_color = VALUES(nav_logo_bg_color),
                nav_logo_font_color = VALUES(nav_logo_font_color), 
                licensee_name_color = VALUES(licensee_name_color), 
                instructions_color = VALUES(instructions_color), 
                default_font_color = VALUES(default_font_color), 
                border_color = VALUES(border_color)";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $_POST['contract_type'],
        $_POST['bg_color'],
        $_POST['vendor_name_color'],
        $_POST['station_train_color'],
        $_POST['nav_logo_bg_color'],
        $_POST['nav_logo_font_color'],
        $_POST['licensee_name_color'],
        $_POST['instructions_color'],
        $_POST['default_font_color'],
        $_POST['border_color']
    ]);

    // Log the successful activity
    log_activity($pdo, 'ID_STYLE_UPDATE', [
        'details' => "Updated styles for contract type: {$_POST['contract_type']}"
    ]);

    // Send a success response
    echo json_encode([
        'success' => true,
        'message' => 'Style saved successfully!',
        'new_csrf_token' => generate_csrf_token() // Send a new token for the next action
    ]);

} catch (PDOException $e) {
    // This is the complete catch block
    // Log the detailed error for the administrator
    log_activity($pdo, 'ID_STYLE_FAIL', ['details' => 'Database error: ' . $e->getMessage()]);
    
    // Send a generic, user-friendly error message back to the frontend
    http_response_code(500); // Internal Server Error
    echo json_encode(['success' => false, 'message' => 'A database error occurred while saving the style.']);
}