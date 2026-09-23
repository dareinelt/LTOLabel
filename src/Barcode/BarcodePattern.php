<?php

declare(strict_types=1);

namespace App\Barcode;

/**
 * The encoded, ready-to-render representation of a barcode.
 *
 * A barcode is a sequence of "runs". Each run is either a bar (printed black)
 * or a space (left white). Widths are expressed in module units so that the
 * renderer can scale them to exact physical dimensions.
 */
final class BarcodePattern
{
    /**
     * @param array<int, array{bar: bool, width: float}> $runs
     */
    public function __construct(
        private readonly string $data,
        private readonly string $symbology,
        private readonly array $runs,
    ) {
    }

    public function data(): string
    {
        return $this->data;
    }

    public function symbology(): string
    {
        return $this->symbology;
    }

    /**
     * @return array<int, array{bar: bool, width: float}>
     */
    public function runs(): array
    {
        return $this->runs;
    }

    /**
     * Total width of the encoded bars/spaces in module units (without quiet zone).
     */
    public function modulesWidth(): float
    {
        $width = 0.0;
        foreach ($this->runs as $run) {
            $width += $run['width'];
        }

        return $width;
    }

    public function totalWidthMm(BarcodeSpec $spec): float
    {
        return $this->modulesWidth() * $spec->moduleWidthMm + 2 * $spec->quietZoneMm;
    }
}
