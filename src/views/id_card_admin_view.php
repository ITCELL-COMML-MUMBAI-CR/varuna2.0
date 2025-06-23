<?php
if (!defined('VARUNA_ENTRY_POINT') || $_SESSION['role'] !== 'ADMIN') {
    require_once __DIR__ . '/errors/404.php';
    exit();
}
$contract_types = $pdo->query("SELECT ContractType FROM varuna_contract_types ORDER BY ContractType ASC")->fetchAll(PDO::FETCH_COLUMN);
?>
<?php include __DIR__ . '/header.php'; ?>
<?php include __DIR__ . '/navbar.php'; ?>

<style>
    .admin-container {
        display: flex;
        flex-wrap: wrap;
        gap: 150px;
        align-items: flex-start;
    }

    .style-controls {
        flex: 1;
        min-width: 450px;
    }

    .preview-container {
        flex: 2;
        min-width: 620px;
        position: sticky;
        top: 20px;
    }

    .preview-iframe {
        /* Corrected width and height to show both cards */
        width: 650px;
        height: 500px;
        border: 2px solid #ccc;
        border-radius: 5px;
        background-color: #fff;
    }

    .control-group {
        margin-bottom: 15px;
    }

    .control-group label {
        font-weight: 500;
        margin-bottom: 5px;
        display: block;
    }

    .control-group input[type="color"] {
        width: 100%;
        height: 40px;
        border: 1px solid #ccc;
        border-radius: 5px;
        cursor: pointer;
    }
</style>

<main class="page-container" style="padding: 20px 40px;">
    <h2>ID Card Template Customization</h2>
    <div class="admin-container">
        <div class="style-controls">
            <form id="style_form" action="<?php echo BASE_URL; ?>api/save_id_styles.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                <div class="input-group">
                    <label><b>Select Contract Type to Style</b></label>
                    <select name="contract_type" id="style_contract_type" required>
                        <option value="">-- Select --</option>
                        <?php foreach ($contract_types as $type): ?>
                            <option value="<?php echo htmlspecialchars($type); ?>"><?php echo htmlspecialchars($type); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div id="style_form_fields" class="hidden">
                    <div class="details-grid" style="grid-template-columns: 1fr 1fr 1fr;">
                        <div class="control-group"><label>Border Color</label><input type="color" name="border_color">
                        </div>
                        <div class="control-group"><label>Background Color</label><input type="color" name="bg_color">
                        </div>
                        <div class="control-group"><label>Designation Bar BG</label><input type="color"
                                name="nav_logo_bg_color"></div>
                        <div class="control-group"><label>Designation Font</label><input type="color"
                                name="nav_logo_font_color"></div>
                        <div class="control-group"><label>Licensee Name Font</label><input type="color"
                                name="licensee_name_color"></div>
                        <div class="control-group"><label>Staff Name Font</label><input type="color"
                                name="vendor_name_color"></div>
                        <div class="control-group"><label>Station/Train Font</label><input type="color"
                                name="station_train_color"></div>
                        <div class="control-group"><label>Instructions Title</label><input type="color"
                                name="instructions_color"></div>
                        <div class="control-group"><label>Default Text Color</label><input type="color"
                                name="default_font_color"></div>
                    </div>
                    <button type="submit" class="btn-login" style="margin-top: 20px;">Save Style</button>
                </div>
            </form>
        </div>
        <div class="preview-container">
            <iframe id="id_preview_iframe" class="preview-iframe" title="ID Card Preview"></iframe>
        </div>
    </div>
</main>

<?php include __DIR__ . '/partials/toasts.php'; ?>
<script src="<?php echo BASE_URL; ?>js/pages/idCardAdmin.js"></script>
<?php include __DIR__ . '/footer.php'; ?>