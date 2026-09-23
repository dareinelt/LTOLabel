<?php

declare(strict_types=1);

namespace App\Label;

use App\Profile\MediaTypeProfile;
use App\Support\ValidationResult;

/**
 * Validation rules for LTO data-tape labels.
 *
 * Format (researched, configurable): VOLSER (N alphanumeric chars) followed by
 * a two-character media identifier (e.g. "L8", "M8"). Default total length 8.
 */
final class DataLabelValidator implements LabelValidator
{
    public function __construct(private readonly MediaTypeProfile $profile)
    {
    }

    public function supports(MediaType $type): bool
    {
        return $type === MediaType::DATA;
    }

    public function validate(Label $label): ValidationResult
    {
        $result = new ValidationResult();
        $code = $label->labelCode;

        if ($label->mediaType !== MediaType::DATA) {
            $result->addError('Label ist kein Datenband.');

            return $result;
        }

        if (strlen($code) !== $this->profile->totalLength) {
            $result->addError(sprintf(
                'Label-ID "%s" hat %d Zeichen; erwartet sind genau %d Zeichen.',
                $code,
                strlen($code),
                $this->profile->totalLength
            ));
        }

        $this->validateCharacters($code, $result);
        $this->validateMediaId($code, $result);

        return $result;
    }

    private function validateCharacters(string $code, ValidationResult $result): void
    {
        $pattern = '/^[' . $this->profile->allowedChars . ']+$/';
        if (!preg_match($pattern, $code)) {
            $result->addError(sprintf(
                'Label-ID "%s" enthält unzulässige Zeichen. Erlaubt sind nur [%s].',
                $code,
                $this->profile->allowedChars
            ));
        }
    }

    private function validateMediaId(string $code, ValidationResult $result): void
    {
        $mediaId = substr($code, -2);
        if (!in_array($mediaId, $this->profile->mediaIds, true)) {
            $result->addError(sprintf(
                'Medienkennung "%s" ist nicht erlaubt. Zulässig sind: %s.',
                $mediaId,
                implode(', ', $this->profile->mediaIds)
            ));
        }
    }
}
