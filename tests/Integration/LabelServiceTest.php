<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Application\BatchRequest;
use App\Application\LabelService;
use App\Barcode\BarcodeRegistry;
use App\Barcode\BarcodeValidator;
use App\Barcode\Code39BarcodeGenerator;
use App\Database\Database;
use App\Database\LabelRepository;
use App\Database\Migration;
use App\Label\LabelGenerator;
use App\Label\LabelIdBuilder;
use App\Label\MediaType;
use App\Label\ValidatorFactory;
use App\Profile\ProfileFactory;
use PDO;
use PHPUnit\Framework\TestCase;

final class LabelServiceTest extends TestCase
{
    private LabelService $service;
    private LabelRepository $repository;

    protected function setUp(): void
    {
        $db = new Database(new PDO('sqlite::memory:'));
        (new Migration())->migrate($db);
        $this->repository = new LabelRepository($db);

        $barcodes = new BarcodeRegistry();
        $barcodes->register(new Code39BarcodeGenerator());

        $this->service = new LabelService(
            ['dell_ml3' => (new ProfileFactory())->create('dell_ml3', [])],
            new ValidatorFactory(),
            new LabelGenerator(new LabelIdBuilder()),
            $this->repository,
            $barcodes,
            new BarcodeValidator(),
        );
    }

    public function testCreateDataBatch(): void
    {
        $result = $this->service->createBatch(new BatchRequest(
            profileName: 'dell_ml3',
            mediaType: MediaType::DATA,
            start: 1,
            count: 2,
            prefix: 'ARCH',
            mediaId: 'L8',
        ));

        self::assertSame([], $result['errors']);
        self::assertSame([], $result['skipped']);
        self::assertCount(2, $result['created']);
        self::assertSame(2, $this->repository->countAll());
    }

    public function testDuplicateHandlingSkips(): void
    {
        $this->service->createBatch(new BatchRequest(
            profileName: 'dell_ml3',
            mediaType: MediaType::DATA,
            start: 1,
            count: 2,
            prefix: 'ARCH',
            mediaId: 'L8',
        ));

        $result = $this->service->createBatch(new BatchRequest(
            profileName: 'dell_ml3',
            mediaType: MediaType::DATA,
            start: 1,
            count: 2,
            prefix: 'ARCH',
            mediaId: 'L8',
        ));

        self::assertCount(0, $result['created']);
        self::assertCount(2, $result['skipped']);
        self::assertSame(2, $this->repository->countAll());
    }

    public function testCreateCleaningBatch(): void
    {
        $result = $this->service->createBatch(new BatchRequest(
            profileName: 'dell_ml3',
            mediaType: MediaType::CLEANING,
            start: 1,
            count: 3,
        ));

        self::assertSame([], $result['errors']);
        self::assertCount(3, $result['created']);
        self::assertSame('CLN001L1', $result['created'][0]->labelCode);
        self::assertSame('CLN003L1', $result['created'][2]->labelCode);
    }

    public function testInvalidBatchReturnsErrors(): void
    {
        $result = $this->service->createBatch(new BatchRequest(
            profileName: 'dell_ml3',
            mediaType: MediaType::DATA,
            start: 1,
            count: 1,
            prefix: 'TOOLONG',
            mediaId: 'L8',
        ));

        self::assertNotSame([], $result['errors']);
        self::assertCount(0, $result['created']);
    }
}
