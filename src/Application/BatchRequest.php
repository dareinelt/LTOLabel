<?php

declare(strict_types=1);

namespace App\Application;

use App\Label\MediaType;

/**
 * Input value object for a batch label-creation request.
 */
final class BatchRequest
{
    public function __construct(
        public readonly string $profileName,
        public readonly MediaType $mediaType,
        public readonly int $start,
        public readonly int $count,
        public readonly string $prefix = '',
        public readonly ?string $mediaId = null,
        public readonly ?string $notes = null,
        public readonly ?string $location = null,
        public readonly string $duplicateAction = 'skip',
    ) {
    }
}
