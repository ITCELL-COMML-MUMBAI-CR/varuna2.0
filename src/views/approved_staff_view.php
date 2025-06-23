<?php
if (!defined('VARUNA_ENTRY_POINT') || !in_array($_SESSION['role'], ['ADMIN', 'SCI'])) {
    require_once __DIR__ . '/errors/404.php';
    exit();
}
?>
<?php include __DIR__ . '/header.php'; ?>
<?php include __DIR__ . '/navbar.php'; ?>
<main class="page-container" style="padding: 20px 40px;">
    <h2>Approved Staff List</h2>
    <p>View all approved staff in your section and generate their ID cards.</p>
    <div class="table-container" style="margin-top: 20px;">
        <table id="approved_staff_table" class="display" style="width:100%">
            <thead>
                <tr>
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
</main>
<script src="<?php echo BASE_URL; ?>js/pages/approvedStaff.js"></script>
<?php include __DIR__ . '/footer.php'; ?>