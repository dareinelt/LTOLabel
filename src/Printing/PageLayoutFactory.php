<?php

declare(strict_types=1);

namespace App\Printing;

/**
 * Builds a PageLayout from the "page" configuration section.
 */
final class PageLayoutFactory
{
    /** @var array<string, array{0: float, 1: float}> width x height in mm */
    private const FORMATS = [
        'A4' => [210.0, 297.0],
        'LETTER' => [215.9, 279.4],
        'A5' => [148.0, 210.0],
        'A6' => [105.0, 148.0],
    ];

    /**
     * @param array<string, mixed> $page
     */
    public static function fromConfig(array $page): PageLayout
    {
        $format = strtoupper((string) ($page['format'] ?? 'A4'));
        $orientation = strtolower((string) ($page['orientation'] ?? 'portrait'));

        $width = (float) ($page['width_mm'] ?? 0);
        $height = (float) ($page['height_mm'] ?? 0);

        if ($width <= 0 || $height <= 0) {
            [$width, $height] = self::FORMATS[$format] ?? self::FORMATS['A4'];
        }

        if ($orientation === 'landscape') {
            [$width, $height] = [$height, $width];
        }

        $grid = $page['grid'] ?? [];

        return new PageLayout(
            widthMm: $width,
            heightMm: $height,
            marginTopMm: (float) ($page['margin_top_mm'] ?? 10.0),
            marginRightMm: (float) ($page['margin_right_mm'] ?? 10.0),
            marginBottomMm: (float) ($page['margin_bottom_mm'] ?? 10.0),
            marginLeftMm: (float) ($page['margin_left_mm'] ?? 10.0),
            columns: max(1, (int) ($grid['columns'] ?? 2)),
            rows: max(1, (int) ($grid['rows'] ?? 8)),
            gapHorizontalMm: (float) ($grid['gap_horizontal_mm'] ?? 5.0),
            gapVerticalMm: (float) ($grid['gap_vertical_mm'] ?? 5.0),
            format: $format,
            orientation: $orientation,
        );
    }
}
