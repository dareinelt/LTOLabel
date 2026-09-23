<?php

declare(strict_types=1);

namespace App\Profile;

use App\Barcode\BarcodeSpec;
use App\Label\MediaType;

/**
 * Builds Profile objects from configuration arrays, applying researched
 * defaults when keys are absent.
 */
final class ProfileFactory
{
    /**
     * Standard LTO data-tape media identifiers (IBM LTO Ultrium spec).
     *
     * @var string[]
     */
    private const DATA_MEDIA_IDS = [
        'L1', 'L2', 'L3', 'L4', 'L5', 'L6', 'L7', 'L8', 'L9',
        'M8', 'LT', 'LU', 'LV', 'LW', 'LX', 'LY', 'LZ',
    ];

    /**
     * @param array<string, mixed> $data
     */
    public function create(string $name, array $data): Profile
    {
        $label = $data['label'] ?? [];
        $barcode = $data['barcode'] ?? [];
        $text = $data['text'] ?? [];
        $mediaTypesData = $data['media_types'] ?? [];

        $barcodeSpec = new BarcodeSpec(
            type: strtoupper((string) ($barcode['type'] ?? 'CODE39')),
            moduleWidthMm: (float) ($barcode['module_width_mm'] ?? 0.423),
            wideNarrowRatio: (float) ($barcode['wide_narrow_ratio'] ?? 2.75),
            heightMm: (float) ($barcode['height_mm'] ?? 11.1),
            quietZoneMm: (float) ($barcode['quiet_zone_mm'] ?? 4.0),
        );

        $mediaTypes = [];
        foreach (MediaType::cases() as $type) {
            $mediaTypes[$type->value] = $this->createMediaTypeProfile(
                $type,
                $mediaTypesData[$type->value] ?? []
            );
        }

        return new Profile(
            name: $name,
            labelWidthMm: (float) ($label['width_mm'] ?? 102.0),
            labelHeightMm: (float) ($label['height_mm'] ?? 14.0),
            barcode: $barcodeSpec,
            font: (string) ($text['font'] ?? 'Helvetica'),
            fontSizePt: (float) ($text['font_size_pt'] ?? 6.0),
            textPosition: (string) ($text['position'] ?? 'below'),
            showLabelCode: (bool) ($text['show_label_code'] ?? true),
            mediaTypes: $mediaTypes,
        );
    }

    /**
     * @param array<string, mixed> $data
     */
    private function createMediaTypeProfile(MediaType $type, array $data): MediaTypeProfile
    {
        $id = $data['id'] ?? [];
        $isData = $type === MediaType::DATA;

        return new MediaTypeProfile(
            type: $type,
            totalLength: (int) ($id['total_length'] ?? ($data['total_length'] ?? 8)),
            allowedChars: (string) ($data['allowed_chars'] ?? 'A-Z0-9'),
            mediaIds: array_values(array_map('strval', (array) ($data['media_ids'] ?? ($isData ? self::DATA_MEDIA_IDS : ['L1'])))),
            defaultMediaId: isset($data['default_media_id']) ? (string) $data['default_media_id'] : ($isData ? 'L8' : 'L1'),
            prefix: (string) ($data['prefix'] ?? ($isData ? '' : 'CLN')),
            volserLength: (int) ($id['volser_length'] ?? ($isData ? 6 : 0)),
            suffix: array_key_exists('suffix', $data) ? (string) $data['suffix'] : ($isData ? null : 'L1'),
            sequenceLength: (int) ($id['sequence_length'] ?? 3),
        );
    }
}
