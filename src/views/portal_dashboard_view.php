<?php
// This view is only ever loaded by the PortalController after a successful token validation.
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo generate_csrf_token(); ?>">
    <title>Licensee Portal - VARUNA System</title>
    
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>css/style.css">
    <link rel="stylesheet" type="text/css" href="<?php echo BASE_URL; ?>libs/datatables/datatables.min.css"/>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>libs/sweetalert2/sweetalert2.min.css">
    
    <script src="<?php echo BASE_URL; ?>js/jquery-3.7.1.min.js"></script>
    <script type="text/javascript" src="<?php echo BASE_URL; ?>libs/datatables/datatables.min.js"></script>
    <script src="<?php echo BASE_URL; ?>libs/sweetalert2/sweetalert2.min.js"></script>
    
    <script> const BASE_URL = "<?php echo BASE_URL; ?>"; </script>
</head>
<body>

    <header class="main-header">
         <div class="logo-container"><img src="<?php echo BASE_URL; ?>images/indian_railways_logo.png" alt="Logo" class="logo"></div>
        <div class="system-name-container">
            <h1 class="system-name">Licensee Portal</h1>
            <p class="system-subtitle">Welcome, <?php echo htmlspecialchars($_SESSION['licensee_name']); ?></p>
        </div>
    </header>

    <main class="page-container" style="padding: 20px 40px;">
        <div id="portal_content">
            <h2>Your Contracts</h2>
            <div id="contracts_list"></div>
            <hr style="margin: 30px 0;">
            <div id="staff_section" class="hidden">
                <h2 id="staff_header">Staff</h2>
                 <div style="margin-bottom: 15px;">
                    <button id="add_new_staff_btn" class="btn-login">Add New Staff</button>
                    <button id="bulk_print_btn" class="btn-login" style="background-image: linear-gradient(135deg, #31b0d5, #1e8cbe);">🖨️ Bulk Print IDs</button>
                    
                </div>
                <table id="portal_staff_table" class="display" style="width:100%">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Designation</th>
                            <th>Contact</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </main>
    
    <?php include __DIR__ . '/footer.php'; // Re-use the footer for modals ?>
    <script src="<?php echo BASE_URL; ?>js/pages/portal.js"></script>
</body>
</html>