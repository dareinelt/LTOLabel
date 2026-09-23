<?php

declare(strict_types=1);

namespace App\Http;

/**
 * Simple HTTP response.
 */
final class Response
{
    private int $status = 200;

    /** @var array<string, string> */
    private array $headers = ['Content-Type' => 'text/html; charset=UTF-8'];

    private string $body = '';

    public function setStatus(int $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function header(string $name, string $value): self
    {
        $this->headers[$name] = $value;

        return $this;
    }

    public function body(string $body): self
    {
        $this->body = $body;

        return $this;
    }

    public function redirect(string $url, int $status = 302): self
    {
        return $this->header('Location', $url)->setStatus($status);
    }

    public function json(mixed $data, int $status = 200): self
    {
        $this->status = $status;
        $this->headers['Content-Type'] = 'application/json; charset=UTF-8';
        $this->body = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return $this;
    }

    public function pdf(string $content, string $filename = 'labels.pdf'): self
    {
        $this->headers['Content-Type'] = 'application/pdf';
        $this->headers['Content-Disposition'] = 'inline; filename="' . $filename . '"';
        $this->body = $content;

        return $this;
    }

    public function send(): void
    {
        http_response_code($this->status);
        foreach ($this->headers as $name => $value) {
            header($name . ': ' . $value);
        }
        echo $this->body;
    }
}
