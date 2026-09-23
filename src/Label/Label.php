<?php

declare(strict_types=1);

namespace App\Label;

/**
 * A single tape label.
 *
 * The domain fields (labelCode, mediaType, mediaGeneration, barcodeType,
 * notes, location) are immutable. Persistence fields (id, timestamps, counts)
 * are nullable/zero until the label is stored and are populated by the
 * repository when hydrating rows from the database.
 */
final class Label
{
    public function __construct(
        public readonly string $labelCode,
        public readonly MediaType $mediaType,
        public readonly string $mediaGeneration,
        public readonly string $barcodeType = 'CODE39',
        public readonly ?string $notes = null,
        public readonly ?string $location = null,
        public readonly ?int $id = null,
        public readonly ?string $createdAt = null,
        public readonly ?string $printedAt = null,
        public readonly int $printCount = 0,
        public readonly ?int $batchId = null,
    ) {
    }
}
