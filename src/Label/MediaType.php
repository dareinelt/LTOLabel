<?php

declare(strict_types=1);

namespace App\Label;

enum MediaType: string
{
    case DATA = 'DATA';
    case CLEANING = 'CLEANING';

    public function label(): string
    {
        return match ($this) {
            self::DATA => 'Datenband',
            self::CLEANING => 'Cleaning Tape',
        };
    }
}
