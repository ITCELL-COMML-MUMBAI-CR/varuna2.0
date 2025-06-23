<?php
/**
 * VARUNA System - QR Code Generator API (Definitive Version)
 * Implements the modern PHP 8 Builder constructor syntax as provided.
 * Current Time: Tuesday, June 17, 2025 at 4:15:25 PM IST
 * Location: Kalyan, Maharashtra, India
 */

// 1. Initialize the application and load all libraries
require_once __DIR__ . '/../../src/init.php';

// 1. Data to be encoded (from the URL parameter)
$data = $_GET['data'] ?? 'VARUNA_SYSTEM';

// 2. Path to your logo image
$logoPath = __DIR__ . '/../images/indian_railways_logo.png';

// Check if the logo file exists to prevent errors
if (!file_exists($logoPath)) {
    die('Error: Logo file not found at path: ' . htmlspecialchars($logoPath));
}

use chillerlan\QRCode\{QRCode, QRCodeException, QROptions};
use chillerlan\QRCode\Common\EccLevel;
use chillerlan\QRCode\Data\QRMatrix;
use chillerlan\QRCode\Output\{QROutputInterface, QRMarkupSVG};

class QRSvgWithLogoAndCustomShapes extends QRMarkupSVG
{

	/**
	 * @inheritDoc
	 */
	protected function paths(): string
	{
		// make sure connect paths is enabled
		$this->options->connectPaths = true;

		// we're calling QRMatrix::setLogoSpace() manually, so QROptions::$addLogoSpace has no effect here
		$this->matrix->setLogoSpace((int) ceil($this->moduleCount * $this->options->svgLogoScale));

		// generate the path element(s) - in this case it's just one element as we've "disabled" several options
		$svg = parent::paths();
		// add the custom shapes for the finder patterns
		$svg .= $this->getFinderPatterns();
		// and add the custom logo
		$svg .= $this->getLogo();

		return $svg;
	}

	/**
	 * @inheritDoc
	 */
	protected function path(string $path, int $M_TYPE): string
	{
		// omit the "fill" and "opacity" attributes on the path element
		return sprintf('<path class="%s" d="%s"/>', $this->getCssClass($M_TYPE), $path);
	}

	/**
	 * returns a path segment for a single module
	 *
	 * @see https://developer.mozilla.org/en-US/docs/Web/SVG/Attribute/d
	 */
	protected function module(int $x, int $y, int $M_TYPE): string
	{

		if (
			!$this->matrix->isDark($M_TYPE)
			// we're skipping the finder patterns here
			|| $this->matrix->checkType($x, $y, QRMatrix::M_FINDER)
			|| $this->matrix->checkType($x, $y, QRMatrix::M_FINDER_DOT)
		) {
			return '';
		}

		// return a heart shape (or any custom shape for that matter)
		return sprintf('M%1$s %2$s m0,1 h0.7 q0.3,0 0.3,-0.3 v-0.7 h-0.7 q-0.3,0 -0.3,0.3Z', $x, $y);
	}

	/**
	 * returns a custom path for the 3 finder patterns
	 */
	protected function getFinderPatterns(): string
	{

		$qz = ($this->options->addQuietzone) ? $this->options->quietzoneSize : 0;
		// the positions for the finder patterns (top left corner)
		// $this->moduleCount includes 2* the quiet zone size already, so we need to take this into account
		$pos = [
			[(0 + $qz), (0 + $qz)],
			[(0 + $qz), ($this->moduleCount - $qz - 7)],
			[($this->moduleCount - $qz - 7), (0 + $qz)],
		];

		// the custom path for one finder pattern - the first move (M) is parametrized, the rest are relative coordinates
		$path = 'M%1$s,%2$s m2,0 h3 q2,0 2,2 v3 q0,2 -2,2 h-3 q-2,0 -2,-2 v-3 q0,-2 2,-2z m0,1 q-1,0 -1,1 v3 ' .
			'q0,1 1,1 h3 q1,0 1,-1 v-3 q0,-1 -1,-1z m0,2.5 a1.5,1.5 0 1 0 3,0 a1.5,1.5 0 1 0 -3,0Z';
		$finder = [];

		foreach ($pos as [$ix, $iy]) {
			$finder[] = sprintf($path, $ix, $iy);
		}

		return sprintf(
			'%s<path class="%s" d="%s"/>',
			$this->options->eol,
			$this->getCssClass(QRMatrix::M_FINDER_DARK),
			implode(' ', $finder)
		);
	}

