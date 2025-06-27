<?php
require_once __DIR__ . '/../src/init.php';

// Security check
if (!isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "login");
    exit();
}

$staff_id = $_GET['id'] ?? '';
if (empty($staff_id)) {
    header("Location: " . BASE_URL . "dashboard");
    exit();
}

// Get staff details including documents
$stmt = $pdo->prepare("
    SELECT 
        s.*,
        c.contract_name,
        c.station_code,
        l.name as licensee_name
    FROM varuna_staff s
    LEFT JOIN contracts c ON s.contract_id = c.id
    LEFT JOIN varuna_licensee l ON c.licensee_id = l.id
    WHERE s.id = ?
");
$stmt->execute([$staff_id]);
$staff = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$staff) {
    header("Location: " . BASE_URL . "dashboard");
    exit();
}

include __DIR__ . '/../src/views/header.php';
?>

<main class="page-container" style="padding: 20px 40px;">
    <div class="staff-details-container">
        <h1>Staff Details</h1>
        
        <!-- Basic Information -->
        <div class="details-section">
            <h2>Basic Information</h2>
            <div class="details-grid">
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
                    <label>Contact:</label>
                    <span><?php echo htmlspecialchars($staff['contact']); ?></span>
                </div>
                <div class="detail-item">
                    <label>Status:</label>
                    <span class="status-<?php echo strtolower($staff['status']); ?>"><?php echo htmlspecialchars($staff['status']); ?></span>
                </div>
            </div>
        </div>

        <!-- Contract Information -->
        <div class="details-section">
            <h2>Contract Information</h2>
            <div class="details-grid">
                <div class="detail-item">
                    <label>Contract Name:</label>
                    <span><?php echo htmlspecialchars($staff['contract_name']); ?></span>
                </div>
                <div class="detail-item">
                    <label>Station:</label>
                    <span><?php echo htmlspecialchars($staff['station_code']); ?></span>
                </div>
                <div class="detail-item">
                    <label>Licensee:</label>
                    <span><?php echo htmlspecialchars($staff['licensee_name']); ?></span>
                </div>
            </div>
        </div>

        <!-- Documents -->
        <div class="details-section">
            <h2>Documents</h2>
            <div class="documents-grid">
                <!-- Profile Image -->
                <div class="document-item">
                    <h3>Profile Photo</h3>
                    <?php if (!empty($staff['profile_image'])): ?>
                        <img src="<?php echo BASE_URL . 'uploads/staff/' . htmlspecialchars($staff['profile_image']); ?>" alt="Profile Photo">
                    <?php else: ?>
                        <p>No profile photo available</p>
                    <?php endif; ?>
                </div>

                <!-- Police Verification -->
                <div class="document-item">
                    <h3>Police Verification</h3>
                    <?php if (!empty($staff['police_image'])): ?>
                        <a href="<?php echo BASE_URL . 'uploads/staff/' . htmlspecialchars($staff['police_image']); ?>" target="_blank" class="document-link">
                            View Document
                        </a>
                        <p>Expiry: <?php echo htmlspecialchars($staff['police_expiry_date']); ?></p>
                    <?php else: ?>
                        <p>No police verification document available</p>
                    <?php endif; ?>
                </div>

                <!-- Medical Certificate -->
                <div class="document-item">
                    <h3>Medical Certificate</h3>
                    <?php if (!empty($staff['medical_image'])): ?>
                        <a href="<?php echo BASE_URL . 'uploads/staff/' . htmlspecialchars($staff['medical_image']); ?>" target="_blank" class="document-link">
                            View Document
                        </a>
                        <p>Expiry: <?php echo htmlspecialchars($staff['medical_expiry_date']); ?></p>
                    <?php else: ?>
                        <p>No medical certificate available</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include __DIR__ . '/../src/views/footer.php'; ?> 