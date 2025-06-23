<?php
// Security check to prevent direct access to this file
if (!defined('VARUNA_ENTRY_POINT')) {
    die('Direct access not allowed.');
}
?>
<?php include __DIR__ . '/header.php'; ?>
<?php include __DIR__ . '/navbar.php'; ?>

<main class="login-container">
    <div class="login-box">
        <form action="<?php echo BASE_URL; ?>login" method="POST" class="login-form">
            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
            
            <?php 
            if (!empty($login_error)) {
                echo '<p class="error-message" style="color: red; text-align: center;">' . htmlspecialchars($login_error) . '</p>';
            }
            ?>
            <div class="input-group">
                <input type="text" id="username" name="username" required>
                <label for="username">Username</label>
            </div>
            <div class="input-group">
                <input type="password" id="password" name="password" required>
                <label for="password">Password</label>
            </div>
            <button type="submit" class="btn-login"><span>Login</span></button>
        </form>
    </div>
</main>

<?php include __DIR__ . '/footer.php'; ?>