<?php
/**
 * Controller for Adding a New Contract (with conditional validation)
 * Current Time: Tuesday, June 17, 2025 at 12:28 PM IST
 * Location: Kalyan, Maharashtra, India
 */

// Security: Deny direct file access
if (!defined('VARUNA_ENTRY_POINT')) {
    require_once __DIR__ . '/../views/errors/404.php';
    exit();
}

global $pdo;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 1. Validate CSRF Token
    validate_csrf_token($_POST['csrf_token'] ?? '');
    // 2. Store user input for PRG pattern (to repopulate form on error)
    $_SESSION['old_input'] = $_POST;

    // --- NEW: Conditional Server-Side Validation ---

    $errors = [];
    $invalid_fields = [];

    // First, get the properties of the selected contract type to decide which fields are required
    $contractType = $_POST['contract_type'] ?? '';
    $type_info = null;
    if ($contractType) {
        $type_stmt = $pdo->prepare("SELECT TrainStation FROM varuna_contract_types WHERE ContractType = ?");
        $type_stmt->execute([$contractType]);
        $type_info = $type_stmt->fetch();
    }
    
    // Define the fields that are ALWAYS required
    $always_required = [
        'licensee_id' => 'Licensee Name',
        'contract_name' => 'Contract Name',
        'contract_type' => 'Contract Type',
        'location' => 'Location',
        'license_fee' => 'License Fee',
        'period' => 'Period',
        'status' => 'Status'
    ];

    // Conditionally add fields to be validated based on the contract's 'TrainStation' type
    if ($type_info && $type_info['TrainStation'] === 'Station') {
        $always_required['section_code'] = 'Section';
        $always_required['station_code'] = 'Station';
        $always_required['stalls'] = 'Stalls';
    } else if ($type_info && $type_info['TrainStation'] === 'Train') {
        if (empty($_POST['trains'])) {
            $errors[] = 'Trains'; // Add to error list
            $invalid_fields[] = 'train_select'; // ID of the select2 element for highlighting
        }
    }
    
    // Now, loop through the final list of required fields and check them
    foreach ($always_required as $field_name => $label) {
        if (empty($_POST[$field_name])) {
            $errors[] = $label;
            $invalid_fields[] = $field_name;
        }
    }
    
    // If there are any validation errors, prepare the session and redirect back
    if (!empty($errors)) {
        $_SESSION['error_message'] = 'Please fix the required fields: ' . implode(', ', $errors);
        $_SESSION['invalid_fields'] = $invalid_fields;
        header("Location: " . BASE_URL . "contracts/add");
        exit();
    }

    // --- File Upload Processing ---
    $stmt = $pdo->query("SHOW TABLE STATUS LIKE 'contracts'");
    $next_id = $stmt->fetch(PDO::FETCH_ASSOC)['Auto_increment'];
    $upload_dir = PROJECT_ROOT . '/public/uploads/contracts/';
    if (!is_dir($upload_dir)) { mkdir($upload_dir, 0755, true); }
    $uploaded_files = [];
    $file_fields = ['fssai_image' => 'fssai', 'fire_safety_image' => 'firesafety', 'pest_control_image' => 'pestcontrol', 'water_safety_image' => 'watersafety'];

    foreach ($file_fields as $field_name => $doc_type) {
        if (isset($_FILES[$field_name]) && $_FILES[$field_name]['error'] == UPLOAD_ERR_OK) {
            $newFileName = $next_id . '_' . $doc_type . '.' . pathinfo($_FILES[$field_name]['name'], PATHINFO_EXTENSION);
            $result = process_image_upload($_FILES[$field_name], $upload_dir, $newFileName);
            if (is_array($result)) {
                $_SESSION['error_message'] = 'Invalid file detected. Please use only JPG, PNG, or WEBP formats under 5MB.';
                header("Location: " . BASE_URL . "contracts/add");
                exit();
            }
            $uploaded_files[$field_name] = $result;
        }
    }

    // --- Prepare Data for Insertion ---
    $stationCodeValue = null;
    if ($type_info && $type_info['TrainStation'] === 'Train' && !empty($_POST['trains'])) {
        $stationCodeValue = implode(',', $_POST['trains']);
    } elseif ($type_info && $type_info['TrainStation'] === 'Station' && !empty($_POST['station_code'])) {
        $stationCodeValue = $_POST['station_code'];
    }

    // --- Database Insertion ---
    try {
        $data = [
            'licensee_id' => $_POST['licensee_id'],
            'section_code' => ($type_info && $type_info['TrainStation'] === 'Train') ? 'TRAIN' : $_POST['section_code'],
            'station_code' => $stationCodeValue,
            'contract_name' => $_POST['contract_name'],
            'contract_type' => $_POST['contract_type'],
            'location' => $_POST['location'],
            'stalls' => ($type_info && $type_info['TrainStation'] === 'Station') ? $_POST['stalls'] : null,
            'license_fee' => $_POST['license_fee'],
            'period' => $_POST['period'],
            'status' => $_POST['status'],
            'rail_neer_stock' => !empty($_POST['rail_neer_stock']) ? $_POST['rail_neer_stock'] : null,
            'fssai_image' => $uploaded_files['fssai_image'] ?? null,
            'fire_safety_image' => $uploaded_files['fire_safety_image'] ?? null,
            'pest_control_image' => $uploaded_files['pest_control_image'] ?? null,
            'water_safety_image' => $uploaded_files['water_safety_image'] ?? null
        ];

        $sql = "INSERT INTO contracts (licensee_id, section_code, station_code, contract_name, contract_type, location, stalls, license_fee, period, status, fssai_image, fire_safety_image, pest_control_image, rail_neer_stock, water_safety_image) 
                VALUES (:licensee_id, :section_code, :station_code, :contract_name, :contract_type, :location, :stalls, :license_fee, :period, :status, :fssai_image, :fire_safety_image, :pest_control_image, :rail_neer_stock, :water_safety_image)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($data);
        
        $newContractId = $pdo->lastInsertId();

        // --- Activity Logging ---
        $logData = [ 'details' => "Added new contract '{$_POST['contract_name']}' with ID: $newContractId" ];
        log_activity($pdo, 'CONTRACT_ADD_SUCCESS', $logData);

        unset($_SESSION['old_input']);
        $_SESSION['success_message'] = "Contract '{$_POST['contract_name']}' added successfully!";
        header("Location: " . BASE_URL . "contracts/add");
        exit();

    } catch (PDOException $e) {
        // --- Error Logging ---
        $logData = [ 'details' => "Failed to add contract '{$_POST['contract_name']}'. Error: " . $e->getMessage() ];
        log_activity($pdo, 'CONTRACT_ADD_FAIL', $logData);
        
        $_SESSION['error_message'] = "Database Error: Could not add the contract.";
        header("Location: " . BASE_URL . "contracts/add");
        exit();
    }
}
?>