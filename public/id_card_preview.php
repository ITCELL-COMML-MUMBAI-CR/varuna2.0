<?php
require_once __DIR__ . '/../src/init.php';

// --- Get styles from URL parameters and ensure the '#' is present ---
$styles = [];
$color_fields = [
    'bg_color',
    'vendor_name_color',
    'station_train_color',
    'nav_logo_bg_color',
    'nav_logo_font_color',
    'licensee_name_color',
    'instructions_color',
    'default_font_color',
    'border_color'
];

foreach ($color_fields as $field) {
    // If the color code from GET does not start with #, add it.
    $color = $_GET[$field] ?? '#FFFFFF'; // Default to white if not set
    if (strpos($color, '#') !== 0) {
        $color = '#' . $color;
    }
    $styles[$field] = $color;
}

// Dummy QR code for layout purposes
$qr_placeholder_html = '<div style="width:100%;height:100%;border:2px dashed #ccc;display:flex;align-items:center;justify-content:center;font-size:0.8rem;color:#888;">QR Code</div>';

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>ID Card Preview</title>
    <link href="<?php echo BASE_URL; ?>css/id_card_style.css" rel="stylesheet" type="text/css">
    <style>
        body {
            margin: 0;
            padding: 0;
            background-color: transparent;
        }

        /* Inject all the dynamic styles */
        #id,
        .id-1 {
            background-color:
                <?php echo htmlspecialchars($styles['bg_color']); ?>
                !important;
            border-color:
                <?php echo htmlspecialchars($styles['border_color']); ?>
                !important;
            color:
                <?php echo htmlspecialchars($styles['default_font_color']); ?>
                !important;
        }

        p,
        h2,
        h6 {
            color:
                <?php echo htmlspecialchars($styles['default_font_color']); ?>
                !important;
        }

        .vendor-name {
            color:
                <?php echo htmlspecialchars($styles['vendor_name_color']); ?>
                !important;
        }

        .station-name {
            color:
                <?php echo htmlspecialchars($styles['station_train_color']); ?>
                !important;
        }

        .nav-logo {
            background-color:
                <?php echo htmlspecialchars($styles['nav_logo_bg_color']); ?>
                !important;
        }

        .nav-logo h3 {
            color:
                <?php echo htmlspecialchars($styles['nav_logo_font_color']); ?>
                !important;
        }

        .nav-licensee h2 {
            color:
                <?php echo htmlspecialchars($styles['licensee_name_color']); ?>
                !important;
        }

        .container-id-2 center p {
            color:
                <?php echo htmlspecialchars($styles['instructions_color']); ?>
                !important;
        }
    </style>
</head>

<body>
    <div id="bg">
        <div id="id">
            <div class='header-logo'>
                <div class='idlogo'><img class='logo' src='<?php echo BASE_URL; ?>images/indian_railways_logo.png'></div>
                <div class='idhead'>
                    <div class='idhead-section'>
                        <h6>UNDER CONTRACTUAL OBLIGATIONS OF</h6>
                    </div>
                    <div class='idhead-section'>
                        <h3>CENTRAL RAILWAY</h3>
                    </div>
                    <div class='idhead-section'>
                        <h3>MUMBAI DIVISION</h3>
                    </div>
                </div>
            </div>
            <div class='nav-logo'>
                <h3><strong>DESIGNATION - XXXXXX</strong></h3>
            </div>
            <div class='nav-licensee'>
                <h2>LICENSEE NAME</h2>
            </div>
            <div class='container-id'><img class='profile-pic' src='<?php echo BASE_URL; ?>images/default_profile.png'>
                <p class='field-name'>Name</p>
                <p class='vendor-name'>STAFF NAME</p>
                <p class='field-name'>Station / Train</p>
                <p class='station-name'>STATION</p>
            </div>
            <div class='qr-container'>
                <div class='selfsign'>
                    <p>Holder's Signature</p>
                </div>
                <div class='qrimg'><?php echo $qr_placeholder_html; ?></div>
                <div class='authsign'>
                    <p>Issuing Authority<br>CCI SECTION</p>
                </div>
            </div>
        </div>
        <div class="id-1">
            <div class='bg-id'>
                <div class='qrimg2'><?php echo $qr_placeholder_html; ?></div>
            </div>
            <div class='container-id-2'>
                <center>
                    <p>Instructions</p>
                </center>
                <p>1) The Holder of this ID is not a regular Railway Employee.<br>2) The loss of this card should be
                    immediately reported...<br>...etc</p>
            </div>
        </div>
    </div>
</body>

</html>