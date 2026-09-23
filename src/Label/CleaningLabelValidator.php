<?php

declare(strict_types=1);

namespace App\Label;

use App\Profile\MediaTypeProfile;
use App\Support\ValidationResult;

/**
 * Validation rules for LTO cleaning-cartridge labels.
 *
 * Researched format (configurable): a fixed "CLN" prefix, a sequence number,
 * and the generation-independent "L1" universal-cleaning suffix (e.g.
 * "CLN001L1"). Cleaning cartridges are universal across LTO generations, which
 * is why the suffix is "L1" regardless of the generation being cleaned.
 */
final class CleaningLabelValidator implements LabelValidator
{
    public function __construct(private readonly MediaTypeProfile $profile)
    {
    }

    public function supports(MediaType $type): bool
    {
        return $type === MediaType::CLEANING;
    }

    public function validate(Label $label): ValidationResult
    {
        $result = new ValidationResult();
        $code = $label->labelCode;

        if ($label->mediaType !== MediaType::CLEANING) {
            $result->addError('Label ist keine Reinigungskassette.');

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

        $this->validatePrefix($code, $result);
        $this->validateSuffix($code, $result);
        $this->validateSequence($code, $result);

        return $result;
    }

    private function validatePrefix(string $code, ValidationResult $result): void
    {
        if (!str_starts_with($code, $this->profile->prefix)) {
            $result->addError(sprintf(
                'Cleaning-Label muss mit "%s" beginnen (Kennung für Reinigungskassetten).',
                $this->profile->prefix
            ));
        }
    }

    private function validateSuffix(string $code, ValidationResult $result): void
    {
        $suffix = $this->profile->suffix;
        if ($suffix !== null && !str_ends_with($code, $suffix)) {
            $result->addError(sprintf(
                'Cleaning-Label muss mit "%s" enden (universelle Cleaning-Kennung).',
                $suffix
            ));
        }
    }

    private function validateSequence(string $code, ValidationResult $result): void
    {
        $suffix = $this->profile->suffix ?? '';
        $body = substr($code, strlen($this->profile->prefix));
        $body = $suffix !== '' ? substr($body, 0, -strlen($suffix)) : $body;

        if ($body === '' || !ctype_digit($body)) {
            $result->addError(sprintf(
                'Cleaning-Label "%s" enthält keine gültige fortlaufende Nummer.',
                $code
            ));
        }
    }
}
