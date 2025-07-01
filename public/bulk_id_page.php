<?php
require_once __DIR__ . '/../src/init.php';

// This function now fetches data and includes the template, ensuring no data conflicts.
function renderIdCard($staff_id, $pdo) {
    $card_data = get_staff_card_data($pdo, $staff_id);
    
    if ($card_data) {
        // The template uses the $card_data variable to render the ID card.
        include 'id_card_template.php';
    } else {
        echo "<div class='page-break' style='text-align:center; padding: 20px; color: red;'>Could not load data for Staff ID: " . htmlspecialchars($staff_id) . "</div>";
    }
}

$filter_by = $_GET['filter_by'] ?? '';
$filter_value = $_GET['filter_value'] ?? '';

if (empty($filter_by) || empty($filter_value)) {
    die("Error: Required filter parameters are missing.");
}

$staff_ids = getStaffIdsForBulkPrint($pdo, $filter_by, $filter_value);

if (empty($staff_ids)) {
    die("No approved staff found for the selected filter. Please go back and check your selection.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Bulk ID Card Print</title>
    <link href="<?php echo BASE_URL; ?>css/id_card_style.css" rel="stylesheet" type="text/css">
    <style>
        @media screen {
            body { display: flex; flex-direction: column; align-items: center; background-color: #ccc; }
            .print-controls { text-align: center; margin: 20px; padding: 15px; background: white; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
            .card-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; }
            #printableArea { width: auto; height: auto; float: none; }
        }
        @media print {
            body { background-color: white; }
            .print-controls { display: none !important; }
            .card-grid { display: block; }
            .card-wrapper { page-break-after: always; }
        }
    </style>
</head>
<body>
    <div class="print-controls">
        <h3>Bulk Print Preview</h3>
        <p>Found <?php echo count($staff_ids); ?> card(s). Use your browser's print function (Ctrl+P or Cmd+P) to print.</p>
        <button class="btn-login" onclick="window.print()">🖨️ Print All Cards</button>
    </div>
    <div class="card-grid">
    <?php
    foreach ($staff_ids as $staff_id_to_print) {
        echo "<div class='card-wrapper'>";
        renderIdCard($staff_id_to_print, $pdo);
        echo "</div>";
    }
    ?>
    </div>
</body>
</html>