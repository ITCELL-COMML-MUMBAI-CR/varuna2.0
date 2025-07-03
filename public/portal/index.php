<?php
/**
 * Entry point for the Licensee Portal
 */

// Define entry point constant
define('VARUNA_ENTRY_POINT', true);

// Include initialization file
require_once __DIR__ . '/../../src/init.php';

// Ensure we're using the token from the query string
if (!isset($_GET['token'])) {
    require_once __DIR__ . '/../../src/views/errors/invalid_link_view.php';
    exit();
}

// Include and execute the portal controller
require_once __DIR__ . '/../../src/controllers/PortalController.php'; 