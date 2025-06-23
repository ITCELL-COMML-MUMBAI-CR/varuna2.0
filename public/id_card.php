<?php
/**
 * VARUNA System - ID Card Generator (Final Corrected Version)
 * FIX: Hides the single print button during a bulk print operation.
 */

// 1. Initialize the application environment
require_once __DIR__ . '/../src/init.php';

// QR Code Generator Library & related classes
use chillerlan\QRCode\{QRCode, QRCodeException, QROptions};
use chillerlan\QRCode\Common\EccLevel;
use chillerlan\QRCode\Data\QRMatrix;
use chillerlan\QRCode\Output\{QROutputInterface, QRMarkupSVG};

if (!class_exists('QRSvgWithLogoAndCustomShapes')) {
    class QRSvgWithLogoAndCustomShapes extends QRMarkupSVG {
        protected function paths():string{
		$this->options->connectPaths=true;
		$this->matrix->setLogoSpace((int)ceil($this->moduleCount*$this->options->svgLogoScale));
		$svg=parent::paths();
		$svg.=$this->getFinderPatterns();
		$svg.=$this->getLogo();
		return $svg;
	}
	protected function path(string $path,int $M_TYPE):string{
		return sprintf('<path class="%s" d="%s"/>',$this->getCssClass($M_TYPE),$path);
	}
	protected function module(int $x,int $y,int $M_TYPE):string{
		if(!$this->matrix->isDark($M_TYPE)||$this->matrix->checkType($x,$y,QRMatrix::M_FINDER)||$this->matrix->checkType($x,$y,QRMatrix::M_FINDER_DOT)){
			return '';
		}
		return sprintf('M%1$s %2$s m0,1 h0.7 q0.3,0 0.3,-0.3 v-0.7 h-0.7 q-0.3,0 -0.3,0.3Z',$x,$y);
	}
	protected function getFinderPatterns():string{
		$qz=($this->options->addQuietzone)?$this->options->quietzoneSize:0;
		$pos=[[(0+$qz),(0+$qz)],[(0+$qz),($this->moduleCount-$qz-7)],[($this->moduleCount-$qz-7),(0+$qz)],];
		$path='M%1$s,%2$s m2,0 h3 q2,0 2,2 v3 q0,2 -2,2 h-3 q-2,0 -2,-2 v-3 q0,-2 2,-2z m0,1 q-1,0 -1,1 v3 q0,1 1,1 h3 q1,0 1,-1 v-3 q0,-1 -1,-1z m0,2.5 a1.5,1.5 0 1 0 3,0 a1.5,1.5 0 1 0 -3,0Z';
		$finder=[];
		foreach($pos as[$ix,$iy]){
			$finder[]=sprintf($path,$ix,$iy);
		}
		return sprintf('%s<path class="%s" d="%s"/>',$this->options->eol,$this->getCssClass(QRMatrix::M_FINDER_DARK),implode(' ',$finder));
	}
	protected function getLogo():string{
		return sprintf('%5$s<g transform="translate(%1$s %1$s) scale(%2$s)" class="%3$s">%5$s	%4$s%5$s</g>',(($this->moduleCount-($this->moduleCount*$this->options->svgLogoScale))/2),$this->options->svgLogoScale,$this->options->svgLogoCssClass,file_get_contents($this->options->svgLogo),$this->options->eol);
	}
    }
}
if (!class_exists('SVGWithLogoAndCustomShapesOptions')) {
    class SVGWithLogoAndCustomShapesOptions extends QROptions {
        protected string $svgLogo;
	protected float $svgLogoScale=0.20;
	protected string $svgLogoCssClass='';
	protected function set_svgLogo(string $svgLogo):void{
		if(!file_exists($svgLogo)||!is_readable($svgLogo)){
			throw new QRCodeException('invalid svg logo');
		}
		$this->svgLogo=$svgLogo;
	}
	protected function set_svgLogoScale(float $svgLogoScale):void{
		$this->svgLogoScale=max(0.05,min(0.3,$svgLogoScale));
	}
    }
}
if (!function_exists('generateQR')) {
    function generateQR($id) {
        $options=new SVGWithLogoAndCustomShapesOptions;
	$options->svgLogo=__DIR__ .'/images/ir_logo.svg';
	$options->svgLogoScale=0.25;
	$options->svgLogoCssClass='qr-logo dark';
	$options->version=5;
	$options->quietzoneSize=4;
	$options->outputType=QROutputInterface::CUSTOM;
	$options->outputInterface=QRSvgWithLogoAndCustomShapes::class;
	$options->outputBase64=false;
	$options->eccLevel=EccLevel::H;
	$options->addQuietzone=true;
	$options->svgDefs='
				<linearGradient id="gradient" x1="100%" y2="100%">
					<stop stop-color="#00b6ff" offset="0"/>
					<stop stop-color="#ff003d" offset="0.5"/>
					<stop stop-color="#ffcd00" offset="1"/>
				</linearGradient>
				<style><![CDATA[
					.dark{fill: #000000;}
					.qr-logo{fill: #FF171F;}
				]]></style>';
	$out=(new QRCode($options))->render($id);
	return $out;
    }
}

// 2. Data fetching logic (remains unchanged)
if (!isset($_GET['staff_id']) || empty(trim($_GET['staff_id']))) {
    die("Error: A valid Staff ID is required to generate the card.");
}
$staff_id = trim($_GET['staff_id']);
try {
    $stmt = $pdo->prepare("
        SELECT s.*, c.contract_name, c.contract_type, c.section_code, c.station_code, l.name as licensee_name
        FROM varuna_staff s
        LEFT JOIN contracts c ON s.contract_id = c.id
        LEFT JOIN varuna_licensee l ON c.licensee_id = l.id
        WHERE s.id = ? AND s.status = 'approved'
    ");
    $stmt->execute([$staff_id]);
    $staff = $stmt->fetch();

    if (!$staff) {
        die("Error: Approved staff member with this ID was not found.");
    }
    
    $log_stmt = $pdo->prepare("
        SELECT user_id FROM varuna_activity_log 
        WHERE action = 'STAFF_STATUS_UPDATE' AND details LIKE ? ORDER BY timestamp DESC LIMIT 1
    ");
    $log_stmt->execute(["%Staff ID $staff_id status updated to approved%"]);
    $approver_id = $log_stmt->fetchColumn();
    
    $auth_sig_path = 'default_sig.png';
    if ($approver_id) {
        $sig_stmt = $pdo->prepare("SELECT signature_path FROM varuna_authority_signatures WHERE user_id = ?");
        $sig_stmt->execute([$approver_id]);
        $auth_sig_path_from_db = $sig_stmt->fetchColumn();
        if ($auth_sig_path_from_db) { $auth_sig_path = $auth_sig_path_from_db; }
    }

    $style_stmt = $pdo->prepare("SELECT * FROM varuna_id_styles WHERE contract_type = ?");
    $style_stmt->execute([$staff['contract_type']]);
    $styles = $style_stmt->fetch();

} catch (Exception $e) {
    error_log("ID Card Data Fetch Error for staff_id {$staff_id}: " . $e->getMessage());
    die("A critical error occurred while fetching data for the ID card.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ID Card - <?php echo htmlspecialchars($staff['name']); ?></title>
    <link href="<?php echo BASE_URL; ?>css/id_card_style.css" rel="stylesheet" type="text/css">
    <style>
        <?php if ($styles): ?>
        #id, .id-1 { 
            background-color: <?php echo htmlspecialchars($styles['bg_color']); ?> !important;
            border-color: <?php echo htmlspecialchars($styles['border_color']); ?> !important;
            color: <?php echo htmlspecialchars($styles['default_font_color']); ?> !important;
        }
        p, h2, h6 { color: <?php echo htmlspecialchars($styles['default_font_color']); ?> !important; }
        .vendor-name { color: <?php echo htmlspecialchars($styles['vendor_name_color']); ?> !important; }
        .station-name { color: <?php echo htmlspecialchars($styles['station_train_color']); ?> !important; }
        .nav-logo { background-color: <?php echo htmlspecialchars($styles['nav_logo_bg_color']); ?> !important; }
        .nav-logo h3 { color: <?php echo htmlspecialchars($styles['nav_logo_font_color']); ?> !important; }
        .nav-licensee h2 { color: <?php echo htmlspecialchars($styles['licensee_name_color']); ?> !important; }
        .container-id-2 center p { color: <?php echo htmlspecialchars($styles['instructions_color']); ?> !important; }
        <?php endif; ?>
    </style>
</head>
<body>
    <div id="printableArea">
        <div id="bg">
            <div id="id">
                <div class='header-logo'>
                    <div class='idlogo'><img class='logo' src='<?php echo BASE_URL; ?>images/indian_railways_logo.png'></div>
                    <div class='idhead'>
                        <div class='idhead-section'><h6>UNDER CONTRACTUAL OBLIGATIONS OF</h6></div>
                        <div class='idhead-section'><h3>CENTRAL RAILWAY</h3></div>
                        <div class='idhead-section'><h3>MUMBAI DIVISION</h3></div>
                    </div>
                </div>
                <div class='nav-logo'><h3><strong><?php echo htmlspecialchars($staff['designation']); ?> ID - <?php echo htmlspecialchars($staff['id']); ?></strong></h3></div>
                <div class='nav-licensee'><h2><?php echo htmlspecialchars($staff['licensee_name']); ?></h2></div>
                <div class='container-id'>
                    <img class='profile-pic' src='<?php echo BASE_URL . "uploads/staff/" . htmlspecialchars($staff['profile_image']); ?>'>
                    <p class='field-name'>Name</p>
                    <p class='vendor-name'><?php echo htmlspecialchars($staff['name']); ?></p>
                    <p class='field-name'>Station / Train</p>
                    <p class='station-name'><?php echo htmlspecialchars($staff['station_code']); ?></p>
                </div>
                <div class='qr-container'>
                    <div class='selfsign'>
                        <img class='selfimg' src='<?php echo BASE_URL . "uploads/staff/" . htmlspecialchars($staff['signature_image']); ?>'>
                        <p>Holder's Signature</p>
                    </div>
                    <div class='qrimg'><?php echo generateQR($staff['id']); ?></div>
                    <div class='authsign'>
                        <img class='authimg' src='<?php echo BASE_URL . "uploads/authority/" . htmlspecialchars($auth_sig_path); ?>'>
                        <p>Issuing Authority <br> CCI <?php echo htmlspecialchars($staff['section_code']); ?> </p>
                    </div>
                </div>
            </div>
            <div class="id-1">
                <div class='bg-id'><div class='qrimg2'><?php echo generateQR($staff['id']); ?></div></div>
                <div class='container-id-2'>
                    <center><p style='margin-bottom:5px;font-size: 1rem;font-weight: 1000;color:#CF5C36'>Instructions</p></center>
                    <p style='margin:2px;font-size: 0.8rem;text-align:left;'>
                        1) The Holder of this ID is not a regular Railway Employee.<br>
                        2) The loss of this card should be immediately reported to card issuing office.<br>
                        3) It is only valid for specified Station or Train.<br>
                        4) This ID card is not Transferable.<br>
                        5) For more details scan QR.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <?php if (empty($is_bulk_print)): ?>
        <div class="print-container">
            <button class="btn-login" onclick="printIdCard()">🖨️ Print ID Card</button>
        </div>

        <script>
            function printIdCard() {
                const printContents = document.getElementById('printableArea').innerHTML;
                const originalContents = document.body.innerHTML;

                // Temporarily replace the page content with just the card
                document.body.innerHTML = printContents;
                
                // Trigger the print dialog
                window.print();
                
                // Restore the original page content
                document.body.innerHTML = originalContents;
                // It's good practice to reload to re-initialize any scripts
                location.reload();
            }
        </script>
        <?php endif; ?>

</body>
</html>