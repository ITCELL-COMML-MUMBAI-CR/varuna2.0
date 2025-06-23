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
    
    $destination = $uploadDir . $finalFileName;

    // 3. Compress image if needed
    $compressionTargetSize = 2 * 1024 * 1024; // 2 MB
    if ($fileSize > $compressionTargetSize) {
        switch ($fileType) {
            case 'image/jpeg': $image = imagecreatefromjpeg($fileTmpName); break;
            case 'image/png': $image = imagecreatefrompng($fileTmpName); break;
            case 'image/gif': $image = imagecreatefromgif($fileTmpName); break;
            case 'image/webp': $image = imagecreatefromwebp($fileTmpName); break;
            default: $image = false;
        }
        if ($image) {
            imagejpeg($image, $destination, 75);
            imagedestroy($image);
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