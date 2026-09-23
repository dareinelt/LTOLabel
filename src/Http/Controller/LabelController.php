<?php

declare(strict_types=1);

namespace App\Http\Controller;

use App\Application\BatchRequest;
use App\Application\LabelService;
use App\Barcode\BarcodeRegistry;
use App\Barcode\BarcodeSvgRenderer;
use App\Config\Config;
use App\Database\LabelRepository;
use App\Http\Csrf;
use App\Http\Request;
use App\Http\Response;
use App\Http\View;
use App\Label\Label;
use App\Label\MediaType;

final class LabelController extends Controller
{
    private const MAX_COUNT = 1000;

    public function __construct(
        View $view,
        Csrf $csrf,
        Config $config,
        private readonly LabelService $labels,
        private readonly LabelRepository $repository,
        private readonly BarcodeRegistry $barcodes,
        private readonly BarcodeSvgRenderer $svg,
    ) {
        parent::__construct($view, $csrf, $config);
    }

    public function index(Request $request): Response
    {
        $filters = [
            'media_type' => strtoupper($request->string('media_type', 'ALL')),
            'media_generation' => strtoupper($request->string('media_generation', 'ALL')),
            'search' => $request->string('search', ''),
            'printed' => $request->string('printed', ''),
        ];

        $where = [];
        if ($filters['media_type'] !== 'ALL') {
            $where['media_type'] = $filters['media_type'];
        }
        if ($filters['media_generation'] !== 'ALL') {
            $where['media_generation'] = $filters['media_generation'];
        }
        if ($filters['search'] !== '') {
            $where['search'] = $filters['search'];
        }
        if ($filters['printed'] !== '') {
            $where['printed'] = $filters['printed'];
        }

        return $this->render('label/index.php', [
            'labels' => $this->repository->findAll($where),
            'filters' => $filters,
            'generations' => $this->repository->distinctGenerations(),
        ]);
    }

    public function createForm(Request $request): Response
    {
        return $this->render('label/create.php', [
            'profiles' => $this->labels->profiles(),
            'profileName' => $this->resolveProfileName($request),
            'profilesArray' => $this->labels->profileNames(),
        ]);
    }

    public function preview(Request $request): Response
    {
        [$batch, $error] = $this->batchFromRequest($request);
        if ($error !== null) {
            $this->flash($error, 'error');

            return $this->redirect('/labels/create');
        }

        $plan = $this->labels->planBatch($batch);
        $profile = $plan['profile'];

        $preview = [];
        foreach ($plan['labels'] as $i => $label) {
            $pattern = $this->barcodes->generator($profile->barcode->type)->generate($label->labelCode, $profile->barcode);
            $preview[] = [
                'label' => $label,
                'svg' => $this->svg->render($pattern, $profile->barcode),
                'result' => $plan['results'][$i],
            ];
        }

        return $this->render('label/preview.php', [
            'profile' => $profile,
            'mediaTypeProfile' => $plan['mediaTypeProfile'],
            'preview' => $preview,
            'duplicates' => $plan['duplicates'],
            'form' => $this->formFromBatch($batch),
        ]);
    }

    public function create(Request $request): Response
    {
        [$batch, $error] = $this->batchFromRequest($request, true);
        if ($error !== null) {
            $this->flash($error, 'error');

            return $this->redirect('/labels/create');
        }

        $result = $this->labels->createBatch($batch);

        if ($result['errors'] !== []) {
            $this->flash(implode(' ', $result['errors']), 'error');

            return $this->redirect('/labels/create');
        }

        $created = count($result['created']);
        $skipped = count($result['skipped']);
        $message = sprintf('%d Label erstellt.', $created);
        if ($skipped > 0) {
            $message .= sprintf(' %d Duplikat(e) übersprungen.', $skipped);
        }
        $this->flash($message, $created > 0 ? 'success' : 'warning');

        return $this->redirect('/labels');
    }

    /**
     * @return array{0: ?BatchRequest, 1: ?string}
     */
    private function batchFromRequest(Request $request, bool $isCreate = false): array
    {
        $mediaTypeValue = strtoupper($request->string('media_type', 'DATA'));
        if (!in_array($mediaTypeValue, [MediaType::DATA->value, MediaType::CLEANING->value], true)) {
            return [null, 'Ungültiger Medientyp.'];
        }
        $mediaType = MediaType::from($mediaTypeValue);

        $profileName = $this->resolveProfileName($request);
        $start = $request->int('start', 1);
        $count = $request->int('count', 1);

        if ($count < 1 || $count > self::MAX_COUNT) {
            return [null, sprintf('Anzahl muss zwischen 1 und %d liegen.', self::MAX_COUNT)];
        }

        $prefix = strtoupper(trim($request->string('prefix', '')));
        $mediaId = strtoupper(trim($request->string('media_id', '')));
        $notes = $request->string('notes', '');
        $location = $request->string('location', '');
        $duplicateAction = $isCreate ? $request->string('duplicate_action', 'skip') : 'skip';

        if ($mediaType === MediaType::DATA && $prefix === '') {
            return [null, 'Für Datenbänder ist ein Präfix erforderlich.'];
        }

        $batch = new BatchRequest(
            profileName: $profileName,
            mediaType: $mediaType,
            start: $start,
            count: $count,
            prefix: $mediaType === MediaType::DATA ? $prefix : '',
            mediaId: $mediaType === MediaType::DATA && $mediaId !== '' ? $mediaId : null,
            notes: $notes !== '' ? $notes : null,
            location: $location !== '' ? $location : null,
            duplicateAction: $duplicateAction,
        );

        return [$batch, null];
    }

    /**
     * @return array<string, string>
     */
    private function formFromBatch(BatchRequest $batch): array
    {
        return [
            'profile' => $batch->profileName,
            'media_type' => $batch->mediaType->value,
            'prefix' => $batch->prefix,
            'media_id' => $batch->mediaId ?? '',
            'start' => (string) $batch->start,
            'count' => (string) $batch->count,
            'notes' => $batch->notes ?? '',
            'location' => $batch->location ?? '',
        ];
    }

    private function resolveProfileName(Request $request): string
    {
        $names = $this->labels->profileNames();
        $profileName = $request->string('profile', $this->config->defaultProfileName());

        return in_array($profileName, $names, true) ? $profileName : ($names[0] ?? 'dell_ml3');
    }
}
