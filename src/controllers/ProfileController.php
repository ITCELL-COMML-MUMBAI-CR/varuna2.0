<?php
namespace Varuna\Controllers;

if (!defined('VARUNA_ENTRY_POINT')) {
    require_once __DIR__ . '/../errors/404.php';
    exit();
}

use PDO;

class ProfileController
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function handleRequest()
    {
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            validate_csrf_token($_POST['csrf_token'] ?? '');
            
            // Get the current request URI to determine the action
            $request_uri = trim($_GET['url'] ?? '', '/');
            
            if ($request_uri === 'profile/upload_signature') {
                $this->uploadSignature();
            } elseif ($request_uri === 'profile/delete_signature') {
                $this->deleteSignature();
            } else {
                $_SESSION['error_message'] = "Invalid action specified.";
            }
            
            header("Location: " . BASE_URL . "profile");
            exit();
        }
    }

    private function uploadSignature()
    {
        if (isset($_FILES['signature_file']) && $_FILES['signature_file']['error'] == UPLOAD_ERR_OK) {
            $user_id = $_SESSION['user_id'];
            $upload_dir = 'uploads/authority/';
            
            // Get file extension from uploaded file
            $file_ext = pathinfo($_FILES['signature_file']['name'], PATHINFO_EXTENSION);
            
            // Use filename with proper extension
            $signature_filename = "auth_sig_{$user_id}.{$file_ext}";
            
            // Process the uploaded image
            $new_filename = process_image_upload($_FILES['signature_file'], $upload_dir, $signature_filename);

            if (is_array($new_filename)) {
                $_SESSION['error_message'] = implode(', ', $new_filename);
            } else {
                // Store the complete filename with extension in the database
                $stmt = $this->pdo->prepare("
                    INSERT INTO varuna_authority_signatures (user_id, signature_path) 
                    VALUES (?, ?) 
                    ON DUPLICATE KEY UPDATE signature_path = ?
                ");
                $stmt->execute([$user_id, $new_filename, $new_filename]);
                $_SESSION['success_message'] = "Signature uploaded successfully!";
                // Update session with the new signature path so pages unlock immediately
                $_SESSION['signature_path'] = $new_filename;
            }
        } else {
            $_SESSION['error_message'] = "No file was uploaded or an error occurred.";
        }
    }

    private function deleteSignature()
    {
        $user_id = $_SESSION['user_id'];
        
        try {
            // First, get the current signature path to delete the file
            $stmt = $this->pdo->prepare("SELECT signature_path FROM varuna_authority_signatures WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $current_signature = $stmt->fetchColumn();
            
            if ($current_signature) {
                // Delete the physical file
                $file_path = __DIR__ . '/../../public/uploads/authority/' . $current_signature;
                if (file_exists($file_path)) {
                    unlink($file_path);
                }
                
                // Remove the record from database
                $delete_stmt = $this->pdo->prepare("DELETE FROM varuna_authority_signatures WHERE user_id = ?");
                $delete_stmt->execute([$user_id]);
                
                // Update session to remove signature path
                $_SESSION['signature_path'] = '';
                
                $_SESSION['success_message'] = "Signature deleted successfully!";
            } else {
                $_SESSION['error_message'] = "No signature found to delete.";
            }
        } catch (\Exception $e) {
            error_log("Error deleting signature for user {$user_id}: " . $e->getMessage());
            $_SESSION['error_message'] = "An error occurred while deleting the signature. Please try again.";
        }
    }

    public function changeOwnPassword($userId, $currentPassword, $newPassword)
    {
        try {
            // Get user from database
            $stmt = $this->pdo->prepare("SELECT password FROM varuna_users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();

            if (!$user) {
                return ['success' => false, 'message' => 'User not found.'];
            }

            // Verify current password
            if (!password_verify($currentPassword, $user['password'])) {
                return ['success' => false, 'message' => 'Incorrect current password.'];
            }

            // Hash new password
            $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);

            // Update password in the database
            $stmt = $this->pdo->prepare("UPDATE varuna_users SET password = ? WHERE id = ?");
            if ($stmt->execute([$newPasswordHash, $userId])) {
                return ['success' => true, 'message' => 'Password updated successfully.'];
            } else {
                return ['success' => false, 'message' => 'Failed to update password.'];
            }
        } catch (\Exception $e) {
            error_log("Password change error for user {$userId}: " . $e->getMessage());
            return ['success' => false, 'message' => 'An error occurred. Please try again.'];
        }
    }
}

global $pdo;
$profileController = new \Varuna\Controllers\ProfileController($pdo);
$profileController->handleRequest();