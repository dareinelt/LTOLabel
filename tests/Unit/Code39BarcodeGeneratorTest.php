<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Barcode\BarcodeSpec;
use App\Barcode\Code39BarcodeGenerator;
use PHPUnit\Framework\TestCase;

final class Code39BarcodeGeneratorTest extends TestCase
{
    private Code39BarcodeGenerator $generator;
    private BarcodeSpec $spec;

    protected function setUp(): void
    {
        $this->generator = new Code39BarcodeGenerator();
        $this->spec = new BarcodeSpec();
    }

    public function testSupportsCode39(): void
    {
        self::assertTrue($this->generator->supports('CODE39'));
        self::assertTrue($this->generator->supports('code39'));
        self::assertFalse($this->generator->supports('CODE128'));
    }

    public function testGenerateProducesPattern(): void
    {
        $pattern = $this->generator->generate('ABC123L8', $this->spec);

        self::assertSame('ABC123L8', $pattern->data());
        self::assertSame(Code39BarcodeGenerator::SYMBOLOGY, $pattern->symbology());
        self::assertNotEmpty($pattern->runs());
        self::assertTrue($pattern->runs()[0]['bar'], 'Erstes Element muss ein Balken sein.');
        self::assertGreaterThan(0.0, $pattern->modulesWidth());
    }

    public function testGenerateWrapsWithStartStopAsterisks(): void
    {
        $pattern = $this->generator->generate('A', $this->spec);

        // Start '*' + 'A' + stop '*': 3 Zeichen × 10 Runs (9 Elemente + Lücke).
        self::assertCount(30, $pattern->runs());
    }

    public function testGenerateRejectsUnsupportedCharacter(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->generator->generate('abc', $this->spec);
    }
}
