<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Barcode\BarcodeRegistry;
use App\Barcode\Code39BarcodeGenerator;
use App\Label\Label;
use App\Label\MediaType;
use App\Pdf\LabelRenderer;
use App\Pdf\PdfGenerator;
use App\Profile\ProfileFactory;
use PHPUnit\Framework\TestCase;

final class PdfGeneratorTest extends TestCase
{
    private PdfGenerator $generator;
    private \App\Profile\Profile $profile;

    protected function setUp(): void
    {
        $registry = new BarcodeRegistry();
        $registry->register(new Code39BarcodeGenerator());

        $this->generator = new PdfGenerator(new LabelRenderer($registry));
        $this->profile = (new ProfileFactory())->create('dell_ml3', []);
    }

    public function testRenderSingleProducesValidPdf(): void
    {
        $label = new Label('ABC123L8', MediaType::DATA, 'L8');
        $pdf = $this->generator->renderSingle($label, $this->profile);

        self::assertStringStartsWith('%PDF', $this->generator->toString($pdf));
    }

    public function testRenderSheetProducesValidPdf(): void
    {
        $labels = [
            new Label('ABC123L8', MediaType::DATA, 'L8'),
            new Label('CLN001L1', MediaType::CLEANING, 'L1'),
        ];
        $layout = \App\Printing\PageLayoutFactory::fromConfig([
            'format' => 'A4',
            'grid' => ['columns' => 2, 'rows' => 8],
        ]);

        $pdf = $this->generator->renderSheet($labels, $this->profile, $layout);

        self::assertStringStartsWith('%PDF', $this->generator->toString($pdf));
    }
}
