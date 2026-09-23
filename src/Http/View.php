<?php

declare(strict_types=1);

namespace App\Http;

/**
 * Plain-PHP template renderer (no template engine dependency).
 */
final class View
{
    public function __construct(private readonly string $templatesDir)
    {
    }

    /**
     * @param array<string, mixed> $data
     */
    public function render(string $template, array $data = []): string
    {
        $file = rtrim($this->templatesDir, '/\\') . DIRECTORY_SEPARATOR . $template;

        if (!is_file($file)) {
            throw new \RuntimeException(sprintf('Template "%s" nicht gefunden.', $template));
        }

        extract($data, EXTR_SKIP);
        ob_start();
        include $file;

        return (string) ob_get_clean();
    }
}
