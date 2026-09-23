<?php

declare(strict_types=1);

namespace App\Http\Controller;

use App\Application\LabelService;
use App\Application\PrintService;
use App\Config\Config;
use App\Database\LabelRepository;
use App\Http\Csrf;
use App\Http\Request;
use App\Http\Response;
use App\Http\View;
use App\Label\Label;

final class PrintController extends Controller
{
    public function __construct(
        View $view,
        Csrf $csrf,
        Config $config,
        private readonly PrintService $print,
        private readonly LabelRepository $repository,
        private readonly LabelService $labels,
    ) {
        parent::__construct($view, $csrf, $config);
    }

    public function sheet(Request $request): Response
    {
        $ids = $this->parseIds($request->query('ids', ''));
        $labels = $this->loadLabels($ids);

        if ($labels === []) {
            $this->flash('Keine Labels zum Drucken gefunden.', 'warning');

            return $this->redirect('/labels');
        }

        $profileName = $this->resolveProfileName($request);
        $pdf = $this->print->renderSheet($labels, $profileName);
        $this->registerPrints($labels);

        return (new Response())->pdf($this->print->toString($pdf), 'ltolabel-sheet.pdf');
    }

    public function single(Request $request): Response
    {
        $id = $request->int('id');
        $label = $this->repository->findById($id);

        if ($label === null) {
            $this->flash('Label nicht gefunden.', 'warning');

            return $this->redirect('/labels');
        }

        $pdf = $this->print->renderSingle($label, $this->resolveProfileName($request));
        $this->repository->registerPrint($id);

        return (new Response())->pdf($this->print->toString($pdf), $label->labelCode . '.pdf');
    }

    public function test(Request $request): Response
    {
        $pdf = $this->print->renderTestSheet($this->resolveProfileName($request));

        return (new Response())->pdf($this->print->toString($pdf), 'ltolabel-test.pdf');
    }

    public function history(Request $request): Response
    {
        $filter = strtoupper($request->string('media_type', 'ALL'));
        $history = $this->repository->printHistory(500);

        if ($filter !== 'ALL') {
            $history = array_values(array_filter(
                $history,
                static fn (Label $label): bool => $label->mediaType->value === $filter
            ));
        }

        return $this->render('print/history.php', ['history' => $history, 'filter' => $filter]);
    }

    /**
     * @return int[]
     */
    private function parseIds(mixed $ids): array
    {
        $parts = is_array($ids) ? array_map('strval', $ids) : explode(',', (string) $ids);

        $parsed = [];
        foreach ($parts as $part) {
            $part = trim($part);
            if ($part !== '' && ctype_digit($part)) {
                $parsed[] = (int) $part;
            }
        }

        return array_values(array_unique($parsed));
    }

    /**
     * @param int[] $ids
     * @return Label[]
     */
    private function loadLabels(array $ids): array
    {
        $labels = [];
        foreach ($ids as $id) {
            $label = $this->repository->findById($id);
            if ($label !== null) {
                $labels[] = $label;
            }
        }

        return $labels;
    }

    /**
     * @param Label[] $labels
     */
    private function registerPrints(array $labels): void
    {
        foreach ($labels as $label) {
            if ($label->id !== null) {
                $this->repository->registerPrint($label->id);
            }
        }
    }

    private function resolveProfileName(Request $request): string
    {
        $names = $this->labels->profileNames();
        $profileName = $request->string('profile', $this->config->defaultProfileName());

        return in_array($profileName, $names, true) ? $profileName : ($names[0] ?? 'dell_ml3');
    }
}
