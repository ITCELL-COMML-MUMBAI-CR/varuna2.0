<?php
if (!defined('VARUNA_ENTRY_POINT') || !isset($_SESSION['user_id'])) {
    require_once __DIR__ . '/errors/404.php';
    exit();
}

// Fetch active contracts for the initial dropdown
$contracts = $pdo->query("SELECT id, contract_name, station_code FROM contracts WHERE status = 'Regular' ORDER BY contract_name ASC")->fetchAll();
$designations = $pdo->query("SELECT designation_name FROM varuna_staff_designation ORDER BY designation_name ASC")->fetchAll();


// Session data handling for PRG pattern
$error_message = $_SESSION['error_message'] ?? '';
$success_message = $_SESSION['success_message'] ?? '';
$old_input = $_SESSION['old_input'] ?? [];
$invalid_fields = $_SESSION['invalid_fields'] ?? [];
unset($_SESSION['error_message'], $_SESSION['success_message'], $_SESSION['old_input'], $_SESSION['invalid_fields']);
?>
<?php include __DIR__ . '/header.php'; ?>
<?php include __DIR__ . '/navbar.php'; ?>

<main class="page-container" style="padding: 20px 40px;">

    <div class="selection-box" style="margin-bottom: 20px;">
        <h2 style="margin-bottom: 15px;">Staff Onboarding</h2>
        <div class="input-group" style="max-width: 500px;">
            <label for="contract_selector">Select a Contract to Add/View Staff</label>
            <select id="contract_selector">
                <option value="">-- Select Contract --</option>
                <?php foreach ($contracts as $contract): ?>
                    <option value="<?php echo $contract['id']; ?>">
                        <?php echo htmlspecialchars($contract['contract_name'] . " (" . $contract['station_code'] . ")"); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <div id="content_container" class="hidden">
        <div id="contract_details_container" class="details-card hidden">
            <div id="contract_details_section" class="info-grid"></div>
        </div>
        <div id="existing_staff_section" style="margin-top: 20px;">
            <h3>Existing Staff</h3>
            <div class="table-container">
                <table id="staff_table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Designation</th>
                            <th>Contact</th>
                            <th>Aadhar Number</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="form-box" style="margin-top: 30px;">
            <h2 style="text-align: center; margin-bottom: 25px;">Add New Staff</h2>
            <form id="addStaffForm" action="<?php echo BASE_URL; ?>staff/add" method="POST"
                enctype="multipart/form-data" novalidate>
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                <input type="hidden" id="contract_id_field" name="contract_id" value="">

                <div class="details-grid">
                    <div class="input-group"><label>Full Name</label><input type="text" id="staff_name" name="name"
                            required><span class="validation-warning" id="name_warning"></span></div>
                    <div class="input-group">
                        <label for="designation">Designation</label>
                        <select id="designation" name="designation" required
                            class="<?php echo in_array('designation', $invalid_fields) ? 'is-invalid' : ''; ?>">
                            <option value="">-- Select Designation --</option>
                            <?php foreach ($designations as $desg): ?>
                                <option value="<?php echo htmlspecialchars($desg['designation_name']); ?>" <?php echo (isset($old_input['designation']) && $old_input['designation'] == $desg['designation_name']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($desg['designation_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="input-group"><label>Contact Number</label><input type="text" name="contact" required
                            maxlength="10"></div>
                    <div class="input-group"><label>Aadhar Card Number (Optional)</label><input type="text"
                            id="adhar_number" name="adhar_card_number" maxlength="12"><span class="validation-warning"
                            id="adhar_warning"></span></div>
                </div>

                <div id="staff_documents_container" class="details-grid" style="margin-top:20px;"></div>

                <div class="details-grid" style="margin-top:20px;">
                    <div class="input-group">
                        <label>Profile Image (Required)</label>
                        <input type="file" name="profile_image" required accept="image/*">
                    </div>

                    <div class="input-group">
                        <label>Signature Image (Required)</label>
                        <input type="file" name="signature_image" required accept="image/*">
                    </div>
                </div>

                <div class="details-grid" style="margin-top: 30px;">
                    <div class="grid-full-width" style="text-align: center;">
                        <button type="submit" class="btn-login">Add Staff Member</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</main>

<div id="staff_details_modal" class="modal-overlay hidden">
    <div class="modal-content">
        <button class="modal-close-btn">&times;</button>
        <div id="modal_body"></div>
    </div>
</div>


<?php include __DIR__ . '/partials/toasts.php'; ?>
<script src="<?php echo BASE_URL; ?>js/pages/staffOnboarding.js"></script>
<?php include __DIR__ . '/footer.php'; ?>