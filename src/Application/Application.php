<?php

declare(strict_types=1);

namespace App\Application;

use App\Barcode\BarcodeRegistry;
use App\Barcode\BarcodeSvgRenderer;
use App\Barcode\BarcodeValidator;
use App\Barcode\Code39BarcodeGenerator;
use App\Config\Config;
use App\Database\Database;
use App\Database\DatabaseFactory;
use App\Database\LabelRepository;
use App\Database\Migration;
use App\Http\Csrf;
use App\Http\Router;
use App\Http\View;
use App\Label\LabelGenerator;
use App\Label\LabelIdBuilder;
use App\Label\ValidatorFactory;
use App\Logging\Logger;
use App\Pdf\LabelRenderer;
use App\Pdf\PdfGenerator;
use App\Profile\Profile;
use App\Profile\ProfileFactory;

/**
 * Composition root: builds and wires the whole dependency graph manually.
 */
final class Application
{
    /**
     * @param array<string, Profile> $profiles
     */
    public function __construct(
        public readonly Config $config,
        public readonly Logger $logger,
        public readonly Database $database,
        public readonly LabelRepository $labels,
        public readonly LabelService $labelService,
        public readonly PrintService $printService,
        public readonly BarcodeRegistry $barcodes,
        public readonly BarcodeSvgRenderer $svg,
        public readonly Csrf $csrf,
        public readonly View $view,
        public readonly Router $router,
    ) {
    }

    public static function boot(string $baseDir): self
    {
        $config = new Config($baseDir);
        date_default_timezone_set($config->timezone());

        $logger = new Logger(
            self::resolvePath($baseDir, (string) ($config->logging()['path'] ?? 'data/logs')),
            (string) ($config->logging()['level'] ?? 'info'),
        );

        $database = (new DatabaseFactory($config))->create();
        (new Migration())->migrate($database);
        $labels = new LabelRepository($database);

        $profileFactory = new ProfileFactory();
        $profiles = [];
        foreach ($config->profiles() as $name => $data) {
            $profiles[$name] = $profileFactory->create($name, $data);
        }
        if ($profiles === []) {
            $profiles['dell_ml3'] = $profileFactory->create('dell_ml3', []);
        }

        $barcodes = new BarcodeRegistry();
        $barcodes->register(new Code39BarcodeGenerator());
        $barcodeValidator = new BarcodeValidator();
        $svg = new BarcodeSvgRenderer();

        $validatorFactory = new ValidatorFactory();
        $generator = new LabelGenerator(new LabelIdBuilder());

        $labelService = new LabelService(
            $profiles,
            $validatorFactory,
            $generator,
            $labels,
            $barcodes,
            $barcodeValidator,
        );

        $renderer = new LabelRenderer($barcodes);
        $pdf = new PdfGenerator($renderer);
        $printService = new PrintService($pdf, $labelService, $config);

        $view = new View($baseDir . '/templates');
        $csrf = new Csrf();
        $router = new Router();

        return new self(
            $config,
            $logger,
            $database,
            $labels,
            $labelService,
            $printService,
            $barcodes,
            $svg,
            $csrf,
            $view,
            $router,
        );
    }

    private static function resolvePath(string $baseDir, string $path): string
    {
        if ($path === '' || $path[0] === '/' || $path[0] === '\\' || preg_match('/^[A-Za-z]:/', $path)) {
            return $path;
        }

        return rtrim($baseDir, '/\\') . DIRECTORY_SEPARATOR . $path;
    }
}
