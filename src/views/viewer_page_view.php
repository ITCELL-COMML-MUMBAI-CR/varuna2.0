<?php
if (!defined('VARUNA_ENTRY_POINT') || !in_array($_SESSION['role'], ['ADMIN', 'VIEWER', 'SCI'])) {
    require_once __DIR__ . '/errors/404.php';
    exit();
}

// Fetch filter data based on user role
$user_role = $_SESSION['role'];
$user_designation = $_SESSION['designation'] ?? ''; // Assuming designation is stored in session

$licensees = $contracts = $stations = $sections = [];
$show_licensee_contract_filters = ($user_designation !== 'ASC');

if ($user_role === 'ADMIN' || $show_licensee_contract_filters) {
    $licensees = $pdo->query("SELECT id, name FROM varuna_licensee ORDER BY name ASC")->fetchAll();
    $contracts = $pdo->query("SELECT id, contract_name FROM contracts ORDER BY contract_name ASC")->fetchAll();
}
// All roles can see station and section filters
$stations = $pdo->query("SELECT DISTINCT station_code FROM contracts WHERE station_code IS NOT NULL AND station_code != '' ORDER BY station_code ASC")->fetchAll(PDO::FETCH_COLUMN);
$sections = $pdo->query("SELECT Section_Code, Name FROM Section ORDER BY Name ASC")->fetchAll();
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