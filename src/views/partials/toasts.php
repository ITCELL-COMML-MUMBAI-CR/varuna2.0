<?php
// This file is designed to be included in a view.
// It assumes the view has already processed the session and defined these variables.

// Security check
if (!defined('VARUNA_ENTRY_POINT')) {
    // Show the 404 page instead, as this file should not be directly accessible
    require_once __DIR__ . '/../errors/404.php';
    exit();
}
?>

<script>
    // Check for a success message from the session
    <?php if (!empty($success_message)): ?>
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'success',
            title: '<?php echo addslashes($success_message); ?>',
            showConfirmButton: false,
            timer: 3000, // 3 seconds
            timerProgressBar: true,
            didOpen: (toast) => {
                toast.addEventListener('mouseenter', Swal.stopTimer)
                toast.addEventListener('mouseleave', Swal.resumeTimer)
            }
        });
    <?php endif; ?>

    // Check for an error message from the session
    <?php if (!empty($error_message)): ?>
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'error',
            title: '<?php echo addslashes($error_message); ?>',
            showConfirmButton: false,
            timer: 5000, // Give more time for users to read errors
            timerProgressBar: true,
            didOpen: (toast) => {
                toast.addEventListener('mouseenter', Swal.stopTimer)
                toast.addEventListener('mouseleave', Swal.resumeTimer)
            }
        });
    <?php endif; ?>
</script>