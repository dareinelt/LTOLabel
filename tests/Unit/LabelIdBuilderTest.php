<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Label\LabelIdBuilder;
use App\Profile\MediaTypeProfile;
use App\Label\MediaType;
use PHPUnit\Framework\TestCase;

final class LabelIdBuilderTest extends TestCase
{
    private LabelIdBuilder $builder;

    protected function setUp(): void
    {
        $this->builder = new LabelIdBuilder();
    }

    private function dataProfile(): MediaTypeProfile
    {
        return new MediaTypeProfile(
            type: MediaType::DATA,
            totalLength: 8,
            allowedChars: 'A-Z0-9',
            mediaIds: ['L8'],
            defaultMediaId: 'L8',
            prefix: '',
            volserLength: 6,
            suffix: null,
            sequenceLength: 3,
        );
    }

    private function cleaningProfile(): MediaTypeProfile
    {
        return new MediaTypeProfile(
            type: MediaType::CLEANING,
            totalLength: 8,
            allowedChars: 'A-Z0-9',
            mediaIds: ['L1'],
            defaultMediaId: 'L1',
            prefix: 'CLN',
            volserLength: 0,
            suffix: 'L1',
            sequenceLength: 3,
        );
    }

    public function testBuildDataPadsNumber(): void
    {
        self::assertSame('ARCH01L8', $this->builder->build($this->dataProfile(), 1, 'ARCH', 'L8'));
        self::assertSame('ARCH100L8', $this->builder->build($this->dataProfile(), 100, 'ARCH', 'L8'));
    }

    public function testBuildDataUsesDefaultMediaId(): void
    {
        self::assertSame('ABC001L8', $this->builder->build($this->dataProfile(), 1, 'ABC'));
    }

    public function testBuildCleaning(): void
    {
        self::assertSame('CLN001L1', $this->builder->build($this->cleaningProfile(), 1));
        self::assertSame('CLN123L1', $this->builder->build($this->cleaningProfile(), 123));
    }
}
