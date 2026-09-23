<?php

declare(strict_types=1);

namespace App\Http\Controller;

use App\Application\BatchRequest;
use App\Application\LabelService;
use App\Config\Config;
use App\Database\LabelRepository;
use App\Http\Csrf;
use App\Http\Request;
use App\Http\Response;
use App\Http\View;
use App\Label\Label;
use App\Label\MediaType;

final class ApiController extends Controller
{
    public function __construct(
        View $view,
        Csrf $csrf,
        Config $config,
        private readonly LabelService $labels,
        private readonly LabelRepository $repository,
    ) {
        parent::__construct($view, $csrf, $config);
    }

    public function index(Request $request): Response
    {
        $where = [];
        $mediaType = strtoupper($request->query('media_type', 'ALL'));
        if ($mediaType !== 'ALL') {
            $where['media_type'] = $mediaType;
        }

        $labels = array_map(
            fn (Label $label): array => $this->toArray($label),
            $this->repository->findAll($where)
        );

        return (new Response())->json(['labels' => $labels]);
    }

    public function create(Request $request): Response
    {
        $body = $request->all();

        $mediaTypeValue = strtoupper((string) ($body['media_type'] ?? 'DATA'));
        if (!in_array($mediaTypeValue, [MediaType::DATA->value, MediaType::CLEANING->value], true)) {
            return (new Response())->json(['errors' => ['Ungültiger Medientyp.']], 422);
        }
        $mediaType = MediaType::from($mediaTypeValue);

        $profileName = (string) ($body['profile'] ?? $this->config->defaultProfileName());
        if (!in_array($profileName, $this->labels->profileNames(), true)) {
            $profileName = $this->labels->profileNames()[0] ?? 'dell_ml3';
        }

        $batch = new BatchRequest(
            profileName: $profileName,
            mediaType: $mediaType,
            start: (int) ($body['start'] ?? 1),
            count: (int) ($body['count'] ?? 1),
            prefix: $mediaType === MediaType::DATA ? strtoupper((string) ($body['prefix'] ?? '')) : '',
            mediaId: isset($body['media_id']) ? strtoupper((string) $body['media_id']) : null,
            notes: isset($body['notes']) ? (string) $body['notes'] : null,
            location: isset($body['location']) ? (string) $body['location'] : null,
            duplicateAction: (string) ($body['duplicate_action'] ?? 'skip'),
        );

        $result = $this->labels->createBatch($batch);

        if ($result['errors'] !== []) {
            return (new Response())->json(['errors' => $result['errors']], 422);
        }

        return (new Response())->json([
            'created' => array_map(fn (Label $label): array => $this->toArray($label), $result['created']),
            'skipped' => $result['skipped'],
            'batch_id' => $result['batchId'],
        ], 201);
    }

    /**
     * @return array<string, mixed>
     */
    private function toArray(Label $label): array
    {
        return [
            'id' => $label->id,
            'label_code' => $label->labelCode,
            'media_type' => $label->mediaType->value,
            'media_generation' => $label->mediaGeneration,
            'barcode_type' => $label->barcodeType,
            'created_at' => $label->createdAt,
            'printed_at' => $label->printedAt,
            'print_count' => $label->printCount,
        ];
    }
}
