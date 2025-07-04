<?php
if (!defined('VARUNA_ENTRY_POINT') || !in_array($_SESSION['role'], ['ADMIN', 'VIEWER', 'SCI'])) {
    require_once __DIR__ . '/errors/404.php';
    exit();
}

// Fetch filter data based on user role
$user_role = $_SESSION['role'];
$user_designation = $_SESSION['designation'] ?? '';
$geo_section = $_SESSION['section'] ?? null;
$dept_section = $_SESSION['department_section'] ?? null;

$licensees = $contracts = $stations = $sections = [];
$show_licensee_contract_filters = ($user_designation !== 'ASC');

// Viewers can see all data (no filtering)
if ($user_role === 'VIEWER') {
    if ($show_licensee_contract_filters) {
        $licensees = $pdo->query("SELECT id, name FROM varuna_licensee WHERE status = 'active' ORDER BY name ASC")->fetchAll();
        $contracts = $pdo->query("SELECT id, contract_name FROM contracts WHERE status = 'Active' ORDER BY contract_name ASC")->fetchAll();
    }
    $stations = $pdo->query("SELECT DISTINCT station_code FROM contracts WHERE station_code IS NOT NULL AND station_code != '' AND station_code NOT LIKE '%,%' ORDER BY station_code ASC")->fetchAll(PDO::FETCH_COLUMN);
    $sections = $pdo->query("SELECT Section_Code, Name FROM Section ORDER BY Name ASC")->fetchAll();
}
// IT CELL admins can see all data (no filtering) 
elseif ($user_role === 'ADMIN' && $geo_section === 'IT CELL') {
    if ($show_licensee_contract_filters) {
        $licensees = $pdo->query("SELECT id, name FROM varuna_licensee WHERE status = 'active' ORDER BY name ASC")->fetchAll();
        $contracts = $pdo->query("SELECT id, contract_name FROM contracts WHERE status = 'Active' ORDER BY contract_name ASC")->fetchAll();
    }
    $stations = $pdo->query("SELECT DISTINCT station_code FROM contracts WHERE station_code IS NOT NULL AND station_code != '' AND station_code NOT LIKE '%,%' ORDER BY station_code ASC")->fetchAll(PDO::FETCH_COLUMN);
    $sections = $pdo->query("SELECT Section_Code, Name FROM Section ORDER BY Name ASC")->fetchAll();
}
// SCIs and non-IT CELL admins see filtered options
elseif ($user_role === 'SCI' || ($user_role === 'ADMIN' && $geo_section !== 'IT CELL')) {
    $where_clause = '';
    $params = [];

    // Handle CCI CP users - they get access to both TRAIN section and their department section
    if ($user_designation === 'CCI CP') {
        $conditions = ["c.section_code = 'TRAIN'"]; // Default access to TRAIN section
        if (!empty($dept_section)) {
            $conditions[] = "c.contract_type IN (SELECT vct.ContractType FROM varuna_contract_types vct WHERE vct.Section = ?)";
            $params[] = $dept_section;
        }
        $where_clause = "(" . implode(" OR ", $conditions) . ")";
    }
    // Regular users with geographical section
    elseif ($geo_section) {
        $where_clause = "c.section_code = ?";
        $params[] = $geo_section;
    } 
    // Users with department section only
    elseif ($dept_section) {
        $where_clause = "c.contract_type IN (SELECT vct.ContractType FROM varuna_contract_types vct WHERE vct.Section = ?)";
        $params[] = $dept_section;
    }

    if (!empty($where_clause) && $show_licensee_contract_filters) {
        // Fetch contracts relevant to the user's section
        $contracts_sql = "SELECT id, contract_name FROM contracts c WHERE c.status = 'Active' AND $where_clause ORDER BY c.contract_name ASC";
        $stmt = $pdo->prepare($contracts_sql);
        $stmt->execute($params);
        $contracts = $stmt->fetchAll();
        
        // Fetch licensees who have contracts in the user's section
        $licensees_sql = "SELECT DISTINCT l.id, l.name FROM varuna_licensee l JOIN contracts c ON l.id = c.licensee_id WHERE l.status = 'active' AND $where_clause ORDER BY l.name ASC";
        $stmt = $pdo->prepare($licensees_sql);
        $stmt->execute($params);
        $licensees = $stmt->fetchAll();
    }
    
    // Fetch stations regardless of licensee/contract filters
    if (!empty($where_clause)) {
        // Fetch distinct stations from the relevant contracts
        $stations_sql = "SELECT DISTINCT c.station_code FROM contracts c WHERE c.station_code IS NOT NULL AND c.station_code != '' AND c.station_code NOT LIKE '%,%' AND $where_clause ORDER BY c.station_code ASC";
        $stmt = $pdo->prepare($stations_sql);
        $stmt->execute($params);
        $stations = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Fetch sections based on user type
        if ($user_designation === 'CCI CP') {
            // CCI CP users see both TRAIN and their department section
            $section_conditions = ["Section_Code = 'TRAIN'"];
            $section_params = [];
            if (!empty($dept_section)) {
                $section_conditions[] = "Section_Code = ?";
                $section_params[] = $dept_section;
            }
            $sections_sql = "SELECT Section_Code, Name FROM Section WHERE " . implode(" OR ", $section_conditions) . " ORDER BY Name ASC";
            $stmt = $pdo->prepare($sections_sql);
            $stmt->execute($section_params);
            $sections = $stmt->fetchAll();
        } elseif ($geo_section) {
            $sections_sql = "SELECT Section_Code, Name FROM Section WHERE Section_Code = ? ORDER BY Name ASC";
            $stmt = $pdo->prepare($sections_sql);
            $stmt->execute([$geo_section]);
            $sections = $stmt->fetchAll();
        } elseif ($dept_section) {
            // For departmental users, show their department section if it exists in Section table
            $sections_sql = "SELECT Section_Code, Name FROM Section WHERE Section_Code = ? ORDER BY Name ASC";
            $stmt = $pdo->prepare($sections_sql);
            $stmt->execute([$dept_section]);
            $sections = $stmt->fetchAll();
        }
    } else {
        // If no section assigned, show no data
        $licensees = $contracts = $stations = $sections = [];
    }
}
?>
<?php include __DIR__ . '/header.php'; ?>
<!-- Add PDFMake Library for PDF export -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>

