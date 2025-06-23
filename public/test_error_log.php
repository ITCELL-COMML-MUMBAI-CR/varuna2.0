<?php
/**
 * VARUNA System - Error Logging Diagnostic Script
 * Current Time: Tuesday, June 17, 2025 at 12:38 PM IST
 * Location: Kalyan, Maharashtra, India
 */

// Temporarily display errors on this page ONLY for debugging purposes
ini_set('display_errors', '1');
error_reporting(E_ALL);

// --- The Test Starts Here ---

echo "<h1>Error Log Test</h1>";

// Define the absolute path to the log file
$log_file_path = dirname(__DIR__) . '/logs/error.log';
echo "<p>Attempting to log errors to: <strong>" . htmlspecialchars($log_file_path) . "</strong></p><hr>";


// --- Test 1: Check if the /logs/ directory is writable ---
echo "<h2>Step 1: Testing Folder Permissions...</h2>";
$logs_dir = dirname($log_file_path);

if (is_dir($logs_dir)) {
    if (is_writable($logs_dir)) {
        echo "<p style='color:green; font-weight:bold;'>SUCCESS: The '/logs/' directory exists and is writable by the server.</p>";
        // Try to write a test message directly
        $test_content = "Permission test successful at " . date('Y-m-d H:i:s') . "\n";
        if (file_put_contents($log_file_path, $test_content, FILE_APPEND) !== false) {
             echo "<p style='color:green; font-weight:bold;'>SUCCESS: Wrote a test line directly to the log file.</p>";
        } else {
             echo "<p style='color:red; font-weight:bold;'>FAILURE: The '/logs/' directory is writable, but the script could NOT write to the log file. This could be a file ownership issue on the server.</p>";
        }
    } else {
        echo "<p style='color:red; font-weight:bold;'>FAILURE: The '/logs/' directory exists but is NOT writable. This is the most likely problem. Please set the directory permissions to 775 on your Hostinger server.</p>";
    }
} else {
    echo "<p style='color:red; font-weight:bold;'>FAILURE: The '/logs/' directory does not exist at the expected path.</p>";
}
echo "<hr>";


// --- Test 2: Attempt to trigger a PHP error ---
echo "<h2>Step 2: Triggering a PHP Notice...</h2>";
echo "<p>This next step will intentionally generate a PHP Notice. We will check if PHP's built-in logger writes it to the file.</p>";

// Configure PHP's built-in logger
ini_set('log_errors', '1');
ini_set('error_log', $log_file_path);

// This line will generate a "Notice: Undefined variable..."
$test = $some_variable_that_does_not_exist; 

echo "<p style='color:blue;'>A PHP Notice was just generated.</p>";
echo "<hr>";


// --- Test 3: Final Instructions ---
echo "<h2>Step 3: Check the Results</h2>";
echo "<p>Please now open your <code>varuna/logs/error.log</code> file using your File Manager.</p>";
echo "<ul><li>If you see the 'Permission test successful' line AND the 'PHP Notice: Undefined variable' line, then our logging is working, and the issue is somewhere in the application's code structure.</li>";
echo "<li>If you ONLY see the 'Permission test successful' line but NOT the PHP Notice, it means your Hostinger server is preventing `ini_set('error_log', ...)` from working. Please see the `.user.ini` solution below.</li>";
echo "<li>If you see a FAILURE message in Step 1, you must fix the folder permissions first.</li></ul>";