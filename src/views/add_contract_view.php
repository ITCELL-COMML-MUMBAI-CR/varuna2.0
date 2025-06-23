<?php
/**
 * View for Adding a New Contract (with dynamic fields)
 * Current Time: Tuesday, June 17, 2025 at 12:12 PM IST
 * Location: Kalyan, Maharashtra, India
 */

// Security check
if (!defined('VARUNA_ENTRY_POINT')) {
    require_once __DIR__ . '/errors/404.php';
    exit();
}

// Get data from session and then clear it
$error_message = $_SESSION['error_message'] ?? '';
$success_message = $_SESSION['success_message'] ?? '';
$old_input = $_SESSION['old_input'] ?? [];
$invalid_fields = $_SESSION['invalid_fields'] ?? [];

unset($_SESSION['error_message']);
unset($_SESSION['success_message']);
unset($_SESSION['old_input']);
unset($_SESSION['invalid_fields']);

// Fetch all necessary data for dropdowns
$licensees = $pdo->query("SELECT id, name FROM varuna_licensee WHERE status = 'active' ORDER BY name ASC")->fetchAll();
$sections = $pdo->query("SELECT Section_Code, Name FROM Section ORDER BY Name ASC")->fetchAll();
$contract_types = $pdo->query("SELECT * FROM varuna_contract_types")->fetchAll(PDO::FETCH_ASSOC);
$trains = $pdo->query("SELECT train_number, train_name FROM trains ORDER BY train_number ASC")->fetchAll();

// Prepare contract types data for JavaScript
$contract_docs_json = json_encode(array_column($contract_types, null, 'ContractType'));
?>
<?php include __DIR__ . '/header.php'; ?>
<?php include __DIR__ . '/navbar.php'; ?>

<main class="form-container" style="padding: 40px; display: flex; justify-content: center;">
    <div class="form-box" style="width: 100%; max-width: 800px; background: #fff; padding: 30px; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.1);">
        <h2 style="text-align: center; margin-bottom: 25px; color: var(--primary-color);">Add New Contract</h2>

        <form action="<?php echo BASE_URL; ?>contracts/add" method="POST" enctype="multipart/form-data" novalidate>
            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
            
            <div class="details-grid">
                <div class="input-group">
                    <label for="contract_type">Contract Type</label>
                    <select id="contract_type" name="contract_type" class="<?php echo in_array('contract_type', $invalid_fields) ? 'is-invalid' : ''; ?>" required data-docs='<?php echo htmlspecialchars($contract_docs_json, ENT_QUOTES, 'UTF-8'); ?>'>
                        <option value="">-- Select Type --</option>
                        <?php foreach ($contract_types as $type): ?>
                            <option value="<?php echo htmlspecialchars($type['ContractType']); ?>" <?php echo (isset($old_input['contract_type']) && $old_input['contract_type'] == $type['ContractType']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($type['ContractType']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="input-group">
                    <label for="contract_name">Contract Name</label>
                    <input type="text" id="contract_name" name="contract_name" class="<?php echo in_array('contract_name', $invalid_fields) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($old_input['contract_name'] ?? ''); ?>" required>
                </div>
            </div>

            <div id="station_fields_container" class="hidden">
                <div class="details-grid">
                    <div class="input-group">
                        <label for="section_code">Section</label>
                        <select id="section_code" name="section_code" class="<?php echo in_array('section_code', $invalid_fields) ? 'is-invalid' : ''; ?>">
                            <option value="">-- Select Section --</option>
                            <?php foreach ($sections as $section): ?>
                                <option value="<?php echo htmlspecialchars($section['Section_Code']); ?>" <?php echo (isset($old_input['section_code']) && $old_input['section_code'] == $section['Section_Code']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($section['Name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="input-group">
                        <label for="station_code">Station</label>
                        <select id="station_code" name="station_code" disabled data-old-value="<?php echo htmlspecialchars($old_input['station_code'] ?? ''); ?>" class="<?php echo in_array('station_code', $invalid_fields) ? 'is-invalid' : ''; ?>">
                            <option value="">-- Select Section First --</option>
                        </select>
                    </div>
                </div>
            </div>

            <div id="train_fields_container" class="hidden">
                <div class="input-group">
                    <label for="train_select">Select Train(s) (type to search)</label>
                    <select id="train_select" name="trains[]" multiple="multiple" style="width: 100%;">
                        <?php foreach ($trains as $train): ?>
                            <option value="<?php echo htmlspecialchars($train['train_number']); ?>"><?php echo htmlspecialchars($train['train_number'] . ' - ' . $train['train_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="details-grid" style="margin-top: 20px;">
                <div class="input-group">
                    <label for="licensee_id">Licensee Name</label>
                    <select id="licensee_id" name="licensee_id" class="<?php echo in_array('licensee_id', $invalid_fields) ? 'is-invalid' : ''; ?>" required>
                        <option value="">-- Select Licensee --</option>
                        <?php foreach ($licensees as $licensee): ?>
                            <option value="<?php echo htmlspecialchars($licensee['id']); ?>" <?php echo (isset($old_input['licensee_id']) && $old_input['licensee_id'] == $licensee['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($licensee['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                 <div class="input-group">
                    <label>Location</label>
                    <input type="text" name="location" class="<?php echo in_array('location', $invalid_fields) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($old_input['location'] ?? ''); ?>" required>
                </div>
                <div class="input-group">
                    <label>Stalls</label>
                    <input type="number" name="stalls" class="<?php echo in_array('stalls', $invalid_fields) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($old_input['stalls'] ?? ''); ?>" required>
                </div>
                <div class="input-group">
                    <label>License Fee</label>
                    <input type="text" name="license_fee" class="<?php echo in_array('license_fee', $invalid_fields) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($old_input['license_fee'] ?? ''); ?>" required>
                </div>
                <div class="input-group">
                    <label>Period</label>
                    <input type="text" name="period" class="<?php echo in_array('period', $invalid_fields) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($old_input['period'] ?? ''); ?>" required>
                </div>
                <div class="input-group">
                    <label for="status">Status</label>
                    <select id="status" name="status" class="<?php echo in_array('status', $invalid_fields) ? 'is-invalid' : ''; ?>" required>
                        <option value="Regular" <?php echo (isset($old_input['status']) && $old_input['status'] == 'Regular') ? 'selected' : ''; ?>>Regular</option>
                        <option value="Under extension" <?php echo (isset($old_input['status']) && $old_input['status'] == 'Under extension') ? 'selected' : ''; ?>>Under extension</option>
                        <option value="Expired" <?php echo (isset($old_input['status']) && $old_input['status'] == 'Expired') ? 'selected' : ''; ?>>Expired</option>
                        <option value="Terminated" <?php echo (isset($old_input['status']) && $old_input['status'] == 'Terminated') ? 'selected' : ''; ?>>Terminated</option>
                    </select>
                </div>
            </div>

            <h3 style="margin-top: 30px; border-bottom: 2px solid var(--primary-color); padding-bottom: 5px;">Required Documents</h3>
            <div id="document_fields" style="margin-top: 15px;" class="details-grid">
                </div>

            <div class="details-grid" style="margin-top: 30px;">
                <div class="grid-full-width" style="text-align: center;">
                    <button type="submit" class="btn-login">Add Contract</button>
                </div>
            </div>
        </form>
    </div>
</main>

<?php include __DIR__ . '/partials/toasts.php'; ?>
<script src="<?php echo BASE_URL; ?>js/pages/contractForm.js"></script>
<?php include __DIR__ . '/footer.php'; ?>