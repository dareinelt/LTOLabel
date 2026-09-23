<?php

declare(strict_types=1);

namespace App\Profile;

use App\Label\MediaType;

/**
 * Resolved validation/id-format rules for a single media type (DATA or
 * CLEANING) within a library profile. Built from configuration with safe
 * defaults; never hard-coded outside the config layer.
 */
final class MediaTypeProfile
{
    /**
     * @param string[] $mediaIds Valid media identifiers (e.g. "L8", "M8", "L1").
     */
    public function __construct(
        public readonly MediaType $type,
        public readonly int $totalLength,
        public readonly string $allowedChars,
        public readonly array $mediaIds,
        public readonly ?string $defaultMediaId,
        public readonly string $prefix,
        public readonly int $volserLength,
        public readonly ?string $suffix,
        public readonly int $sequenceLength,
    ) {
    }
}
