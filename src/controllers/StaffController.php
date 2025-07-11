<?php
/**
 * Controller for Staff Onboarding
 * Current Time: Tuesday, June 24, 2025 at 4:25 PM IST
 * Location: Kalyan, Maharashtra, India
 */

// Security: Deny direct file access
if (!defined('VARUNA_ENTRY_POINT')) {
    require_once __DIR__ . '/../views/errors/404.php';
    exit();
}

global $pdo;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 1. CSRF Token Validation
    validate_csrf_token($_POST['csrf_token'] ?? '');

    // 2. Store user input for PRG pattern (to repopulate form on error)
    $_SESSION['old_input'] = $_POST;

    // 3. Server-Side Validation
    $errors = [];
    $invalid_fields = [];
    $field_labels = [
        'contract_id' => 'Contract',
        'name' => 'Full Name',
        'designation' => 'Designation',
        'contact' => 'Contact Number'
    ];

    foreach ($field_labels as $field_name => $label) {
        if (empty($_POST[$field_name])) {
            $errors[] = $label;
            $invalid_fields[] = $field_name;
        }
    }

    if (empty($_FILES['profile_image']) || $_FILES['profile_image']['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'Profile Image';
        $invalid_fields[] = 'profile_image';
    }
    if (empty($_FILES['signature_image']) || $_FILES['signature_image']['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'Signature Image';
        $invalid_fields[] = 'signature_image';
    }

    if (!empty($errors)) {
        $_SESSION['error_message'] = 'Please fix the required fields: ' . implode(', ', $errors);
        $_SESSION['invalid_fields'] = $invalid_fields;
        header("Location: " . BASE_URL . "staff/add");
        exit();
    }

    // 4. Custom & Unique Staff ID Generation
    do {
        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $randomString = $characters[rand(0, 25)] . $characters[rand(0, 25)];
        $randomString2 = $characters[rand(0, 25)];
        $gen = rand(10, 99);
        $gen2 = rand(10, 99);
        $newStaffId = $randomString . $gen . $randomString2 . $gen2;

        $idCheckStmt = $pdo->prepare("SELECT id FROM varuna_staff WHERE id = ?");
        $idCheckStmt->execute([$newStaffId]);
        $idExists = $idCheckStmt->fetch();
    } while ($idExists);

    // 5. File Upload Processing
    $upload_dir = 'uploads/staff/';
    if (!is_dir($upload_dir)) { mkdir($upload_dir, 0755, true); }

    $uploaded_files = [];
    // =================== LOGIC UPDATED ===================
    // Added 'adhar_card' to the list of documents to process
    $doc_types = ['police', 'medical', 'ta', 'ppo', 'profile', 'signature', 'adhar_card'];
    // =====================================================

    foreach ($doc_types as $doc_type) {
        $field_name = $doc_type . '_image';
        if (isset($_FILES[$field_name]) && $_FILES[$field_name]['error'] == UPLOAD_ERR_OK) {
            // Use id_doctype.extension format (e.g., {ID}_profile.jpg)
            $file_doc_type = ($doc_type === 'adhar_card') ? 'adhar' : $doc_type;
            $newFileName = $newStaffId . '_' . $file_doc_type . '.' . pathinfo($_FILES[$field_name]['name'], PATHINFO_EXTENSION);
            
            $result = process_image_upload($_FILES[$field_name], $upload_dir, $newFileName);

            if (is_array($result)) {
                $_SESSION['error_message'] = 'File Upload Error: ' . implode(', ', $result);
                header("Location: " . BASE_URL . "staff/add");
                exit();
            }
            $uploaded_files[$field_name] = $result;
        }
    }

    // 6. Database Insertion
    try {
        // =================== SQL QUERY UPDATED ===================
        // Added `adhar_card_image` column and its placeholder
        $sql = "INSERT INTO varuna_staff 
                    (id, contract_id, name, designation, contact, adhar_card_number, adhar_card_image, status,
                     police_image, police_issue_date, police_expiry_date, 
                     medical_image, medical_issue_date, medical_expiry_date,
                     ta_image, ta_expiry_date, ppo_image, profile_image, signature_image) 
                VALUES 
                    (:id, :contract_id, :name, :designation, :contact, :adhar_card_number, :adhar_card_image, :status,
                     :police_image, :police_issue_date, :police_expiry_date,
                     :medical_image, :medical_issue_date, :medical_expiry_date,
                     :ta_image, :ta_expiry_date, :ppo_image, :profile_image, :signature_image)";
        // =========================================================
        
        $stmt = $pdo->prepare($sql);

        // =================== DATA ARRAY UPDATED ===================
        // Added the new adhar_card_image path to the insertion array
        $data_to_insert = [
            'id' => $newStaffId,
            'contract_id' => $_POST['contract_id'],
            'name' => $_POST['name'],
            'designation' => $_POST['designation'],
            'contact' => $_POST['contact'],
            'adhar_card_number' => !empty($_POST['adhar_card_number']) ? $_POST['adhar_card_number'] : null,
            'adhar_card_image' => $uploaded_files['adhar_card_image'] ?? null,
            'status' => 'pending',
            'police_image' => $uploaded_files['police_image'] ?? null,
            'police_issue_date' => !empty($_POST['police_issue_date']) ? $_POST['police_issue_date'] : null,
            'police_expiry_date' => !empty($_POST['police_expiry_date']) ? $_POST['police_expiry_date'] : null,
            'medical_image' => $uploaded_files['medical_image'] ?? null,
            'medical_issue_date' => !empty($_POST['medical_issue_date']) ? $_POST['medical_issue_date'] : null,
            'medical_expiry_date' => !empty($_POST['medical_expiry_date']) ? $_POST['medical_expiry_date'] : null,
            'ta_image' => $uploaded_files['ta_image'] ?? null,
            'ta_expiry_date' => !empty($_POST['ta_expiry_date']) ? $_POST['ta_expiry_date'] : null,
            'ppo_image' => $uploaded_files['ppo_image'] ?? null,
            'profile_image' => $uploaded_files['profile_image'] ?? null,
            'signature_image' => $uploaded_files['signature_image'] ?? null
        ];
        // ==========================================================
        
        $stmt->execute($data_to_insert);

        // 7. Success Logging and Redirect
        $logData = ['details' => "Added new staff '{$_POST['name']}' with generated ID: $newStaffId"];
        log_activity($pdo, 'STAFF_ADD_SUCCESS', $logData);

        unset($_SESSION['old_input']);
        $_SESSION['success_message'] = "Staff member '{$_POST['name']}' added successfully!";
        header("Location: " . BASE_URL . "staff/add");
        exit();

    } catch (PDOException $e) {
        // 8. Error Logging and Redirect
        $logData = ['details' => "Database failure while adding staff '{$_POST['name']}'. Error: " . $e->getMessage()];
        log_activity($pdo, 'STAFF_ADD_FAIL', $logData);

        $_SESSION['error_message'] = "A critical database error occurred. Please try again.";
        header("Location: " . BASE_URL . "staff/add");
        exit();
    }
}
?>
