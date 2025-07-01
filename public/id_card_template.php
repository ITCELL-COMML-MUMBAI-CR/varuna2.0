<?php
/**
 * VARUNA System - ID Card HTML Template
 * This file contains only the presentation logic for the ID card.
 * It expects $card_data to be pre-populated.
 */

// Extract variables for easier use in the template
$staff = $card_data['staff'];
$auth_sig_path = $card_data['auth_sig_path'];
$styles = $card_data['styles'];

// Determine CSS classes for long names
$name_length = strlen($staff['name']);
$name_class = '';
if ($name_length > 20) {
    $name_class = 'very-long-name';
} elseif ($name_length > 15) {
    $name_class = 'long-name';
}
?>
<div id="printableArea">
    <div id="bg">
        <div id="id" style="<?php if ($styles) echo "background-color: {$styles['bg_color']} !important; border-color: {$styles['border_color']} !important; color: {$styles['default_font_color']} !important;"; ?>">
            <div class='header-logo'>
                <div class='idlogo'><img class='logo' src='<?php echo BASE_URL; ?>images/indian_railways_logo.png'></div>
                <div class='idhead'>
                    <div class='idhead-section'><h6 style="<?php if ($styles) echo "color: {$styles['default_font_color']} !important;"; ?>">UNDER CONTRACTUAL OBLIGATIONS OF</h6></div>
                    <div class='idhead-section'><h3 style="<?php if ($styles) echo "color: {$styles['default_font_color']} !important;"; ?>">CENTRAL RAILWAY</h3></div>
                    <div class='idhead-section'><h3 style="<?php if ($styles) echo "color: {$styles['default_font_color']} !important;"; ?>">MUMBAI DIVISION</h3></div>
                </div>
            </div>
            <div class='nav-logo' style="<?php if ($styles) echo "background-color: {$styles['nav_logo_bg_color']} !important;"; ?>">
                <h3 style="<?php if ($styles) echo "color: {$styles['nav_logo_font_color']} !important;"; ?>">
                    <strong><?php echo htmlspecialchars($staff['designation']); ?> ID - <?php echo htmlspecialchars($staff['id']); ?></strong>
                </h3>
            </div>
            <div class='nav-licensee'>
                <h2 style="<?php if ($styles) echo "color: {$styles['licensee_name_color']} !important;"; ?>"><?php echo htmlspecialchars($staff['licensee_name']); ?></h2>
            </div>
            <div class='container-id'>
                <img class='profile-pic' src='<?php echo BASE_URL . "uploads/staff/" . htmlspecialchars($staff['profile_image']); ?>'>
                <p class='field-name' style="<?php if ($styles) echo "color: {$styles['default_font_color']} !important;"; ?>">Name</p>
                <p class='vendor-name <?php echo $name_class; ?>' style="<?php if ($styles) echo "color: {$styles['vendor_name_color']} !important;"; ?>"><?php echo htmlspecialchars($staff['name']); ?></p>
                <p class='field-name' style="<?php if ($styles) echo "color: {$styles['default_font_color']} !important;"; ?>">Station / Train</p>
                <p class='station-name' style="<?php if ($styles) echo "color: {$styles['station_train_color']} !important;"; ?>"><?php echo htmlspecialchars($staff['station_code']); ?></p>
            </div>
            <div class='qr-container'>
                <div class='selfsign'>
                    <img class='selfimg' src='<?php echo BASE_URL . "uploads/staff/" . htmlspecialchars($staff['signature_image']); ?>'>
                    <p style="<?php if ($styles) echo "color: {$styles['default_font_color']} !important;"; ?>">Holder's Signature</p>
                </div>
                <div class='qrimg'><?php echo generateQR($staff['id']); ?></div>
                <div class='authsign'>
                    <img class='authimg' src='<?php echo BASE_URL . "uploads/authority/" . htmlspecialchars($auth_sig_path); ?>'>
                    <p style="<?php if ($styles) echo "color: {$styles['default_font_color']} !important;"; ?>">
                        Issuing Authority <br>
                        <?php echo $staff['section_code'] == 'TRAIN' ? 'ACM CP' : 'CCI ' . htmlspecialchars($staff['section_code']); ?>
                    </p>
                </div>
            </div>
        </div>
        <div class="id-1" style="<?php if ($styles) echo "background-color: {$styles['bg_color']} !important; border-color: {$styles['border_color']} !important; color: {$styles['default_font_color']} !important;"; ?>">
            <div class='bg-id'><div class='qrimg2'><?php echo generateQR($staff['id']); ?></div></div>
            <div class='container-id-2'>
                <center><p style='margin-bottom:5px;font-size: 1rem;font-weight: 1000; <?php if ($styles) echo "color: {$styles['instructions_color']} !important;"; else echo "color:#CF5C36"; ?>'>Instructions</p></center>
                <p style='margin:2px;font-size: 0.8rem;text-align:left; <?php if ($styles) echo "color: {$styles['default_font_color']} !important;"; ?>'>
                    1) The Holder of this ID is not a regular Railway Employee.<br>
                    2) The loss of this card should be immediately reported to card issuing office.<br>
                    3) It is only valid for specified Station or Train.<br>
                    4) This ID card is not Transferable.<br>
                    5) For more details scan QR.
                </p>
            </div>
        </div>
    </div>
</div> 