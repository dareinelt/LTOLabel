<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Label\CleaningLabelValidator;
use App\Label\Label;
use App\Label\MediaType;
use App\Profile\ProfileFactory;
use PHPUnit\Framework\TestCase;

final class CleaningLabelValidatorTest extends TestCase
{
    private CleaningLabelValidator $validator;

    protected function setUp(): void
    {
        $profile = (new ProfileFactory())->create('dell_ml3', []);
        $this->validator = new CleaningLabelValidator($profile->mediaType(MediaType::CLEANING));
    }

    private function label(string $code): Label
    {
        return new Label($code, MediaType::CLEANING, 'L1');
    }

    public function testValidCleaningLabel(): void
    {
        self::assertTrue($this->validator->validate($this->label('CLN001L1'))->isValid());
        self::assertTrue($this->validator->validate($this->label('CLN123L1'))->isValid());
    }

    public function testRejectsMissingClnPrefix(): void
    {
        self::assertFalse($this->validator->validate($this->label('ABC001L1'))->isValid());
    }

    public function testRejectsMissingL1Suffix(): void
    {
        self::assertFalse($this->validator->validate($this->label('CLN001L8'))->isValid());
    }

    public function testRejectsNonDigitSequence(): void
    {
        self::assertFalse($this->validator->validate($this->label('CLN00AL1'))->isValid());
    }

    public function testRejectsWrongMediaType(): void
    {
        $data = new Label('ABC123L8', MediaType::DATA, 'L8');
        self::assertFalse($this->validator->validate($data)->isValid());
    }
}
