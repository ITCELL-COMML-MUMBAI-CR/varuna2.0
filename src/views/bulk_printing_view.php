<?php
if (!defined('VARUNA_ENTRY_POINT')) { require_once __DIR__ . '/errors/404.php'; exit(); }

$licensees = [];
$contracts = [];
$stations = [];
$sections = [];

$user_role = $_SESSION['role'] ?? 'VIEWER';
$geo_section = $_SESSION['section'] ?? null;
$dept_section = $_SESSION['department_section'] ?? null;

// Admins see all options
if ($user_role === 'ADMIN') {
    $licensees = $pdo->query("SELECT id, name FROM varuna_licensee WHERE status = 'active' ORDER BY name ASC")->fetchAll();
    $contracts = $pdo->query("SELECT id, contract_name, station_code FROM contracts WHERE status = 'Regular' ORDER BY contract_name ASC")->fetchAll();
    $stations = $pdo->query("SELECT DISTINCT station_code FROM contracts WHERE station_code IS NOT NULL AND station_code != '' AND station_code NOT LIKE '%,%' ORDER BY station_code ASC")->fetchAll(PDO::FETCH_COLUMN);
    $sections = $pdo->query("SELECT Section_Code, Name FROM Section ORDER BY Name ASC")->fetchAll();
} 
// SCIs see filtered options
elseif ($user_role === 'SCI') {
    $where_clause = '';
    $params = [];

    // Determine filter based on Geographical or Departmental SCI
    if ($geo_section) {
        $where_clause = "c.section_code = ?";
        $params[] = $geo_section;
    } elseif ($dept_section) {
        $where_clause = "c.contract_type IN (SELECT vct.ContractType FROM varuna_contract_types vct WHERE vct.Section = ?)";
        $params[] = $dept_section;
    }

    if (!empty($params)) {
        // Fetch contracts relevant to the SCI
        $contracts_sql = "SELECT id, contract_name, station_code FROM contracts c WHERE c.status = 'Regular' AND $where_clause ORDER BY c.contract_name ASC";
        $stmt = $pdo->prepare($contracts_sql);
        $stmt->execute($params);
        $contracts = $stmt->fetchAll();
        
        // Fetch licensees who have contracts in the SCI's section
        $licensees_sql = "SELECT DISTINCT l.id, l.name FROM varuna_licensee l JOIN contracts c ON l.id = c.licensee_id WHERE l.status = 'active' AND $where_clause ORDER BY l.name ASC";
        $stmt = $pdo->prepare($licensees_sql);
        $stmt->execute($params);
        $licensees = $stmt->fetchAll();
        
        // Fetch distinct stations from the relevant contracts
        $stations_sql = "SELECT DISTINCT c.station_code FROM contracts c WHERE c.station_code IS NOT NULL AND c.station_code != '' AND c.station_code NOT LIKE '%,%' AND $where_clause ORDER BY c.station_code ASC";
        $stmt = $pdo->prepare($stations_sql);
        $stmt->execute($params);
        $stations = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Fetch only the SCI's own section
        if ($geo_section) {
            $sections_sql = "SELECT Section_Code, Name FROM Section WHERE Section_Code = ? ORDER BY Name ASC";
            $stmt = $pdo->prepare($sections_sql);
            $stmt->execute([$geo_section]);
            $sections = $stmt->fetchAll();
        }
        // Note: The "By Section" filter is primarily for geographical SCIs. A departmental SCI would see no options here.
    }
}
?>
<?php include __DIR__ . '/header.php'; ?>
<?php include __DIR__ . '/navbar.php'; ?>

<main class="page-container" style="padding: 20px 40px;">
    <h2>Bulk ID Card Printing</h2>
    <p>Select a group of staff to generate a printable page with all their ID cards.</p>

    <div class="tab-container" style="margin-top: 20px;">
        <button class="tab-link active" data-tab="tab_licensee">By Licensee</button>
        <button class="tab-link" data-tab="tab_contract">By Contract</button>
        <button class="tab-link" data-tab="tab_station">By Station</button>
        <button class="tab-link" data-tab="tab_section">By Section</button>
    </div>

    <form id="bulkPrintForm" action="<?php echo BASE_URL; ?>bulk_id_page.php" method="GET" target="_blank">
        
        <div id="tab_licensee" class="tab-content active">
            <div class="input-group" style="max-width: 500px; margin-top: 15px;">
                <label>Select Licensee</label>
                <select name="licensee_id">
                    <option value="">-- Select --</option>
                    <?php foreach($licensees as $l): ?><option value="<?php echo $l['id']; ?>"><?php echo htmlspecialchars($l['name']); ?></option><?php endforeach; ?>
                </select>
                <button type="submit" data-filter="licensee" class="btn-login form-submit-btn" style="margin-top: 15px;">Generate Page</button>
            </div>
        </div>

        <div id="tab_contract" class="tab-content">
            <div class="input-group" style="max-width: 500px; margin-top: 15px;">
                <label>Select Contract</label>
                <select name="contract_id">
                    <option value="">-- Select --</option>
                    <?php foreach($contracts as $c): ?><option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['contract_name'] . ' (' . $c['station_code'] . ')'); ?></option><?php endforeach; ?>
                </select>
                <button type="submit" data-filter="contract" class="btn-login form-submit-btn" style="margin-top: 15px;">Generate Page</button>
            </div>
        </div>

        <div id="tab_station" class="tab-content">
            <div class="input-group" style="max-width: 500px; margin-top: 15px;">
                <label>Select Station</label>
                <select name="station_code">
                     <option value="">-- Select --</option>
                    <?php foreach($stations as $s): ?><option value="<?php echo $s; ?>"><?php echo htmlspecialchars($s); ?></option><?php endforeach; ?>
                </select>
                <button type="submit" data-filter="station" class="btn-login form-submit-btn" style="margin-top: 15px;">Generate Page</button>
            </div>
        </div>
        
        <div id="tab_section" class="tab-content">
            <div class="input-group" style="max-width: 500px; margin-top: 15px;">
                <label>Select Section</label>
                <select name="section_code">
                    <option value="">-- Select --</option>
                    <?php foreach($sections as $s): ?><option value="<?php echo $s['Section_Code']; ?>"><?php echo htmlspecialchars($s['Name']); ?></option><?php endforeach; ?>
                </select>
                <button type="submit" data-filter="section" class="btn-login form-submit-btn" style="margin-top: 15px;">Generate Page</button>
            </div>
        </div>
        
        <input type="hidden" name="filter_by" id="filter_by">
        <input type="hidden" name="filter_value" id="filter_value">
    </form>
</main>
<?php include __DIR__ . '/partials/toasts.php'; ?>
<script src="<?php echo BASE_URL; ?>js/pages/bulkPrinting.js"></script>
<?php include __DIR__ . '/footer.php'; ?>