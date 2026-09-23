<?php

declare(strict_types=1);

namespace App\Http\Controller;

use App\Config\Config;
use App\Http\Csrf;
use App\Http\Response;
use App\Http\View;

/**
 * Base controller: template rendering with shared data, redirects and flash
 * messages.
 */
abstract class Controller
{
    public function __construct(
        protected readonly View $view,
        protected readonly Csrf $csrf,
        protected readonly Config $config,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    protected function render(string $template, array $data = [], int $status = 200): Response
    {
        $html = $this->view->render($template, array_merge([
            'appName' => $this->config->appName(),
            'csrf' => $this->csrf,
            'csrfField' => $this->csrf->field(),
            'flash' => $this->consumeFlash(),
        ], $data));

        return (new Response())->setStatus($status)->body($html);
    }

    protected function redirect(string $url): Response
    {
        return (new Response())->redirect($url);
    }

    protected function flash(string $message, string $type = 'info'): void
    {
        $_SESSION['_flash'] = ['message' => $message, 'type' => $type];
    }

    /**
     * @return array{message: string, type: string}|null
     */
    protected function consumeFlash(): ?array
    {
        if (empty($_SESSION['_flash'])) {
            return null;
        }

        $flash = $_SESSION['_flash'];
        unset($_SESSION['_flash']);

        return $flash;
    }
}
