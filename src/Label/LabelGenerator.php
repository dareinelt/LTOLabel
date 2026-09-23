<?php

declare(strict_types=1);

namespace App\Label;

use App\Profile\MediaTypeProfile;

/**
 * Generates batches of Label value objects from a start number and count.
 */
final class LabelGenerator
{
    public function __construct(private readonly LabelIdBuilder $idBuilder)
    {
    }

    /**
     * @return Label[]
     */
    public function generate(
        MediaTypeProfile $profile,
        int $start,
        int $count,
        string $userPrefix = '',
        ?string $mediaId = null,
        ?string $notes = null,
        ?string $location = null,
    ): array {
        $labels = [];

        for ($i = 0; $i < $count; $i++) {
            $number = $start + $i;
            $code = $this->idBuilder->build($profile, $number, $userPrefix, $mediaId);
            $generation = $profile->type === MediaType::DATA
                ? ($mediaId ?? $profile->defaultMediaId ?? 'L8')
                : ($profile->suffix ?? 'L1');

            $labels[] = new Label(
                labelCode: $code,
                mediaType: $profile->type,
                mediaGeneration: $generation,
                barcodeType: 'CODE39',
                notes: $notes,
                location: $location,
            );
        }

        return $labels;
    }
}
