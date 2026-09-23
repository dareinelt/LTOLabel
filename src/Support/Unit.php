<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Physical measurement conversions.
 *
 * PDFs are laid out in "points" (1 pt = 1/72 inch). All user-facing and
 * configuration values are expressed in millimetres or inches. This class is
 * the single place where those units are converted so that print dimensions
 * are always exact.
 */
final class Unit
{
    public const MM_PER_INCH = 25.4;
    public const PT_PER_INCH = 72.0;

    public static function mmToPt(float $mm): float
    {
        return $mm * self::PT_PER_INCH / self::MM_PER_INCH;
    }

    public static function ptToMm(float $pt): float
    {
        return $pt * self::MM_PER_INCH / self::PT_PER_INCH;
    }

    public static function inchToMm(float $inch): float
    {
        return $inch * self::MM_PER_INCH;
    }

    public static function mmToInch(float $mm): float
    {
        return $mm / self::MM_PER_INCH;
    }
}
