<?php
// This script is meant to be run by a server cron job, not accessed via a browser.
if (php_sapi_name() !== 'cli') {
    die("This script can only be run from the command line.");
}

// Bootstrap the application to get the database connection
require_once dirname(__DIR__) . '/../init.php';

echo "Cron Job: Checking for expired staff documents...\n";

try {
    // Find all approved staff whose police, medical, or TA documents have expired.
    $sql = "SELECT id FROM varuna_staff
            WHERE status = 'approved' AND
                  (
                      (police_expiry_date IS NOT NULL AND police_expiry_date < CURDATE()) OR
                      (medical_expiry_date IS NOT NULL AND medical_expiry_date < CURDATE()) OR
                      (ta_expiry_date IS NOT NULL AND ta_expiry_date < CURDATE())
                  )";
    $stmt = $pdo->query($sql);
    $expired_staff_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (empty($expired_staff_ids)) {
        echo "No expired staff found. Exiting.\n";
        exit();
    }

    echo "Found " . count($expired_staff_ids) . " staff member(s) to terminate.\n";

    $placeholders = implode(',', array_fill(0, count($expired_staff_ids), '?'));
    
    // Update their status to Terminated
    $update_stmt = $pdo->prepare("UPDATE varuna_staff SET status = 'Terminated' WHERE id IN ($placeholders)");
    $update_stmt->execute($expired_staff_ids);

    // Log this system activity
    log_activity($pdo, 'SYSTEM_AUTO_TERMINATE', [
        'user_id' => null,
        'username' => 'SystemCron',
        'details' => 'Terminated ' . count($expired_staff_ids) . ' staff due to expired documents. IDs: ' . implode(', ', $expired_staff_ids)
    ]);
    
    echo "Successfully terminated " . count($expired_staff_ids) . " staff member(s).\n";

} catch (Exception $e) {
    echo "An error occurred: " . $e->getMessage() . "\n";
    error_log("Cron Job Failed (update_expired_staff.php): " . $e->getMessage());
}