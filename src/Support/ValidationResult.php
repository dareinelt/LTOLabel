<?php

declare(strict_types=1);

namespace App\Support;

/**
 * A collection of validation errors and warnings.
 *
 * Errors make a value invalid; warnings do not block the value but flag that
 * something deviates from a recommended requirement.
 */
final class ValidationResult
{
    /** @var string[] */
    private array $errors = [];

    /** @var string[] */
    private array $warnings = [];

    public function addError(string $error): self
    {
        $this->errors[] = $error;

        return $this;
    }

    public function addWarning(string $warning): self
    {
        $this->warnings[] = $warning;

        return $this;
    }

    /**
     * @return string[]
     */
    public function errors(): array
    {
        return $this->errors;
    }

    /**
     * @return string[]
     */
    public function warnings(): array
    {
        return $this->warnings;
    }

    public function isValid(): bool
    {
        return $this->errors === [];
    }

    public function merge(self $other): self
    {
        $merged = new self();
        $merged->errors = array_merge($this->errors, $other->errors);
        $merged->warnings = array_merge($this->warnings, $other->warnings);

        return $merged;
    }
}
