<?php

declare(strict_types=1);

namespace App\Barcode;

use App\Support\ValidationResult;

/**
 * Validates that a barcode can actually be scanned reliably: valid data,
 * sufficient module width, quiet zone, bar height and fit within the label.
 *
 * Thresholds reflect the IBM LTO Ultrium cartridge label specification
 * (docs/RESEARCH.md); deviations produce errors or warnings instead of silent
 * unscannable output.
 */
final class BarcodeValidator
{
    public const MIN_MODULE_WIDTH_MM = 0.15;
    public const RECOMMENDED_MODULE_WIDTH_MM = 0.423;
    public const MIN_QUIET_ZONE_MM = 3.0;
    public const MIN_BAR_HEIGHT_MM = 11.1;

    public function validate(
        string $data,
        BarcodePattern $pattern,
        BarcodeSpec $spec,
        float $labelWidthMm,
        float $labelHeightMm,
    ): ValidationResult {
        $result = new ValidationResult();

        if ($data === '') {
            $result->addError('Barcode-Daten dürfen nicht leer sein.');
        }

        if ($spec->moduleWidthMm < self::MIN_MODULE_WIDTH_MM) {
            $result->addError(sprintf(
                'Modulbreite %.3f mm ist zu klein für zuverlässiges Scannen.',
                $spec->moduleWidthMm
            ));
        } elseif ($spec->moduleWidthMm < self::RECOMMENDED_MODULE_WIDTH_MM) {
            $result->addWarning(sprintf(
                'Modulbreite %.3f mm liegt unter dem LTO-Nennwert von %.3f mm.',
                $spec->moduleWidthMm,
                self::RECOMMENDED_MODULE_WIDTH_MM
            ));
        }

        if ($spec->quietZoneMm < self::MIN_QUIET_ZONE_MM) {
            $result->addError(sprintf(
                'Quiet Zone %.1f mm ist zu klein (mindestens %.1f mm).',
                $spec->quietZoneMm,
                self::MIN_QUIET_ZONE_MM
            ));
        }

        if ($spec->heightMm < self::MIN_BAR_HEIGHT_MM) {
            $result->addWarning(sprintf(
                'Balkenhöhe %.1f mm liegt unter dem LTO-Mindestwert von %.1f mm.',
                $spec->heightMm,
                self::MIN_BAR_HEIGHT_MM
            ));
        }

        $totalWidth = $pattern->totalWidthMm($spec);
        if ($totalWidth > $labelWidthMm) {
            $result->addError(sprintf(
                'Die Barcodebreite (%.2f mm) ist für diese Datenlänge zu groß für das Label (%.2f mm). '
                . 'Erhöhe die Labelbreite oder reduziere die Datenlänge.',
                $totalWidth,
                $labelWidthMm
            ));
        }

        if ($spec->heightMm > $labelHeightMm) {
            $result->addError(sprintf(
                'Die Barcodehöhe (%.1f mm) überschreitet die Labelhöhe (%.1f mm).',
                $spec->heightMm,
                $labelHeightMm
            ));
        }

        return $result;
    }
}
