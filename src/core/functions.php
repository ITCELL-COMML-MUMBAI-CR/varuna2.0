<?php
/**
 * Logs an activity into the database.
 * Now automatically falls back to session data for user details.
 * @param PDO $pdo The database connection object.
 * @param string $action The action performed (e.g., 'LOGIN_SUCCESS').
 * @param array $logData Associative array with log data (user_id, username, details).
 */
function log_activity($pdo, $action, $logData = []) {
    $sql = "INSERT INTO varuna_activity_log (user_id, username, action, details, ip_address) VALUES (:user_id, :username, :action, :details, :ip_address)";
    
    $stmt = $pdo->prepare($sql);

    // --- NEW ROBUST LOGIC ---
    // If user_id/username are not passed in $logData, try to get them directly from the session.
    $userId   = $logData['user_id'] ?? $_SESSION['user_id'] ?? null;
    $username = $logData['username'] ?? $_SESSION['username'] ?? null;
    // --- END OF NEW LOGIC ---
    
    $details = $logData['details'] ?? '';
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

    $stmt->bindParam(':user_id', $userId);
    $stmt->bindParam(':username', $username);
    $stmt->bindParam(':action', $action);
    $stmt->bindParam(':details', $details);
    $stmt->bindParam(':ip_address', $ip_address);
    
    $stmt->execute();
}


/**
 * Processes an uploaded image file. Validates, compresses, and moves it.
 *
 * @param array $file The file array from $_FILES.
 * @param string $uploadDir The directory to save the file.
 * @param string $newFileName The desired new filename for the uploaded file (without extension).
 * @return string|array Returns the final filename (with extension) on success, or an array of errors on failure.
 */
function process_image_upload($file, $uploadDir = 'uploads/', $newFileName = '') {
    $errors = [];
    if (empty($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return null; 
    }

    $fileName = $file['name'];
    $fileTmpName = $file['tmp_name'];
    $fileSize = $file['size'];
    $fileType = $file['type'];

    // 1. Validate file type
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($fileType, $allowedTypes)) {
        $errors[] = "Invalid file type: '$fileName'. Only JPG, PNG, GIF, and WEBP are allowed.";
    }

    // 2. Validate file size
    $maxUploadSize = 5 * 1024 * 1024; // 5 MB
    if ($fileSize > $maxUploadSize) {
        $errors[] = "File is too large: '$fileName'. Maximum upload size is 5MB.";
    }

    if (!empty($errors)) {
        return $errors;
    }

    // Use the provided new filename or create a unique one as a fallback
    $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);
    $finalFileName = !empty($newFileName) ? $newFileName : uniqid('', true) . '.' . $fileExtension;
    
    $destination = rtrim($uploadDir, '/\\') . DIRECTORY_SEPARATOR . $finalFileName;

    // Remove existing file to avoid move_uploaded_file failures on some systems (especially Windows)
    if (file_exists($destination)) {
        @unlink($destination);
    }

    // 3. Compress image if needed
    $compressionTargetSize = 2 * 1024 * 1024; // 2 MB
    if ($fileSize > $compressionTargetSize) {
        $image = false; // Initialize image variable
        switch ($fileType) {
            case 'image/jpeg':
                $image = @imagecreatefromjpeg($fileTmpName);
                if ($image) {
                    imagejpeg($image, $destination, 75);
                }
                break;
            case 'image/png':
                $image = @imagecreatefrompng($fileTmpName);
                if ($image) {
                    imagepng($image, $destination, 6); // Compression level 0-9
                }
                break;
            case 'image/gif':
                $image = @imagecreatefromgif($fileTmpName);
                if ($image) {
                    imagegif($image, $destination);
                }
                break;
            case 'image/webp':
                $image = @imagecreatefromwebp($fileTmpName);
                if ($image) {
                    imagewebp($image, $destination, 80);
                }
                break;
        }

        if ($image) {
            imagedestroy($image);
        } else {
            // Fallback to simple move if image creation fails or type not handled
            move_uploaded_file($fileTmpName, $destination);
        }
    } else {
        move_uploaded_file($fileTmpName, $destination);
    }

    return $finalFileName;
}


/**
 * Fetches an array of approved staff IDs based on a filter.
 * This function is reusable and secure for bulk printing.
 *
 * @param PDO $pdo The database connection object.
 * @param string $filter_by The filter type (e.g., 'licensee', 'contract').
 * @param string $filter_value The ID or code to filter by.
 * @return array An array of staff IDs.
 */
function getStaffIdsForBulkPrint($pdo, $filter_by, $filter_value) {
    if (empty($filter_by) || empty($filter_value)) {
        return [];
    }

    $sql = "";
    $params = [];
    $base_query = "SELECT s.id FROM varuna_staff s JOIN contracts c ON s.contract_id = c.id WHERE s.status = 'approved'";

    switch ($filter_by) {
        case 'licensee':
            $sql = $base_query . " AND c.licensee_id = ?";
            $params = [$filter_value];
            break;
        case 'contract':
            $sql = $base_query . " AND c.id = ?";
            $params = [$filter_value];
            break;
        case 'station':
            $sql = $base_query . " AND c.station_code = ?";
            $params = [$filter_value];
            break;
        case 'section':
            $sql = $base_query . " AND c.section_code = ?";
            $params = [$filter_value];
            break;
        default:
            return []; // Return empty array for invalid filter
    }

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) {
        error_log("Bulk Print Function Error: " . $e->getMessage());
        return []; // Return empty on error
    }
}

/**
 * Fetches all necessary data to render a staff ID card.
 *
 * @param PDO $pdo The database connection object.
 * @param string $staff_id The ID of the staff member.
 * @return array|null An associative array of card data or null if not found.
 */
function get_staff_card_data($pdo, $staff_id) {
    if (empty($staff_id)) {
        return null;
    }

    try {
        $stmt = $pdo->prepare("
            SELECT s.*, c.contract_name, c.contract_type, c.section_code, c.station_code, l.name as licensee_name
            FROM varuna_staff s
            LEFT JOIN contracts c ON s.contract_id = c.id
            LEFT JOIN varuna_licensee l ON c.licensee_id = l.id
            WHERE s.id = ? AND s.status = 'approved'
        ");
        $stmt->execute([$staff_id]);
        $staff = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$staff) {
            error_log("Staff not found or not approved for ID: $staff_id");
            return null;
        }
        
        $approver_id = $staff['approved_by'];
        
        $auth_sig_path = '';
        if ($approver_id) {
            $sig_stmt = $pdo->prepare("SELECT signature_path FROM varuna_authority_signatures WHERE user_id = ?");
            $sig_stmt->execute([$approver_id]);
            $path_from_db = $sig_stmt->fetchColumn();
            
            if ($path_from_db && file_exists(PROJECT_ROOT . '/public/uploads/authority/' . $path_from_db)) {
                $auth_sig_path = $path_from_db;
            } else {
                error_log("Signature file not found for approver ID: $approver_id, Path: $path_from_db");
            }
        }

        $style_stmt = $pdo->prepare("SELECT * FROM varuna_id_styles WHERE contract_type = ?");
        $style_stmt->execute([$staff['contract_type']]);
        $styles = $style_stmt->fetch(PDO::FETCH_ASSOC);

        return [
            'staff' => $staff,
            'auth_sig_path' => $auth_sig_path,
            'styles' => $styles,
        ];

    } catch (Exception $e) {
        error_log("ID Card Data Fetch Error for staff_id {$staff_id}: " . $e->getMessage());
        return null;
    }
}