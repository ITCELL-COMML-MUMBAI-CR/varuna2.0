<?php
// Base URL Configuration
// This makes all links and redirects work correctly in any environment.
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
$host = $_SERVER['HTTP_HOST'];
define('APP_ROOT_URL', "$protocol://$host/varuna/"); 
define('BASE_URL', APP_ROOT_URL . 'public/');

// Database Configuration Constants
define('DB_HOST', '193.203.184.199');
define('DB_USER', 'u473452443_narayan');
define('DB_PASS', 'g&1ilWBb!3');
define('DB_NAME', 'u473452443_comml_bb_cr');

// Note: The redundant require_once, session_start, and PDO connection logic have been removed from this file.
// This is now handled centrally by init.php.







#FOR SERVER

/* $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
$host = $_SERVER['HTTP_HOST'];
define('APP_ROOT_URL', "$protocol://$host/"); 
define('BASE_URL', APP_ROOT_URL);

// Database Configuration Constants
define('DB_HOST', 'localhost');
define('DB_USER', 'u473452443_narayan');
define('DB_PASS', 'g&1ilWBb!3');
define('DB_NAME', 'u473452443_comml_bb_cr'); */