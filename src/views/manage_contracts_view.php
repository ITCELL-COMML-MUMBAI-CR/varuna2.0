<?php if (!defined('VARUNA_ENTRY_POINT')) { require_once __DIR__ . '/errors/404.php'; exit(); } ?>
<?php include __DIR__ . '/header.php'; ?>
<?php include __DIR__ . '/navbar.php'; ?>
<main class="page-container" style="padding: 20px 40px;">
    <h2>Manage Contracts</h2>
    <div class="table-container" style="margin-top: 20px;">
        <table id="contractsTable" class="display" style="width:100%">
            <thead><tr><th>ID</th><th>Contract Name</th><th>Type</th><th>Station/Train</th><th>Licensee</th><th>Status</th><th>Actions</th></tr></thead>
        </table>
    </div>
</main>
<?php include __DIR__ . '/partials/toasts.php'; ?>
<script src="<?php echo BASE_URL; ?>js/pages/manageRecords.js"></script>
<?php include __DIR__ . '/footer.php'; ?>