<?php include __DIR__ . '/navbar.php'; ?>

<main class="page-container" style="padding: 20px 40px;">
    <h2>Staff Master View</h2>
    <p>A comprehensive list of all staff members in the system. Use the filters to narrow your search.</p>

    <div class="filter-container details-grid" style="margin-bottom: 20px; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));">
        <?php if ($show_licensee_contract_filters): ?>
            <div class="input-group"><label>Filter by Licensee</label><select id="filter_licensee" class="filter-control"><option value="">All</option><?php foreach($licensees as $l):?><option value="<?php echo $l['id'];?>"><?php echo htmlspecialchars($l['name']);?></option><?php endforeach;?></select></div>
            <div class="input-group"><label>Filter by Contract</label><select id="filter_contract" class="filter-control"><option value="">All</option><?php foreach($contracts as $c):?><option value="<?php echo $c['id'];?>"><?php echo htmlspecialchars($c['contract_name']);?></option><?php endforeach;?></select></div>
        <?php endif; ?>
        <div class="input-group"><label>Filter by Station</label><select id="filter_station" class="filter-control"><option value="">All</option><?php foreach($stations as $s):?><option value="<?php echo $s;?>"><?php echo htmlspecialchars($s);?></option><?php endforeach;?></select></div>
        <div class="input-group"><label>Filter by Section</label><select id="filter_section" class="filter-control"><option value="">All</option><?php foreach($sections as $s):?><option value="<?php echo $s['Section_Code'];?>"><?php echo htmlspecialchars($s['Name']);?></option><?php endforeach;?></select></div>
    </div>
    
    <div class="table-actions" style="margin-bottom: 10px;">
        <button id="export_pdf" class="btn-login" style="background: #5a6268;">Download as PDF</button>
    </div>

    <table id="viewer_staff_table" class="display" style="width:100%">
        <thead>
            <tr>
                <th>Photo</th>
                <th>Staff Details</th>
                <th>Contract Details</th>
                <th>Documents</th>
            </tr>
        </thead>
    </table>
</main>

<script src="<?php echo BASE_URL; ?>js/pages/viewer.js"></script>
<?php include __DIR__ . '/footer.php'; ?>