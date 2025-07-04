<?php
/**
 * Portal API: Adds a new staff member to a contract.
 */
require_once __DIR__ . '/../../../src/init.php';
header('Content-Type: application/json');

try {
    // 1. Security check for portal session and POST method
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['is_licensee_portal'], $_SESSION['licensee_id'])) {
        throw new Exception('Access Denied.', 403);
    }
    
    validate_csrf_token($_POST['csrf_token'] ?? '');
    
    $licensee_id = $_SESSION['licensee_id'];
    $contract_id = $_POST['contract_id'] ?? 0;

    // 2. CRITICAL Security Check: Ensure the submitted contract_id belongs to the licensee in the session.
    $stmt = $pdo->prepare("SELECT id FROM contracts WHERE id = ? AND licensee_id = ?");
    $stmt->execute([$contract_id, $licensee_id]);
    if ($stmt->fetch() === false) {
        throw new Exception('Invalid contract specified.', 403);
    }

    // 3. Generate a unique staff ID
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
    
    // 4. File Upload Processing (using the existing helper function)
    $upload_dir = __DIR__ . '/../../../public/uploads/staff/';
    $uploaded_files = [];
    $doc_types = ['police', 'medical', 'ta', 'ppo', 'profile', 'signature', 'adhar_card'];

    foreach ($doc_types as $doc_type) {
        $field_name = $doc_type . '_image';
        if (isset($_FILES[$field_name]) && $_FILES[$field_name]['error'] == UPLOAD_ERR_OK) {
            $newFileName = $newStaffId . '_' . $doc_type . '.' . pathinfo($_FILES[$field_name]['name'], PATHINFO_EXTENSION);
            
            $result = process_image_upload($_FILES[$field_name], $upload_dir, $newFileName);
            if (is_array($result)) { throw new Exception('File Upload Error: ' . implode(', ', $result)); }
            $uploaded_files[$field_name] = $result;
        }
    }

    // 5. Database Insertion
    $sql = "INSERT INTO varuna_staff 
                (id, contract_id, name, designation, contact, adhar_card_number, adhar_card_image, status,
                 police_image, police_issue_date, police_expiry_date, medical_image, medical_issue_date, medical_expiry_date,
                 ta_image, ta_expiry_date, ppo_image, profile_image, signature_image) 
            VALUES (:id, :contract_id, :name, :designation, :contact, :adhar_card_number, :adhar_card_image, 'pending',
                     :police_image, :police_issue_date, :police_expiry_date, :medical_image, :medical_issue_date, :medical_expiry_date,
                     :ta_image, :ta_expiry_date, :ppo_image, :profile_image, :signature_image)";
    
    $stmt = $pdo->prepare($sql);
    $data = [
        'id' => $newStaffId, 'contract_id' => $contract_id, 'name' => $_POST['name'],
        'designation' => $_POST['designation'], 'contact' => $_POST['contact'],
        'adhar_card_number' => !empty($_POST['adhar_card_number']) ? $_POST['adhar_card_number'] : null,
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
        'signature_image' => $uploaded_files['signature_image'] ?? null,
        'adhar_card_image' => $uploaded_files['adhar_card_image'] ?? null
    ];
    $stmt->execute($data);

    log_activity($pdo, 'PORTAL_STAFF_ADD', ['details' => "Licensee {$_SESSION['licensee_name']} added staff {$data['name']} to contract $contract_id"]);
    
    echo json_encode(['success' => true, 'message' => 'Staff added successfully! The application has been sent for approval.', 'new_csrf_token' => generate_csrf_token(), 'UploadedFiles' => $uploaded_files]);

} catch (Exception $e) {
    http_response_code($e->getCode() > 0 ? $e->getCode() : 500);
    echo json_encode(['success' => false, 'message' => $e->getMessage(), 'new_csrf_token' => generate_csrf_token()]);
}