<?php

require_once __DIR__ . '/../src/init.php';
// Define the entry point constant for security
define('VARUNA_ENTRY_POINT', true);

// Include the master configuration file
require_once __DIR__ . '/../config.php';

// Simple Router Logic
// Get the requested URL, default to 'login' if it's empty
$request_uri = trim($_GET['url'] ?? 'login', '/');


function check_permission($allowed_roles) {
    if (!isset($_SESSION['user_id'])) {
        header("Location: " . BASE_URL . "login");
        exit();
    }
    if (!in_array($_SESSION['role'], $allowed_roles)) {
        require_once __DIR__ . '/../src/views/errors/404.php';
        exit();
    }
}

// Route the request
switch ($request_uri) {
    case 'login':
        // If user is already logged in, redirect away from login page
        if (isset($_SESSION['user_id'])) {
            header("Location: " . BASE_URL . "dashboard");
            exit();
        }
        // Load the login logic controller
        require_once __DIR__ . '/../src/controllers/LoginController.php';
        // Load the login page view
        require_once __DIR__ . '/../src/views/login_view.php';
        break;

    // Allowed for all logged-in users (except ASC)
    case 'dashboard': require_once __DIR__ . '/dashboard.php'; break;
    case 'viewer': require_once __DIR__ . '/../src/views/viewer_page_view.php'; break;
    case 'profile': check_permission(['ADMIN', 'SCI', 'VIEWER']); require_once __DIR__ . '/../src/views/profile_view.php'; break;
    case 'profile/upload_signature': check_permission(['ADMIN', 'SCI', 'VIEWER']); require_once __DIR__ . '/../src/controllers/ProfileController.php'; break;
    
    // SCI and Admin only
    case 'licensees/add': check_permission(['ADMIN', 'SCI']); require_once __DIR__ . '/../src/controllers/LicenseeController.php'; require_once __DIR__ . '/../src/views/add_licensee_view.php'; break;
    case 'contracts/add': check_permission(['ADMIN', 'SCI']); require_once __DIR__ . '/../src/controllers/ContractController.php'; require_once __DIR__ . '/../src/views/add_contract_view.php'; break;
    case 'staff/add': check_permission(['ADMIN', 'SCI']); require_once __DIR__ . '/../src/controllers/StaffController.php'; require_once __DIR__ . '/../src/views/add_staff_view.php'; break;
    case 'contracts/manage': check_permission(['ADMIN', 'SCI']); require_once __DIR__ . '/../src/views/manage_contracts_view.php'; break;
    case 'licensees/manage': check_permission(['ADMIN', 'SCI']); require_once __DIR__ . '/../src/views/manage_licensees_view.php'; break;
    case 'staff/approved': check_permission(['ADMIN', 'SCI']); require_once __DIR__ . '/../src/views/approved_staff_view.php'; break;
    case 'bulk-print': check_permission(['ADMIN', 'SCI']); require_once __DIR__ . '/../src/views/bulk_printing_view.php'; break;
    
    // SCI only
    case 'staff/approval': check_permission(['SCI']); require_once __DIR__ . '/../src/views/staff_approval_view.php'; break;

    // Admin only
    case 'id-cards/admin': check_permission(['ADMIN']); require_once __DIR__ . '/../src/views/id_card_admin_view.php'; break;
    case 'admin-panel': check_permission(['ADMIN']); require_once __DIR__ . '/../src/views/admin_panel_view.php'; break;

    case 'logout': require_once __DIR__ . '/logout.php'; break;
    default: require_once __DIR__ . '/../src/views/errors/404.php'; break;
}