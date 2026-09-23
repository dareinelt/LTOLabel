<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Barcode\BarcodeSpec;
use App\Barcode\BarcodeSvgRenderer;
use App\Barcode\Code39BarcodeGenerator;
use PHPUnit\Framework\TestCase;

final class BarcodeSvgRendererTest extends TestCase
{
    public function testRendersVectorSvg(): void
    {
        $generator = new Code39BarcodeGenerator();
        $spec = new BarcodeSpec();
        $pattern = $generator->generate('ABC123L8', $spec);

        $svg = (new BarcodeSvgRenderer())->render($pattern, $spec);

        self::assertStringStartsWith('<svg', $svg);
        self::assertStringContainsString('<rect', $svg);
        self::assertStringContainsString('</svg>', $svg);
        self::assertStringContainsString('ABC123L8', $svg);
    }
}
