<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Label\DataLabelValidator;
use App\Label\Label;
use App\Label\MediaType;
use App\Profile\ProfileFactory;
use PHPUnit\Framework\TestCase;

final class DataLabelValidatorTest extends TestCase
{
    private DataLabelValidator $validator;

    protected function setUp(): void
    {
        $profile = (new ProfileFactory())->create('dell_ml3', []);
        $this->validator = new DataLabelValidator($profile->mediaType(MediaType::DATA));
    }

    private function label(string $code): Label
    {
        return new Label($code, MediaType::DATA, substr($code, -2));
    }

    public function testValidEightCharacterLabel(): void
    {
        self::assertTrue($this->validator->validate($this->label('ABC123L8'))->isValid());
        self::assertTrue($this->validator->validate($this->label('BACKUPL9'))->isValid());
        self::assertTrue($this->validator->validate($this->label('ABC123M8'))->isValid());
    }

    public function testRejectsWrongLength(): void
    {
        self::assertFalse($this->validator->validate($this->label('ABC123'))->isValid());
        self::assertFalse($this->validator->validate($this->label('ABC123L80'))->isValid());
    }

    public function testRejectsInvalidMediaId(): void
    {
        self::assertFalse($this->validator->validate($this->label('ABC123XX'))->isValid());
    }

    public function testRejectsLowercaseCharacters(): void
    {
        self::assertFalse($this->validator->validate($this->label('abc123L8'))->isValid());
    }

    public function testRejectsWrongMediaType(): void
    {
        $cleaning = new Label('CLN001L1', MediaType::CLEANING, 'L1');
        self::assertFalse($this->validator->validate($cleaning)->isValid());
    }
}
