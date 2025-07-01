<?php
if (!defined('VARUNA_ENTRY_POINT') || $_SESSION['role'] !== 'SCI') {
    require_once __DIR__ . '/errors/404.php';
    exit();
}
?>
<?php include __DIR__ . '/header.php'; ?>
<?php include __DIR__ . '/navbar.php'; ?>

<main class="page-container" style="padding: 20px 40px; position: relative;">
    <?php
    $has_signature = isset($_SESSION['signature_path']) && !empty($_SESSION['signature_path']);
    if (!$has_signature) {
        echo '
        <div class="feature-lock-overlay">
            <div class="feature-lock-overlay-content">
                <span class="lock-icon">🔒</span>
                <p>Functionality Locked</p>
                <span>Click for more info</span>
            </div>
        </div>';
    }
    ?>

    <h2>Staff Approval Management</h2>

    <div class="tab-container">
        <button class="tab-link active" data-tab="pending">Pending Approval</button>
        <button class="tab-link" data-tab="rejected">Rejected Staff</button>
        <button class="tab-link" data-tab="terminated">Terminated Staff</button>
    </div>

    <div id="pending" class="tab-content active">
        <h3>Pending Staff for Your Section</h3>
        
        <div style="margin-bottom: 15px; display: flex; gap: 10px;">
            <button id="bulk_approve_btn" class="btn-action approve" <?php if (!$has_signature) echo 'disabled'; ?>>✔ Approve Selected</button>
            <button id="bulk_reject_btn" class="btn-action reject" <?php if (!$has_signature) echo 'disabled'; ?>>✖ Reject Selected</button>
        </div>

        <table id="pending_staff_table" class="display" style="width:100%">
            <thead>
                <tr>
                    <th style="width: 20px;"><input type="checkbox" id="select_all_pending"></th>
                    <th>Staff ID</th>
                    <th>Name</th>
                    <th>Designation</th>
                    <th>Contract Name</th>
                    <th>Station</th>
                    <th>Actions</th>
                </tr>
            </thead>
        </table>
    </div>

    <div id="rejected" class="tab-content">
        <h3>Rejected Staff from Your Section</h3>
        <table id="rejected_staff_table" class="display" style="width:100%">
            <thead>
                <tr>
                    <th>Staff ID</th>
                    <th>Name</th>
                    <th>Designation</th>
                    <th>Remark</th>
                    <th>Rejected By</th>
                    <th>Actions</th>
                </tr>
            </thead>
        </table>
    </div>

    <div id="terminated" class="tab-content">
    <h3>Terminated Staff in Your Section</h3>
    <p>These staff members were terminated automatically or manually. You can edit their details to update documents and resubmit them for approval.</p>
    <table id="terminated_staff_table" class="display" style="width:100%">
        <thead>
            <tr>
                <th>Staff ID</th>
                <th>Name</th>
                <th>Designation</th>
                <th>Contract</th>
                <th>Actions</th>
            </tr>
        </thead>
    </table>
</div>
</main>

<script src="<?php echo BASE_URL; ?>js/pages/staffApproval.js"></script>
<?php include __DIR__ . '/footer.php'; ?>