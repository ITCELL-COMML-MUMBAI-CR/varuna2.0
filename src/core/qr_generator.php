<?php
/**
 * VARUNA System - QR Code Generation Utilities
 */

use chillerlan\QRCode\{QRCode, QRCodeException, QROptions};
use chillerlan\QRCode\Common\EccLevel;
use chillerlan\QRCode\Data\QRMatrix;
use chillerlan\QRCode\Output\{QROutputInterface, QRMarkupSVG};

if (!class_exists('QRSvgWithLogoAndCustomShapes')) {
    class QRSvgWithLogoAndCustomShapes extends QRMarkupSVG {
        protected function paths():string{
            $this->options->connectPaths=true;
            $this->matrix->setLogoSpace((int)ceil($this->moduleCount * $this->options->svgLogoScale));
            $svg = parent::paths();
            $svg .= $this->getFinderPatterns();
            $svg .= $this->getLogo();
            return $svg;
        }
        protected function path(string $path, int $M_TYPE):string{
            return sprintf('<path class="%s" d="%s"/>', $this->getCssClass($M_TYPE), $path);
        }
        protected function module(int $x, int $y, int $M_TYPE):string{
            if (!$this->matrix->isDark($M_TYPE) || $this->matrix->checkType($x, $y, QRMatrix::M_FINDER) || $this->matrix->checkType($x, $y, QRMatrix::M_FINDER_DOT)) {
                return '';
            }
            return sprintf('M%1$s %2$s m0,1 h0.7 q0.3,0 0.3,-0.3 v-0.7 h-0.7 q-0.3,0 -0.3,0.3Z', $x, $y);
        }
        protected function getFinderPatterns():string{
            $qz = ($this->options->addQuietzone) ? $this->options->quietzoneSize : 0;
            $pos = [[(0+$qz), (0+$qz)], [(0+$qz), ($this->moduleCount-$qz-7)], [($this->moduleCount-$qz-7), (0+$qz)]];
            $path = 'M%1$s,%2$s m2,0 h3 q2,0 2,2 v3 q0,2 -2,2 h-3 q-2,0 -2,-2 v-3 q0,-2 2,-2z m0,1 q-1,0 -1,1 v3 q0,1 1,1 h3 q1,0 1,-1 v-3 q0,-1 -1,-1z m0,2.5 a1.5,1.5 0 1 0 3,0 a1.5,1.5 0 1 0 -3,0Z';
            $finder = [];
            foreach ($pos as [$ix, $iy]) {
                $finder[] = sprintf($path, $ix, $iy);
            }
            return sprintf('%s<path class="%s" d="%s"/>', $this->options->eol, $this->getCssClass(QRMatrix::M_FINDER_DARK), implode(' ', $finder));
        }
        protected function getLogo():string{
            $logoPath = PROJECT_ROOT . $this->options->svgLogo;
            return sprintf('%5$s<g transform="translate(%1$s %1$s) scale(%2$s)" class="%3$s">%5$s	%4$s%5$s</g>', (($this->moduleCount - ($this->moduleCount * $this->options->svgLogoScale)) / 2), $this->options->svgLogoScale, $this->options->svgLogoCssClass, file_get_contents($logoPath), $this->options->eol);
        }
    }
}

if (!class_exists('SVGWithLogoAndCustomShapesOptions')) {
    class SVGWithLogoAndCustomShapesOptions extends QROptions {
        public string $svgLogo; // Changed to public for direct access
        public float $svgLogoScale = 0.20;
        public string $svgLogoCssClass = '';

        protected function set_svgLogo(string $svgLogo):void{
            $fullPath = PROJECT_ROOT . $svgLogo;
            if (!file_exists($fullPath) || !is_readable($fullPath)) {
                throw new QRCodeException('invalid svg logo: ' . $fullPath);
            }
            // we store the relative path
            $this->svgLogo = $svgLogo;
        }

        protected function set_svgLogoScale(float $svgLogoScale):void{
            $this->svgLogoScale = max(0.05, min(0.3, $svgLogoScale));
        }
    }
}

if (!function_exists('generateQR')) {
    function generateQR($id) {
        $options = new SVGWithLogoAndCustomShapesOptions;
        $options->svgLogo = '/public/images/ir_logo.svg';
        $options->svgLogoScale = 0.25;
        $options->svgLogoCssClass = 'qr-logo dark';
        $options->version = 5;
        $options->quietzoneSize = 4;
        $options->outputType = QROutputInterface::CUSTOM;
        $options->outputInterface = QRSvgWithLogoAndCustomShapes::class;
        $options->outputBase64 = false;
        $options->eccLevel = EccLevel::H;
        $options->addQuietzone = true;
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
        return (new QRCode($options))->render($id);
    }
} 