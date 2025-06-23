<?php
if (!defined('VARUNA_ENTRY_POINT')) { 
    require_once __DIR__ . '/errors/404.php';
    exit();
 }
global $pdo;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    validate_csrf_token($_POST['csrf_token'] ?? '');

    if (isset($_FILES['signature_file']) && $_FILES['signature_file']['error'] == UPLOAD_ERR_OK) {
        $user_id = $_SESSION['user_id'];
        $upload_dir = 'uploads/authority/';
        
        // Use our global function to process the image
        $new_filename = process_image_upload($_FILES['signature_file'], $upload_dir, "auth_sig_{$user_id}");

        if (is_array($new_filename)) {
            $_SESSION['error_message'] = implode(', ', $new_filename);
        } else {
            // Use INSERT ... ON DUPLICATE KEY UPDATE to handle both new and existing users
            $stmt = $pdo->prepare("
                INSERT INTO varuna_authority_signatures (user_id, signature_path) 
                VALUES (?, ?) 
                ON DUPLICATE KEY UPDATE signature_path = ?
            ");
            $stmt->execute([$user_id, $new_filename, $new_filename]);
            $_SESSION['success_message'] = "Signature uploaded successfully!";
        }
    } else {
        $_SESSION['error_message'] = "No file was uploaded or an error occurred.";
    }
    header("Location: " . BASE_URL . "profile");
    exit();
}