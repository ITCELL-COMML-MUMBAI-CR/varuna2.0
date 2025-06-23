<?php
// Security check
if (!defined('VARUNA_ENTRY_POINT')) {
    // Show the 404 page instead, as this file should not be directly accessible
    require_once __DIR__ . '/errors/404.php';
    exit();
}
$error_message = $_SESSION['error_message'] ?? '';
$success_message = $_SESSION['success_message'] ?? '';
$old_input = $_SESSION['old_input'] ?? [];

unset($_SESSION['error_message']);
unset($_SESSION['success_message']);
unset($_SESSION['old_input']);
?>
<?php include __DIR__ . '/header.php'; ?>
<?php include __DIR__ . '/navbar.php'; ?>


<main class="form-container" style="padding: 40px; display: flex; justify-content: center;">
    <div class="form-box" style="width: 100%; max-width: 500px; background: #fff; padding: 30px; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.1);">
        <h2 style="text-align: center; margin-bottom: 25px; color: var(--primary-color);">Add New Licensee</h2>
        
        <form action="<?php echo BASE_URL; ?>licensees/add" method="POST" class="styled-form" id="addLicenseeForm" novalidate>
            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">

            <div class="input-group">
                <input type="text" id="name" name="name" required>
                <label for="name">Licensee Name</label>
            </div>

            <div class="input-group" style="margin-top: 25px;">
                <input type="text" id="mobile_number" name="mobile_number" required maxlength="10">
                <label for="mobile_number">Mobile Number</label>
                <span class="error-text" id="mobile_error"></span>
            </div>
            
            

            <button type="submit" class="btn-login" style="margin-top: 30px; width: 100%;">
                <span>Add Licensee</span>
            </button>
        </form>
    </div>
</main>
<?php include __DIR__ . '/partials/toasts.php'; ?>

<script src="<?php echo BASE_URL; ?>js/pages/licenseeForm.js"></script>

<?php include __DIR__ . '/footer.php'; ?>