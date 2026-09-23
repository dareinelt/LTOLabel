<?php

declare(strict_types=1);

namespace App\Application;

use App\Config\Config;
use App\Label\Label;
use App\Label\MediaType;
use App\Pdf\PdfGenerator;
use App\Printing\PageLayout;
use App\Printing\PageLayoutFactory;
use FPDF;

/**
 * Application service for PDF rendering and print history.
 */
final class PrintService
{
    public function __construct(
        private readonly PdfGenerator $pdf,
        private readonly LabelService $labels,
        private readonly Config $config,
    ) {
    }

    public function pageLayout(): PageLayout
    {
        return PageLayoutFactory::fromConfig($this->config->page());
    }

    /**
     * @param Label[] $labels
     */
    public function renderSheet(array $labels, string $profileName): FPDF
    {
        return $this->pdf->renderSheet($labels, $this->labels->profile($profileName), $this->pageLayout());
    }

    public function renderSingle(Label $label, string $profileName): FPDF
    {
        return $this->pdf->renderSingle($label, $this->labels->profile($profileName));
    }

    /**
     * Render a mixed data + cleaning test sheet.
     */
    public function renderTestSheet(string $profileName): FPDF
    {
        $testLabels = [
            new Label('TEST01L8', MediaType::DATA, 'L8'),
            new Label('TEST02L8', MediaType::DATA, 'L8'),
            new Label('TEST03L9', MediaType::DATA, 'L9'),
            new Label('TEST04L9', MediaType::DATA, 'L9'),
            new Label('CLN001L1', MediaType::CLEANING, 'L1'),
            new Label('CLN002L1', MediaType::CLEANING, 'L1'),
        ];

        return $this->renderSheet($testLabels, $profileName);
    }

    public function toString(FPDF $pdf): string
    {
        return $this->pdf->toString($pdf);
    }
}
