<?php
// Security check to prevent direct access to this file
if (!defined('VARUNA_ENTRY_POINT')) {
    require_once __DIR__ . '/../src/views/errors/404.php';
    exit();
}

// Protect the page: Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "login");
    exit();
}

// --- FIX: Add role-based access control ---
if (($_SESSION['designation'] ?? '') === 'ASC') {
    // Users with designation 'ASC' are not allowed to view the dashboard.
    echo "Access Denied.";
    exit();
}
?>
<?php include __DIR__ . '/../src/views/header.php'; ?>
<!-- Add Chart.js library -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<?php include __DIR__ . '/../src/views/navbar.php'; ?>

<main class="dashboard-container" style="padding: 20px 40px;">
    <h1 style="margin-bottom: 20px;">VARUNA System Dashboard</h1>

    <!-- 1. Stats Cards -->
    <div class="stats-grid">
        <div class="stat-card" id="licensees_card">
            <h2>Total Licensees</h2>
            <p id="licensee_count">0</p>
        </div>
        <div class="stat-card" id="contracts_card">
            <h2>Total Contracts</h2>
            <p id="contract_count">0</p>
        </div>
        <div class="stat-card" id="staff_card">
            <h2>Total Staff</h2>
            <p id="staff_count">0</p>
        </div>
    </div>

    <!-- 2. Charts and Tables Section -->
    <div class="dashboard-main-grid">
        <div class="dashboard-card">
            <h3>Staff Status Overview</h3>
            <div class="chart-container">
                <canvas id="staffStatusChart"></canvas>
            </div>
        </div>
        <div class="dashboard-card" style="grid-column: span 2;">
            <h3>Licensee Breakdown</h3>
            <table id="licensee_breakdown_table" class="display" style="width:100%">
                <thead>
                    <tr>
                        <th>Licensee Name</th>
                        <th>Active Contracts</th>
                        <th>Total Staff</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</main>

<!-- Add custom styles for the dashboard -->
<style>
    .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px; }
    .stat-card { background: #fff; border-radius: 8px; padding: 25px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); cursor: pointer; transition: transform 0.2s, box-shadow 0.2s; }
    .stat-card:hover { transform: translateY(-5px); box-shadow: 0 8px 15px rgba(0,0,0,0.1); }
    .stat-card h2 { font-size: 1.1rem; color: #555; margin-bottom: 10px; }
    .stat-card p { font-size: 2.5rem; font-weight: 700; color: var(--primary-color); }
    .dashboard-main-grid { display: grid; grid-template-columns: 1fr 2fr; gap: 20px; }
    .dashboard-card { background: #fff; border-radius: 8px; padding: 25px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); }
    .chart-container { position: relative; height: 300px; }
    @media (max-width: 992px) { .dashboard-main-grid { grid-template-columns: 1fr; } .dashboard-card { grid-column: auto !important; } }
</style>

<!-- Re-use footer for modals -->
<?php include __DIR__ . '/../src/views/footer.php'; ?>
<!-- Add new JS file for dashboard logic -->
<script src="<?php echo BASE_URL; ?>js/pages/dashboard.js"></script>