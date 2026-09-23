<?php

declare(strict_types=1);

namespace App\Barcode;

/**
 * Physical and technical parameters for barcode generation.
 *
 * Defaults reflect the IBM LTO Ultrium cartridge label specification
 * (see docs/RESEARCH.md). All values are configurable via the active profile.
 */
final class BarcodeSpec
{
    public function __construct(
        public readonly string $type = 'CODE39',
        public readonly float $moduleWidthMm = 0.423,
        public readonly float $wideNarrowRatio = 2.75,
        public readonly float $heightMm = 11.1,
        public readonly float $quietZoneMm = 4.0,
    ) {
    }

    public function wideWidthMm(): float
    {
        return $this->moduleWidthMm * $this->wideNarrowRatio;
    }
}
