<?php
require_once __DIR__ . '/../src/init.php';

// This function is now declared OUTSIDE the loop to prevent redeclaration errors.
function renderIdCard($staff_id_to_print, $pdo) {
    // This variable will be used inside id_card.php to hide the single print button.
    $is_bulk_print = true; 
    
    $_GET['staff_id'] = $staff_id_to_print;
    
    // The include will execute the id_card.php script within this function's scope
    include 'id_card.php';
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
            .card-grid { display: flex; flex-wrap: wrap; justify-content: center; gap: 20px; }
            .page-break { page-break-after: always; }
        }
        @media print {
            body { background-color: white; }
            .print-controls { display: none !important; }
            .page-break { page-break-after: always; }
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
        // We wrap each call in a div for layout and print control
        echo "<div class='page-break'>";
        renderIdCard($staff_id_to_print, $pdo);
        echo "</div>";
    }
    ?>
    </div>
</body>
</html>