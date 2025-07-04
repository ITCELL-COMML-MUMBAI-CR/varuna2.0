<?php
if (!defined('VARUNA_ENTRY_POINT')) { 
     require_once __DIR__ . '/errors/404.php';
    exit();
 }
// Fetch current signature to display it
$stmt = $pdo->prepare("SELECT signature_path FROM varuna_authority_signatures WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$current_signature = $stmt->fetchColumn();
?>
<?php include __DIR__ . '/header.php'; ?>
<?php include __DIR__ . '/navbar.php'; ?>

<main class="page-container" style="padding: 20px 40px; max-width: 1200px; margin: 0 auto;">
    <div style="margin-bottom: 30px;">
        <h2 style="color: var(--text-color); margin-bottom: 10px; display: flex; align-items: center; gap: 15px;">
            <i class="fas fa-user-circle" style="color: var(--primary-color); font-size: 2rem;"></i>
            My Profile
        </h2>
        <p style="color: var(--gray-color); margin: 0;">Manage your account settings and signature</p>
    </div>

    <div class="dashboard-main-grid" style="margin-bottom: 30px;">
        <!-- User Information Card -->
        <div class="dashboard-card">
            <h3 style="color: var(--primary-color); margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
                <i class="fas fa-id-badge"></i>
                Account Information
            </h3>
            <div class="details-grid" style="grid-template-columns: 1fr;">
                <div class="detail-item">
                    <label><i class="fas fa-user" style="margin-right: 8px; color: var(--primary-color);"></i>Username</label>
                    <span style="font-weight: 500;"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                </div>
                <div class="detail-item">
                    <label><i class="fas fa-shield-alt" style="margin-right: 8px; color: var(--primary-color);"></i>Role</label>
                    <span style="font-weight: 500; color: var(--primary-color);"><?php echo htmlspecialchars($_SESSION['role']); ?></span>
                </div>
                <?php if (isset($_SESSION['designation']) && !empty($_SESSION['designation'])): ?>
                <div class="detail-item">
                    <label><i class="fas fa-briefcase" style="margin-right: 8px; color: var(--primary-color);"></i>Designation</label>
                    <span style="font-weight: 500;"><?php echo htmlspecialchars($_SESSION['designation']); ?></span>
                </div>
                <?php endif; ?>
                <?php if (isset($_SESSION['section']) && !empty($_SESSION['section'])): ?>
                <div class="detail-item">
                    <label><i class="fas fa-building" style="margin-right: 8px; color: var(--primary-color);"></i>Section</label>
                    <span style="font-weight: 500;"><?php echo htmlspecialchars($_SESSION['section']); ?></span>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Signature Management Card -->
        <div class="dashboard-card">
            <h3 style="color: var(--primary-color); margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
                <i class="fas fa-signature"></i>
                Digital Signature
            </h3>
            
            <?php if ($current_signature): ?>
                <div style="margin-bottom: 25px;">
                    <label style="display: block; margin-bottom: 10px; font-weight: 500; color: var(--text-color);">
                        <i class="fas fa-check-circle" style="color: #28a745; margin-right: 8px;"></i>
                        Current Signature
                    </label>
                    <div style="border: 2px solid var(--secondary-color); border-radius: 8px; padding: 15px; background: #fff; text-align: center;">
                        <img src="<?php echo BASE_URL . 'uploads/authority/' . $current_signature; ?>" 
                             style="max-width: 200px; max-height: 80px; border: 1px solid #ddd; border-radius: 4px;" 
                             alt="Current signature">
                    </div>
                    <div style="margin-top: 15px; display: flex; gap: 10px; justify-content: center;">
                        <button type="button" 
                                onclick="confirmDeleteSignature()" 
                                class="btn-action reject" 
                                style="padding: 8px 16px; font-size: 14px;">
                            <i class="fas fa-trash" style="margin-right: 5px;"></i>
                            Delete Signature
                        </button>
                    </div>
                </div>
            <?php else: ?>
                <div style="margin-bottom: 25px; text-align: center; padding: 20px; background: #f8f9fa; border-radius: 8px; border: 2px dashed #dee2e6;">
                    <i class="fas fa-signature" style="font-size: 3rem; color: var(--gray-color); margin-bottom: 10px;"></i>
                    <p style="color: var(--gray-color); margin: 0; font-weight: 500;">No signature uploaded yet</p>
                    <p style="color: var(--gray-color); margin: 5px 0 0 0; font-size: 14px;">Upload your signature to enable ID card printing features</p>
                </div>
            <?php endif; ?>

            <form action="<?php echo BASE_URL; ?>profile/upload_signature" method="POST" enctype="multipart/form-data" style="background: #f8f9fa; padding: 20px; border-radius: 8px;">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                <div class="input-group">
                    <label style="font-weight: 500;">
                        <i class="fas fa-upload" style="margin-right: 8px; color: var(--primary-color);"></i>
                        <?php echo $current_signature ? 'Update' : 'Upload'; ?> Your Signature Image
                    </label>
                    <input type="file" 
                           name="signature_file" 
                           required 
                           accept="image/png, image/jpeg, image/jpg"
                           style="margin-top: 10px;">
                    <small style="color: var(--gray-color); margin-top: 5px; display: block;">
                        <i class="fas fa-info-circle" style="margin-right: 5px;"></i>
                        Accepted formats: PNG, JPEG, JPG (Max size: 2MB)
                    </small>
                </div>
                <button type="submit" class="btn-login" style="margin-top: 20px; width: 100%;">
                    <i class="fas fa-save" style="margin-right: 8px;"></i>
                    <?php echo $current_signature ? 'Update' : 'Upload'; ?> Signature
                </button>
            </form>
        </div>
    </div>

    <!-- Additional Information Section -->
    <div class="form-section">
        <div class="form-section-title">
            <i class="fas fa-lightbulb" style="margin-right: 10px;"></i>
            Important Information
        </div>
        <div style="background: #e3f2fd; border-left: 4px solid #2196f3; padding: 15px; border-radius: 4px; margin-bottom: 15px;">
            <p style="margin: 0; color: #1565c0;">
                <i class="fas fa-info-circle" style="margin-right: 8px;"></i>
                <strong>Signature Requirements:</strong> Your digital signature is required for ID card approval and printing. 
                Please ensure your signature is clear and professional.
            </p>
        </div>
        <div style="background: #fff3e0; border-left: 4px solid #ff9800; padding: 15px; border-radius: 4px;">
            <p style="margin: 0; color: #e65100;">
                <i class="fas fa-exclamation-triangle" style="margin-right: 8px;"></i>
                <strong>Security Notice:</strong> Your signature will be used on official documents. 
                Only upload your authentic signature and keep your account secure.
            </p>
        </div>
    </div>
</main>

<script>
function confirmDeleteSignature() {
    Swal.fire({
        title: 'Delete Signature?',
        text: 'Are you sure you want to delete your current signature? This action cannot be undone.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, Delete It',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            // Create a form and submit it
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '<?php echo BASE_URL; ?>profile/delete_signature';
            
            const csrfToken = document.createElement('input');
            csrfToken.type = 'hidden';
            csrfToken.name = 'csrf_token';
            csrfToken.value = '<?php echo generate_csrf_token(); ?>';
            
            form.appendChild(csrfToken);
            document.body.appendChild(form);
            form.submit();
        }
    });
}
</script>

<?php include __DIR__ . '/partials/toasts.php'; ?>
<?php include __DIR__ . '/footer.php'; ?>