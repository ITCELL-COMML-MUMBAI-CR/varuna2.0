<?php
/**
 * VARUNA System - QR Code Verification API for Android App
 * Endpoint: /api/searchvendor.php
 * Method: GET
 * Parameter: ?q=<staff_id>
 * FIX: Removed encryption/decryption logic. Expects plain staff ID.
 */

// We need the application's core environment
require_once __DIR__ . '/../../src/init.php';

// --- 1. DECRYPTION LOGIC REMOVED ---

// --- 2. Input Processing (Plain Text) ---
$staff_id = $_GET['q'] ?? '';

// If no 'q' parameter, show an invalid request message.
if (empty($staff_id)) {
    http_response_code(400); // Bad Request
    die("Error: Request parameter 'q' is missing.");
}

// --- 3. Database Query ---
try {
    // Fetch all details for the provided staff ID.
    // We only want to show details for staff who are currently 'approved'.
    $stmt = $pdo->prepare(
        "SELECT 
            s.*, 
            c.contract_name, c.contract_type, c.station_code, 
            l.name as licensee_name
         FROM varuna_staff s
         LEFT JOIN contracts c ON s.contract_id = c.id
         LEFT JOIN varuna_licensee l ON c.licensee_id = l.id
         WHERE s.id = ? AND s.status = 'approved'"
    );
    $stmt->execute([$staff_id]);
    $staff = $stmt->fetch();

} catch (PDOException $e) {
    // Handle database errors gracefully.
    error_log("searchvendor.php DB Error: " . $e->getMessage());
    http_response_code(500);
    die("A server error occurred while fetching data.");
}


// Log the scan attempt
log_activity($pdo, 'QR_SCAN_VERIFY', [
    'user_id' => null, // No logged-in user for this action
    'username' => 'AndroidApp',
    'details' => "Scanned Staff ID: $staff_id. " . ($staff ? "Found." : "Not Found or Not Approved.")
]);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vendor Verification</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>css/style.css">
    <style>
        body { background-color: #f4f4f4; display: block; }
        .verification-container { max-width: 600px; margin: 20px auto; background-color: #fff; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); overflow: hidden; }
        .header { background: linear-gradient(135deg, #28a745, #218838); color: white; padding: 20px; text-align: center; }
        .header.invalid { background: linear-gradient(135deg, #dc3545, #c82333); }
        .header h1 { margin: 0; font-size: 1.5rem; }
        .content { padding: 20px; }
        .profile-pic { display: block; width: 150px; height: 180px; object-fit: cover; margin: 0 auto 20px; border-radius: 8px; border: 4px solid #fff; box-shadow: 0 2px 8px rgba(0,0,0,0.15); }
        .detail-item { margin-bottom: 12px; padding-bottom: 12px; border-bottom: 1px solid #eee; display: flex; align-items: center; }
        .detail-item label { font-weight: bold; color: #555; flex-basis: 120px; }
        .detail-item span { flex-grow: 1; }
        .status { font-weight: bold; text-transform: uppercase; }
        .status-approved { color: #28a745; }
        .documents-section h2 { font-size: 1.2rem; margin-top: 30px; margin-bottom: 15px; border-bottom: 2px solid var(--primary-color); padding-bottom: 5px;}
        .doc-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(120px, 1fr)); gap: 15px; }
        .doc-link { display: block; text-decoration: none; color: #333; text-align: center; }
        .doc-link img { width: 100%; height: 150px; object-fit: cover; border-radius: 5px; border: 1px solid #ddd; margin-bottom: 5px; }
        .doc-link span { font-size: 0.8rem; }
    </style>
</head>
<body>

<div class="verification-container">
    <?php if ($staff): ?>
        <div class="header">
            <h1>✔ Valid ID Card</h1>
        </div>
        <div class="content">
            <img src="<?php echo BASE_URL . 'uploads/staff/' . htmlspecialchars($staff['profile_image']); ?>" alt="Profile Picture" class="profile-pic">

            <div class="detail-item">
                <label>Staff ID:</label>
                <span><?php echo htmlspecialchars($staff['id']); ?></span>
            </div>
            <div class="detail-item">
                <label>Name:</label>
                <span><?php echo htmlspecialchars($staff['name']); ?></span>
            </div>
            <div class="detail-item">
                <label>Designation:</label>
                <span><?php echo htmlspecialchars($staff['designation']); ?></span>
            </div>
            <div class="detail-item">
                <label>Status:</label>
                <span class="status status-<?php echo htmlspecialchars($staff['status']); ?>"><?php echo htmlspecialchars($staff['status']); ?></span>
            </div>
            <div class="detail-item">
                <label>Contract:</label>
                <span><?php echo htmlspecialchars($staff['contract_name']); ?></span>
            </div>
            <div class="detail-item">
                <label>Licensee:</label>
                <span><?php echo htmlspecialchars($staff['licensee_name']); ?></span>
            </div>
            <div class="detail-item" style="border-bottom: none;">
                <label>Location:</label>
                <span><?php echo htmlspecialchars($staff['station_code']); ?></span>
            </div>

            <div class="documents-section">
                <h2>Documents</h2>
                <div class="doc-grid">
                    <a href="<?php echo BASE_URL . 'uploads/staff/' . htmlspecialchars($staff['signature_image']); ?>" target="_blank" class="doc-link">
                        <img src="<?php echo BASE_URL . 'uploads/staff/' . htmlspecialchars($staff['signature_image']); ?>" alt="Signature">
                        <span>Signature</span>
                    </a>
                    <?php if (!empty($staff['police_image'])): ?>
                    <a href="<?php echo BASE_URL . 'uploads/staff/' . htmlspecialchars($staff['police_image']); ?>" target="_blank" class="doc-link">
                        <img src="<?php echo BASE_URL . 'uploads/staff/' . htmlspecialchars($staff['police_image']); ?>" alt="Police Verification">
                        <span>Police Verification<br>(Expires: <?php echo htmlspecialchars($staff['police_expiry_date']); ?>)</span>
                    </a>
                    <?php endif; ?>
                    <?php if (!empty($staff['medical_image'])): ?>
                    <a href="<?php echo BASE_URL . 'uploads/staff/' . htmlspecialchars($staff['medical_image']); ?>" target="_blank" class="doc-link">
                        <img src="<?php echo BASE_URL . 'uploads/staff/' . htmlspecialchars($staff['medical_image']); ?>" alt="Medical Fitness">
                        <span>Medical Fitness<br>(Expires: <?php echo htmlspecialchars($staff['medical_expiry_date']); ?>)</span>
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="header invalid">
            <h1>✖ Invalid or Inactive ID</h1>
        </div>
        <div class="content" style="text-align: center;">
            <p>The scanned QR code is not associated with a valid, approved staff member in the VARUNA system.</p>
            <p>Please check the ID card or contact the issuing authority.</p>
        </div>
    <?php endif; ?>
</div>

</body>
</html>