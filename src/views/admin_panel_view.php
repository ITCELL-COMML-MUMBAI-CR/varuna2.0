<?php
if (!defined('VARUNA_ENTRY_POINT') || $_SESSION['role'] !== 'ADMIN') {
    require_once __DIR__ . '/errors/404.php';
    exit();
}

// This fetch is now only needed for the "Change Password" form
$users = $pdo->query("SELECT id, username FROM varuna_users ORDER BY username ASC")->fetchAll();
$contract_types_list = $pdo->query("SELECT ContractType FROM varuna_contract_types ORDER BY ContractType ASC")->fetchAll(PDO::FETCH_COLUMN);
$sections = $pdo->query("SELECT `name` FROM `Section` ORDER BY `name` ASC")->fetchAll(PDO::FETCH_COLUMN);
$department_sections = $pdo->query("SELECT DISTINCT `Section` FROM `varuna_contract_types` ORDER BY `Section` ASC")->fetchAll(PDO::FETCH_COLUMN);
?>
<?php include __DIR__ . '/header.php'; ?>
<?php include __DIR__ . '/navbar.php'; ?>

<main class="page-container" style="padding: 20px 40px;">
    <h2>Admin Panel</h2>
    <p>Manage core system data and settings.</p>

    <div class="tab-container" style="margin-top: 20px;">
        <button class="tab-link active" data-tab="tab_contract_types">Manage Contract Types</button>
        <button class="tab-link" data-tab="tab_designations">Manage Designations</button>
        <?php if ($_SESSION['section'] === 'IT CELL'): ?>
            <button class="tab-link" data-tab="tab_users">Manage Users</button>
        <?php endif; ?>
    </div>

    <div id="tab_contract_types" class="tab-content active">
        <div class="admin-accordion">
            <div class="accordion-item">
                <button class="accordion-header">Add New Contract Type</button>
                <div class="accordion-content">
                    <div class="accordion-body">
                        <form id="addContractTypeForm" action="<?php echo BASE_URL; ?>api/admin/add_contract_type.php"
                            method="POST">
                            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                            <div class="details-grid">
                                <div class="input-group"><label>Contract Type Name</label><input type="text"
                                        name="ContractType" required></div>
                                <div class="input-group"><label>Train/Station</label><select name="TrainStation"
                                        required>
                                        <option value="Station">Station</option>
                                        <option value="Train">Train</option>
                                    </select></div>
                                <div class="input-group"><label>Department Section</label><input type="text"
                                        name="Section" required placeholder="e.g., CATG, COG"></div>
                            </div>
                            <h4 style="margin-top:15px;">Required Documents (Y/N)</h4>
                            <div class="details-grid"
                                style="grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));">
                                <div class="input-group"><label>Police</label><select name="Police">
                                        <option value="Y">Yes</option>
                                        <option value="N" selected>No</option>
                                    </select></div>
                                <div class="input-group"><label>Medical</label><select name="Medical">
                                        <option value="Y">Yes</option>
                                        <option value="N" selected>No</option>
                                    </select></div>
                                <div class="input-group"><label>TA</label><select name="TA">
                                        <option value="Y">Yes</option>
                                        <option value="N" selected>No</option>
                                    </select></div>
                                <div class="input-group"><label>PPO</label><select name="PPO">
                                        <option value="Y">Yes</option>
                                        <option value="N" selected>No</option>
                                    </select></div>
                                <div class="input-group"><label>FSSAI</label><select name="FSSAI">
                                        <option value="Y">Yes</option>
                                        <option value="N" selected>No</option>
                                    </select></div>
                                <div class="input-group"><label>Fire Safety</label><select name="FireSafety">
                                        <option value="Y">Yes</option>
                                        <option value="N" selected>No</option>
                                    </select></div>
                                <div class="input-group"><label>Pest Control</label><select name="PestControl">
                                        <option value="Y">Yes</option>
                                        <option value="N" selected>No</option>
                                    </select></div>
                                <div class="input-group"><label>RailNeer</label><select name="RailNeerAvailability">
                                        <option value="Y">Yes</option>
                                        <option value="N" selected>No</option>
                                    </select></div>
                                <div class="input-group"><label>Water Safety</label><select name="WaterSafety">
                                        <option value="Y">Yes</option>
                                        <option value="N" selected>No</option>
                                    </select></div>
                            </div>
                            <button type="submit" class="btn-login" style="margin-top: 20px;">Save Contract
                                Type</button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="accordion-item">
                <button class="accordion-header">View Existing Contract Types</button>
                <div class="accordion-content">
                    <div class="accordion-body">
                        <table id="contractTypesTable" class="display" style="width:100%">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Type</th>
                                    <th>Department</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="tab_designations" class="tab-content">
        <div class="admin-accordion">
            <div class="accordion-item">
                <button class="accordion-header">Add New Staff Designation</button>
                <div class="accordion-content">
                    <div class="accordion-body">
                        <form id="addDesignationForm" action="<?php echo BASE_URL; ?>api/admin/add_designation.php"
                            method="POST">
                            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                            <div class="input-group"><label>Designation Name</label><input type="text"
                                    name="designation_name" required></div>
                            <button type="submit" class="btn-login" style="margin-top: 20px;">Save Designation</button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="accordion-item">
                <button class="accordion-header">View Existing Designations</button>
                <div class="accordion-content">
                    <div class="accordion-body">
                        <table id="designationsTable" class="display" style="width:100%">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Designation Name</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if ($_SESSION['section'] === 'IT CELL'): ?>
        <div id="tab_users" class="tab-content">
            <div class="admin-accordion">
                <div class="accordion-item">
                    <button class="accordion-header">Add New User</button>
                    <div class="accordion-content">
                        <div class="accordion-body">
                            <form id="addNewUserForm" action="<?php echo BASE_URL; ?>api/admin/add_user.php" method="POST">
                                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                <div class="details-grid">
                                    <div class="input-group"><label>Username</label><input type="text" name="username"
                                            required></div>
                                    <div class="input-group"><label>Password</label><input type="password" name="password"
                                            required></div>
                                    <div class="input-group"><label>Role</label>
                                        <select name="role" required>
                                            <option value="ADMIN">ADMIN</option>
                                            <option value="SCI">SCI</option>
                                            <option value="VIEWER" selected>VIEWER</option>
                                        </select>
                                    </div>
                                    <div class="input-group"><label>Designation</label><input type="text"
                                            name="designation"></div>
                                    <div class="input-group"><label>Geographical Section</label><select name="section">
    <option value="">-- Select Section --</option>
    <option value="Train">Train</option>
    <?php foreach ($sections as $section): ?>
        <option value="<?php echo htmlspecialchars($section); ?>"><?php echo htmlspecialchars($section); ?></option>
    <?php endforeach; ?>
