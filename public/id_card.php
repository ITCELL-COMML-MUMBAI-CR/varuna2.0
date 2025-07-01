<?php
/**
 * VARUNA System - ID Card Generator
 * This file now acts as a controller to fetch data and render the template.
 */
require_once __DIR__ . '/../src/init.php';

// The QR Code generation functions are now moved to `qr_generator.php` and included via `init.php`
// This makes them globally available and avoids re-declaration issues.

// 1. Get Staff ID and Fetch Data
$staff_id = trim($_GET['staff_id'] ?? '');
if (empty($staff_id)) {
    die("Error: A valid Staff ID is required to generate the card.");
}

$card_data = get_staff_card_data($pdo, $staff_id);

if (!$card_data) {
    die("Error: Could not retrieve data for the specified staff ID. The staff member may not exist or is not approved.");
}

// 2. Render the Card
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ID Card - <?php echo htmlspecialchars($card_data['staff']['name']); ?></title>
    <link href="<?php echo BASE_URL; ?>css/id_card_style.css" rel="stylesheet" type="text/css">
</head>
<body>

    <?php 
    // The template file expects the data in a variable named $card_data
    include 'id_card_template.php'; 
    ?>

    <?php if (empty($is_bulk_print)): ?>
        <div class="print-container">
            <button class="btn-login" onclick="printIdCard()">🖨️ Print ID Card</button>
        </div>

        <script>
            function printIdCard() {
                const printableArea = document.getElementById('printableArea');
                if (printableArea) {
                    const printContents = printableArea.innerHTML;
                    const originalContents = document.body.innerHTML;
                    document.body.innerHTML = printContents;
                    window.print();
                    document.body.innerHTML = originalContents;
                    location.reload(); // Reload to re-initialize scripts
                }
            }
        </script>
    <?php endif; ?>

</body>
</html>