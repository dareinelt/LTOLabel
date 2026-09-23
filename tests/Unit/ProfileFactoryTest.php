<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Label\MediaType;
use App\Profile\ProfileFactory;
use PHPUnit\Framework\TestCase;

final class ProfileFactoryTest extends TestCase
{
    public function testDefaultsMatchResearchedLtoValues(): void
    {
        $profile = (new ProfileFactory())->create('dell_ml3', []);

        self::assertSame('dell_ml3', $profile->name);
        self::assertSame(102.0, $profile->labelWidthMm);
        self::assertSame(14.0, $profile->labelHeightMm);
        self::assertSame('CODE39', $profile->barcode->type);
        self::assertSame(0.423, $profile->barcode->moduleWidthMm);
        self::assertSame(2.75, $profile->barcode->wideNarrowRatio);
        self::assertSame(11.1, $profile->barcode->heightMm);

        $data = $profile->mediaType(MediaType::DATA);
        self::assertNotNull($data);
        self::assertSame(8, $data->totalLength);
        self::assertSame(6, $data->volserLength);
        self::assertSame('L8', $data->defaultMediaId);

        $cleaning = $profile->mediaType(MediaType::CLEANING);
        self::assertNotNull($cleaning);
        self::assertSame('CLN', $cleaning->prefix);
        self::assertSame('L1', $cleaning->suffix);
    }

    public function testConfigOverridesDefaults(): void
    {
        $profile = (new ProfileFactory())->create('custom', [
            'label' => ['width_mm' => 80.0, 'height_mm' => 20.0],
            'barcode' => ['type' => 'CODE39', 'module_width_mm' => 0.3],
            'media_types' => [
                'CLEANING' => ['prefix' => 'CLEAN', 'suffix' => 'L1', 'total_length' => 9],
            ],
        ]);

        self::assertSame(80.0, $profile->labelWidthMm);
        self::assertSame(0.3, $profile->barcode->moduleWidthMm);
        self::assertSame('CLEAN', $profile->mediaType(MediaType::CLEANING)->prefix);
    }
}