</select></div>
<div class="input-group"><label>Department Section</label><select name="department_section">
    <option value="">-- Select Department --</option>
    <?php foreach ($department_sections as $dept_section): ?>
        <option value="<?php echo htmlspecialchars($dept_section); ?>"><?php echo htmlspecialchars($dept_section); ?></option>
    <?php endforeach; ?>
</select></div>
                                </div>
                                <button type="submit" class="btn-login" style="margin-top: 20px;">Create User</button>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="accordion-item">
                    <button class="accordion-header">Change User Password</button>
                    <div class="accordion-content">
                        <div class="accordion-body">
                            <form id="changePasswordForm" action="<?php echo BASE_URL; ?>api/admin/change_password.php"
                                method="POST">
                                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                <div class="input-group"><label>Select User</label><select name="user_id" required>
                                        <option value="">-- Select User --</option><?php foreach ($users as $user): ?>
                                            <option value="<?php echo $user['id']; ?>">
                                                <?php echo htmlspecialchars($user['username']); ?></option><?php endforeach; ?>
                                    </select></div>
                                <div class="input-group"><label>New Password</label><input type="password"
                                        name="new_password" required></div>
                                <button type="submit" class="btn-login" style="margin-top: 20px;">Change Password</button>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="accordion-item">
                    <button class="accordion-header">View System Users</button>
                    <div class="accordion-content">
                        <div class="accordion-body">
                            <table id="usersTable" class="display" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Username</th>
                                        <th>Role</th>
                                        <th>Section</th>
                                        <th>Dept. Section</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</main>

<?php include __DIR__ . '/partials/toasts.php'; ?>
<script src="<?php echo BASE_URL; ?>js/pages/adminPanel.js"></script>
<?php include __DIR__ . '/footer.php'; ?>