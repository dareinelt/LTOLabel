<?php

declare(strict_types=1);

namespace App\Profile;

use App\Barcode\BarcodeSpec;
use App\Label\MediaType;

/**
 * A named library profile (e.g. "dell_ml3", "generic_lto") bundling label
 * geometry, barcode parameters, text settings and per-media-type rules.
 */
final class Profile
{
    /**
     * @param array<string, MediaTypeProfile> $mediaTypes keyed by MediaType value.
     */
    public function __construct(
        public readonly string $name,
        public readonly float $labelWidthMm,
        public readonly float $labelHeightMm,
        public readonly BarcodeSpec $barcode,
        public readonly string $font,
        public readonly float $fontSizePt,
        public readonly string $textPosition,
        public readonly bool $showLabelCode,
        public readonly array $mediaTypes,
    ) {
    }

    public function mediaType(MediaType $type): ?MediaTypeProfile
    {
        return $this->mediaTypes[$type->value] ?? null;
    }
}
