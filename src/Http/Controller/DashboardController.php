<?php

declare(strict_types=1);

namespace App\Http\Controller;

use App\Application\LabelService;
use App\Config\Config;
use App\Database\LabelRepository;
use App\Http\Csrf;
use App\Http\Request;
use App\Http\Response;
use App\Http\View;

final class DashboardController extends Controller
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
        return $this->render('dashboard.php', [
            'count' => $this->repository->countAll(),
            'recent' => $this->repository->recent(8),
            'history' => $this->repository->printHistory(8),
            'profiles' => $this->labels->profiles(),
            'defaultProfileName' => $this->defaultProfileName(),
        ]);
    }

    private function defaultProfileName(): string
    {
        $names = $this->labels->profileNames();
        $preferred = $this->config->defaultProfileName();

        return in_array($preferred, $names, true) ? $preferred : ($names[0] ?? 'dell_ml3');
    }
}
