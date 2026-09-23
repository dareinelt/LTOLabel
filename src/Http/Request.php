<?php

declare(strict_types=1);

namespace App\Http;

/**
 * Immutable wrapper around the HTTP request superglobals.
 */
final class Request
{
    /** @var array<string, mixed> */
    private array $query;

    /** @var array<string, mixed> */
    private array $body;

    /**
     * @param array<string, mixed> $query
     * @param array<string, mixed> $body
     */
    public function __construct(
        array $query,
        array $body,
        private readonly string $method,
        private readonly string $uri,
    ) {
        $this->query = $query;
        $this->body = $body;
    }

    public static function fromGlobals(): self
    {
        $method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
        $uri = (string) ($_SERVER['REQUEST_URI'] ?? '/');

        $query = $_GET;
        $body = $_POST;
        if (in_array($method, ['POST', 'PUT', 'PATCH'], true)) {
            $contentType = (string) ($_SERVER['CONTENT_TYPE'] ?? '');
            if (str_contains($contentType, 'application/json')) {
                $raw = file_get_contents('php://input');
                $decoded = json_decode($raw === false ? '' : $raw, true);
                if (is_array($decoded)) {
                    $body = $decoded;
                }
            }
        }

        return new self($query, $body, $method, $uri);
    }

    public function method(): string
    {
        return $this->method;
    }

    public function isPost(): bool
    {
        return $this->method === 'POST';
    }

    public function path(): string
    {
        $path = parse_url($this->uri, PHP_URL_PATH);
        if (!is_string($path) || $path === '') {
            return '/';
        }

        // Strip a leading base path segment if present (front-controller setup).
        return rtrim($path, '/') ?: '/';
    }

    public function query(string $key, mixed $default = null): mixed
    {
        return $this->query[$key] ?? $default;
    }

    public function input(string $key, mixed $default = null): mixed
    {
        return $this->body[$key] ?? $this->query[$key] ?? $default;
    }

    public function post(string $key, mixed $default = null): mixed
    {
        return $this->body[$key] ?? $default;
    }

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return array_merge($this->query, $this->body);
    }

    public function int(string $key, int $default = 0): int
    {
        $value = $this->input($key, $default);

        return is_numeric($value) ? (int) $value : $default;
    }

    public function string(string $key, string $default = ''): string
    {
        $value = $this->input($key, $default);

        return is_scalar($value) ? (string) $value : $default;
    }
}
