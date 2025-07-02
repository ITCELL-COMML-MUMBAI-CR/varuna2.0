<?php
/**
 * API to handle the submission of the Edit Staff form (Corrected)
 * Current Time: Monday, June 16, 2025 at 5:02 PM IST
 * Location: Kalyan, Maharashtra, India
 */
require_once __DIR__ . '/../../src/init.php';
require_once __DIR__ . '/../../config.php';
header('Content-Type: application/json');

// --- Security and Validation ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access Denied.']);
    exit();
}
validate_csrf_token($_POST['csrf_token'] ?? '');
$staff_id = $_POST['staff_id'] ?? '';
if (empty($staff_id)) {
    echo json_encode(['success' => false, 'message' => 'Staff ID is missing.']);
    exit();
}

try {
    $pdo->beginTransaction();
    $upload_dir = __DIR__ . '/../../public/uploads/staff/';
    if (!is_dir($upload_dir)) { mkdir($upload_dir, 0755, true); }

    // --- File Update Logic ---
    $update_file_clauses = [];
    $file_data_to_bind = [];
    $doc_types = ['police', 'medical', 'ta', 'ppo', 'profile', 'signature', 'adhar_card'];
    
    foreach ($doc_types as $doc_type) {
        $field_name = $doc_type . '_image';
        if (isset($_FILES[$field_name]) && $_FILES[$field_name]['error'] == UPLOAD_ERR_OK) {
            $newFileName = $staff_id . '_' . $doc_type . '.' . pathinfo($_FILES[$field_name]['name'], PATHINFO_EXTENSION);
            
            // Use absolute upload directory to ensure files are saved in the intended location
            $new_filename = process_image_upload($_FILES[$field_name], $upload_dir, $newFileName);
            
            if (is_array($new_filename)) { // Check if process_image_upload returned an error array
                throw new Exception(implode(', ', $new_filename));
            }
            
            $update_file_clauses[] = "$field_name = :$field_name";
            $file_data_to_bind[$field_name] = $new_filename;
        }
    }

    // --- Build and Execute Database Query ---
    $sql = "UPDATE varuna_staff SET 
                name = :name, 
                designation = :designation, 
                contact = :contact, 
                adhar_card_number = :adhar_card_number,
                contract_id = :contract_id,
                police_issue_date = :police_issue_date, police_expiry_date = :police_expiry_date,
                medical_issue_date = :medical_issue_date, medical_expiry_date = :medical_expiry_date,
                status = 'pending'";

    if (!empty($update_file_clauses)) {
        $sql .= ", " . implode(", ", $update_file_clauses);
    }
    
    $sql .= " WHERE id = :staff_id";

    // Combine all data for binding
    $data_to_bind = array_merge($file_data_to_bind, [
        'name' => $_POST['name'],
        'designation' => $_POST['designation'],
        'contact' => $_POST['contact'],
        'adhar_card_number' => !empty($_POST['adhar_card_number']) ? $_POST['adhar_card_number'] : null,
        'contract_id' => $_POST['contract_id'],
        'police_issue_date' => !empty($_POST['police_issue_date']) ? $_POST['police_issue_date'] : null,
        'police_expiry_date' => !empty($_POST['police_expiry_date']) ? $_POST['police_expiry_date'] : null,
        'medical_issue_date' => !empty($_POST['medical_issue_date']) ? $_POST['medical_issue_date'] : null,
        'medical_expiry_date' => !empty($_POST['medical_expiry_date']) ? $_POST['medical_expiry_date'] : null,
        'staff_id' => $staff_id
    ]);
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($data_to_bind);

    // Delete any old rejection remarks
    $remark_stmt = $pdo->prepare("DELETE FROM varuna_remarks WHERE staff_id = ?");
    $remark_stmt->execute([$staff_id]);

    $pdo->commit();

    log_activity($pdo, 'STAFF_EDIT_RESUBMIT', ['details' => "Staff ID $staff_id edited and resubmitted."]);
    echo json_encode([
        'success' => true, 
        'message' => 'Staff details updated and sent for approval.',
        'new_csrf_token' => generate_csrf_token() // Generate and send a new token
    ]);
} catch (Exception $e) {
    $pdo->rollBack();
    log_activity($pdo, 'STAFF_EDIT_FAIL', ['details' => $e->getMessage()]);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}