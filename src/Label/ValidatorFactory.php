<?php

declare(strict_types=1);

namespace App\Label;

use App\Profile\MediaTypeProfile;

/**
 * Selects the correct LabelValidator for a media type, so DATA and CLEANING
 * labels are validated by their own rules.
 */
final class ValidatorFactory
{
    public function forMediaType(MediaTypeProfile $profile): LabelValidator
    {
        return match ($profile->type) {
            MediaType::DATA => new DataLabelValidator($profile),
            MediaType::CLEANING => new CleaningLabelValidator($profile),
        };
    }
}
