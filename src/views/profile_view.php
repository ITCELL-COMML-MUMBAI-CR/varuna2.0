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
<main class="page-container" style="padding: 20px 40px;">
    <h2>My Profile</h2>
    <div class="form-box" style="max-width: 500px;">
        <form action="<?php echo BASE_URL; ?>profile/upload_signature" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
            <div class="input-group">
                <label>Update Your Signature Image</label>
                <?php if ($current_signature): ?>
                    <p>Current Signature:</p>
                    <img src="<?php echo BASE_URL . 'uploads/authority/' . $current_signature; ?>" style="max-width: 200px; border: 1px solid #ddd; margin-bottom: 10px;">
                <?php endif; ?>
                <input type="file" name="signature_file" required accept="image/png, image/jpeg">
            </div>
            <button type="submit" class="btn-login" style="margin-top: 20px;">Upload Signature</button>
        </form>
    </div>
</main>
<?php include __DIR__ . '/partials/toasts.php'; ?>
<?php include __DIR__ . '/footer.php'; ?>