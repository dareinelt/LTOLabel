<?php

declare(strict_types=1);

use App\Application\Application;
use App\Http\Controller\ApiController;
use App\Http\Controller\DashboardController;
use App\Http\Controller\LabelController;
use App\Http\Controller\PrintController;
use App\Http\Request;
use App\Http\Response;

$baseDir = dirname(__DIR__);

require $baseDir . '/vendor/autoload.php';

$app = Application::boot($baseDir);
$router = $app->router;

$dashboard = new DashboardController($app->view, $app->csrf, $app->config, $app->labelService, $app->labels);
$label = new LabelController($app->view, $app->csrf, $app->config, $app->labelService, $app->labels, $app->barcodes, $app->svg);
$print = new PrintController($app->view, $app->csrf, $app->config, $app->printService, $app->labels, $app->labelService);
$api = new ApiController($app->view, $app->csrf, $app->config, $app->labelService, $app->labels);

$router->get('/', static fn (Request $r): Response => $dashboard->index($r));
$router->get('/labels', static fn (Request $r): Response => $label->index($r));
$router->get('/labels/create', static fn (Request $r): Response => $label->createForm($r));
$router->post('/labels/preview', static fn (Request $r): Response => $label->preview($r));
$router->post('/labels/create', static fn (Request $r): Response => $label->create($r));
$router->get('/print/sheet', static fn (Request $r): Response => $print->sheet($r));
$router->get('/print/single', static fn (Request $r): Response => $print->single($r));
$router->get('/print/test', static fn (Request $r): Response => $print->test($r));
$router->get('/print/history', static fn (Request $r): Response => $print->history($r));
$router->get('/api/labels', static fn (Request $r): Response => $api->index($r));
$router->post('/api/labels', static fn (Request $r): Response => $api->create($r));

$request = Request::fromGlobals();

try {
    if (in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
        $token = $request->input('_csrf');
        if (!is_string($token)) {
            $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
        }
        if (!$app->csrf->validate(is_string($token) ? $token : null)) {
            $response = (new Response())->setStatus(419)->body('Ungültiges CSRF-Token.');
            $response->send();
            exit;
        }
    }

    $response = $router->dispatch($request);
    $response->send();
} catch (\Throwable $e) {
    $app->logger->error('Unbehandelter Fehler: ' . $e->getMessage(), ['file' => $e->getFile(), 'line' => $e->getLine()]);
    $response = (new Response())->setStatus(500)->body('Interner Fehler.');
    $response->send();
}
