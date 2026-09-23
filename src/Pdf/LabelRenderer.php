<?php

declare(strict_types=1);

namespace App\Pdf;

use App\Barcode\BarcodeRegistry;
use App\Barcode\BarcodeSpec;
use App\Barcode\BarcodePattern;
use App\Label\Label;
use App\Profile\Profile;
use App\Support\Unit;
use FPDF;

/**
 * Draws a single label (vector barcode + human-readable text) into an FPDF
 * document at exact physical coordinates.
 */
final class LabelRenderer
{
    private const TEXT_GAP_MM = 0.5;

    public function __construct(private readonly BarcodeRegistry $registry)
    {
    }

    public function draw(
        FPDF $pdf,
        Label $label,
        Profile $profile,
        float $xMm,
        float $yMm,
        float $labelWidthMm,
        float $labelHeightMm,
    ): void {
        $spec = $profile->barcode;
        $pattern = $this->registry->generator($spec->type)->generate($label->labelCode, $spec);

        $hasText = $profile->showLabelCode && $profile->textPosition !== 'none';
        $textHeightMm = $profile->fontSizePt * 25.4 / Unit::PT_PER_INCH;
        $contentHeightMm = $spec->heightMm + ($hasText ? self::TEXT_GAP_MM + $textHeightMm : 0);
        $topY = $yMm + max(0.0, ($labelHeightMm - $contentHeightMm) / 2.0);

        $barcodeY = $topY;
        $textY = null;

        if ($hasText) {
            if ($profile->textPosition === 'above') {
                $barcodeY = $topY + $textHeightMm + self::TEXT_GAP_MM;
                $textY = $topY + $textHeightMm;
            } else {
                $textY = $topY + $spec->heightMm + self::TEXT_GAP_MM + $textHeightMm;
            }
        }

        $this->drawBarcode($pdf, $pattern, $spec, $xMm, $barcodeY, $labelWidthMm);

        if ($hasText && $textY !== null) {
            $pdf->SetFont($profile->font, '', $profile->fontSizePt);
            $textWidth = $pdf->GetStringWidth($label->labelCode);
            $textX = $xMm + max(0.0, ($labelWidthMm - $textWidth) / 2.0);
            $pdf->Text($textX, $textY, $label->labelCode);
        }
    }

    private function drawBarcode(
        FPDF $pdf,
        BarcodePattern $pattern,
        BarcodeSpec $spec,
        float $xMm,
        float $barY,
        float $labelWidthMm,
    ): void {
        $totalWidth = $pattern->totalWidthMm($spec);
        $cursorX = $xMm + max(0.0, ($labelWidthMm - $totalWidth) / 2.0) + $spec->quietZoneMm;

        foreach ($pattern->runs() as $run) {
            $widthMm = $run['width'] * $spec->moduleWidthMm;
            if ($run['bar']) {
                $pdf->Rect($cursorX, $barY, $widthMm, $spec->heightMm, 'F');
            }
            $cursorX += $widthMm;
        }
    }
}
