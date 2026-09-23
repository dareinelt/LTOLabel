<?php

declare(strict_types=1);

namespace App\Printing;

/**
 * Physical page + grid layout for sheet printing. All dimensions in mm.
 */
final class PageLayout
{
    public function __construct(
        public readonly float $widthMm,
        public readonly float $heightMm,
        public readonly float $marginTopMm,
        public readonly float $marginRightMm,
        public readonly float $marginBottomMm,
        public readonly float $marginLeftMm,
        public readonly int $columns,
        public readonly int $rows,
        public readonly float $gapHorizontalMm,
        public readonly float $gapVerticalMm,
        public readonly string $format,
        public readonly string $orientation,
    ) {
    }
}
