<?php

declare(strict_types=1);

namespace App\Pdf;

use App\Label\Label;
use App\Printing\PageLayout;
use App\Profile\Profile;
use FPDF;

/**
 * Generates print-ready PDFs with exact physical dimensions and no scaling.
 *
 * - Sheet mode: lays labels out on a configurable grid (columns x rows).
 * - Single mode: produces a PDF whose page size equals the label size.
 */
final class PdfGenerator
{
    public function __construct(private readonly LabelRenderer $renderer)
    {
    }

    /**
     * @param Label[] $labels
     */
    public function renderSheet(array $labels, Profile $profile, PageLayout $layout): FPDF
    {
        $pdf = $this->createPdf($layout->widthMm, $layout->heightMm);
        $pdf->SetMargins(0, 0, 0);
        $pdf->SetAutoPageBreak(false);
        $pdf->AddPage();

        $availableW = $layout->widthMm - $layout->marginLeftMm - $layout->marginRightMm;
        $availableH = $layout->heightMm - $layout->marginTopMm - $layout->marginBottomMm;
        $cols = max(1, $layout->columns);
        $rows = max(1, $layout->rows);
        $cellW = ($availableW - ($cols - 1) * $layout->gapHorizontalMm) / $cols;
        $cellH = ($availableH - ($rows - 1) * $layout->gapVerticalMm) / $rows;
        $perPage = $cols * $rows;

        $i = 0;
        foreach (array_values($labels) as $label) {
            $index = $i % $perPage;
            if ($index === 0 && $i > 0) {
                $pdf->AddPage();
            }
            $col = $index % $cols;
            $row = intdiv($index, $cols);
            $x = $layout->marginLeftMm + $col * ($cellW + $layout->gapHorizontalMm);
            $y = $layout->marginTopMm + $row * ($cellH + $layout->gapVerticalMm);
            $this->renderer->draw($pdf, $label, $profile, $x, $y, $profile->labelWidthMm, $profile->labelHeightMm);
            $i++;
        }

        return $pdf;
    }

    public function renderSingle(Label $label, Profile $profile): FPDF
    {
        $pdf = $this->createPdf($profile->labelWidthMm, $profile->labelHeightMm);
        $pdf->SetMargins(0, 0, 0);
        $pdf->SetAutoPageBreak(false);
        $pdf->AddPage();
        $this->renderer->draw($pdf, $label, $profile, 0, 0, $profile->labelWidthMm, $profile->labelHeightMm);

        return $pdf;
    }

    public function toString(FPDF $pdf): string
    {
        return $pdf->Output('S');
    }

    /**
     * Create an FPDF document whose page size matches the requested width and
     * height exactly. FPDF stores portrait page sizes as [width <= height], so
     * the orientation must be derived from the physical dimensions instead of
     * being hard-coded to portrait (which would rotate landscape labels 90°).
     */
    private function createPdf(float $widthMm, float $heightMm): FPDF
    {
        $orientation = $widthMm > $heightMm ? 'L' : 'P';

        return new FPDF($orientation, 'mm', [$widthMm, $heightMm]);
    }
}
