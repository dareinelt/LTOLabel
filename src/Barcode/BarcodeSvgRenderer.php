<?php

declare(strict_types=1);

namespace App\Barcode;

/**
 * Renders a BarcodePattern as an inline SVG vector for browser previews.
 * No image library or JPEG compression is used.
 */
final class BarcodeSvgRenderer
{
    public function render(BarcodePattern $pattern, BarcodeSpec $spec, float $scale = 8.0): string
    {
        $quietModules = $spec->quietZoneMm / $spec->moduleWidthMm;
        $totalModules = $pattern->modulesWidth() + 2 * $quietModules;
        $heightModules = $spec->heightMm / $spec->moduleWidthMm;

        $width = $totalModules * $scale;
        $height = $heightModules * $scale;

        $svg = sprintf(
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 %s %s" width="%s" height="%s" '
            . 'preserveAspectRatio="none" role="img" aria-label="Barcode %s">',
            $this->num($width),
            $this->num($height),
            $this->num($width),
            $this->num($height),
            e($pattern->data())
        );

        $x = $quietModules * $scale;
        foreach ($pattern->runs() as $run) {
            $runWidth = $run['width'] * $scale;
            if ($run['bar']) {
                $svg .= sprintf(
                    '<rect x="%s" y="0" width="%s" height="%s" fill="#000000"/>',
                    $this->num($x),
                    $this->num($runWidth),
                    $this->num($height)
                );
            }
            $x += $runWidth;
        }

        return $svg . '</svg>';
    }

    private function num(float $value): string
    {
        return rtrim(rtrim(sprintf('%.4f', $value), '0'), '.');
    }
}
