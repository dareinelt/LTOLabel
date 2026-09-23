<?php

declare(strict_types=1);

namespace App\Label;

use App\Support\ValidationResult;

/**
 * Validates a label against the rules of a specific media type.
 *
 * Different media types (DATA, CLEANING, ...) have different validation rules;
 * implementations are selected per media type so no global, media-type-blind
 * validation exists.
 */
interface LabelValidator
{
    public function supports(MediaType $type): bool;

    public function validate(Label $label): ValidationResult;
}
