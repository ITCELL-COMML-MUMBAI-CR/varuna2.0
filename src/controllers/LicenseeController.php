<?php
// Security check
if (!defined('VARUNA_ENTRY_POINT')) {
    // Show the 404 page instead, as this file should not be directly accessible
    require_once __DIR__ . '/../views/errors/404.php';
    exit();
}

global $pdo;
$error_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate CSRF token
    validate_csrf_token($_POST['csrf_token'] ?? '');

    $name = trim($_POST['name']);
    $mobile_number = trim($_POST['mobile_number']);
    $mobile_pattern = "/^[1-9][0-9]{9}$/";

    // Store user's input in the session to repopulate the form on failure
    $_SESSION['old_input'] = $_POST;

    // Validation
    if (empty($name) || empty($mobile_number)) {
        $_SESSION['error_message'] = 'Both name and mobile number are required.';
        header("Location: " . BASE_URL . "licensees/add");
        exit();
    } elseif (!preg_match($mobile_pattern, $mobile_number)) {
        $_SESSION['error_message'] = 'Please enter a valid 10-digit mobile number not starting with 0.';
        header("Location: " . BASE_URL . "licensees/add");
        exit();
    } else {
        // --- On Success ---
        try {
            $stmt = $pdo->prepare("INSERT INTO varuna_licensee (name, mobile_number) VALUES (:name, :mobile_number)");
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':mobile_number', $mobile_number);
            $stmt->execute();

            $logData = ['user_id' => $_SESSION['user_id'], 'username' => $_SESSION['username'], 'details' => "Added new licensee: $name"];
            log_activity($pdo, 'LICENSEE_ADD_SUCCESS', $logData);

            // Set success message and clear old input on success
            $_SESSION['success_message'] = 'Licensee added successfully!';
            unset($_SESSION['old_input']); 

            header("Location: " . BASE_URL . "licensees/add");
            exit();

        } catch (PDOException $e) {
            $_SESSION['error_message'] = "Error: Could not add licensee.";
            log_activity($pdo, 'LICENSEE_ADD_FAIL', ['details' => $e->getMessage()]);
            
            header("Location: " . BASE_URL . "licensees/add");
            exit();
        }
    }
}