	/**
	 * returns a <g> element that contains the SVG logo and positions it properly within the QR Code
	 *
	 * @see https://developer.mozilla.org/en-US/docs/Web/SVG/Element/g
	 * @see https://developer.mozilla.org/en-US/docs/Web/SVG/Attribute/transform
	 */
	protected function getLogo(): string
	{
		// @todo: customize the <g> element to your liking (css class, style...)
		return sprintf(
			'%5$s<g transform="translate(%1$s %1$s) scale(%2$s)" class="%3$s">%5$s	%4$s%5$s</g>',
			(($this->moduleCount - ($this->moduleCount * $this->options->svgLogoScale)) / 2),
			$this->options->svgLogoScale,
			$this->options->svgLogoCssClass,
			file_get_contents($this->options->svgLogo),
			$this->options->eol
		);
	}

}


/**
 * augment the QROptions class
 */
class SVGWithLogoAndCustomShapesOptions extends QROptions
{
	// path to svg logo
	protected string $svgLogo;
	// logo scale in % of QR Code size, clamped to 10%-30%
	protected float $svgLogoScale = 0.20;
	// css class for the logo (defined in $svgDefs)
	protected string $svgLogoCssClass = '';

	// check logo
	protected function set_svgLogo(string $svgLogo): void
	{

		if (!file_exists($svgLogo) || !is_readable($svgLogo)) {
			throw new QRCodeException('invalid svg logo');
		}

		// @todo: validate svg

		$this->svgLogo = $svgLogo;
	}

	// clamp logo scale
	protected function set_svgLogoScale(float $svgLogoScale): void
	{
		$this->svgLogoScale = max(0.05, min(0.3, $svgLogoScale));
	}

}

	function generateQR($id)
	{



		/*
		 * Runtime
		 */

		// please excuse the IDE yelling https://youtrack.jetbrains.com/issue/WI-66549
		$options = new SVGWithLogoAndCustomShapesOptions;

		// SVG logo options (see extended class below)
		$options->svgLogo = __DIR__ . '/../images/ir_logo.svg'; // logo from: https://github.com/simple-icons/simple-icons
		$options->svgLogoScale = 0.25;
		$options->svgLogoCssClass = 'qr-logo dark';

		// QROptions
		$options->version = 5;
		$options->quietzoneSize = 4;
		$options->outputType = QROutputInterface::CUSTOM;
		$options->outputInterface = QRSvgWithLogoAndCustomShapes::class;
		$options->outputBase64 = false;
		$options->eccLevel = EccLevel::H; // ECC level H is required when using logos
		$options->addQuietzone = true;

		// https://developer.mozilla.org/en-US/docs/Web/SVG/Element/linearGradient
		$options->svgDefs = '
				<linearGradient id="gradient" x1="100%" y2="100%">
					<stop stop-color="#00b6ff" offset="0"/>
					<stop stop-color="#ff003d" offset="0.5"/>
					<stop stop-color="#ffcd00" offset="1"/>
				</linearGradient>
				<style><![CDATA[
					.dark{fill: #000000;}
					.qr-logo{fill: #FF171F;}
				]]></style>';


		$out = (new QRCode($options))->render($id);

		return $out;
	}

try {
    // 3. Define the data you want to encode and the path to your logo.
    // The logo MUST be a local file path.
    header('Content-Type: image/svg+xml');

    // Check if the logo file exists.
    if (!file_exists($logoPath)) {
        throw new \Exception('Logo file not found at: '.$logoPath);
    }


    // QR CODE CLASS

echo generateQR($data);
    

} catch (\Exception $e) {
    // Output any errors.
    header('Content-Type: text/plain');
    http_response_code(500);
    echo "Error generating QR code: " . $e->getMessage();
}