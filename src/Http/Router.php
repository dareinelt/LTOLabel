<?php

declare(strict_types=1);

namespace App\Http;

/**
 * Tiny front-controller router. Handlers receive the current Request and return
 * a Response.
 */
final class Router
{
    /** @var array<string, array<string, callable(Request): Response>> */
    private array $routes = [];

    public function get(string $path, callable $handler): void
    {
        $this->add('GET', $path, $handler);
    }

    public function post(string $path, callable $handler): void
    {
        $this->add('POST', $path, $handler);
    }

    /**
     * @param callable(Request): Response $handler
     */
    public function add(string $method, string $path, callable $handler): void
    {
        $this->routes[strtoupper($method)][$path] = $handler;
    }

    public function dispatch(Request $request): Response
    {
        $method = $request->method();
        $path = $request->path();

        // Support HEAD as GET (no body is sent by Response::send anyway).
        if ($method === 'HEAD') {
            $method = 'GET';
        }

        $handler = $this->routes[$method][$path] ?? null;

        if ($handler === null) {
            $response = new Response();
            $response->setStatus(404);

            return $response->body('404 Not Found');
        }

        return $handler($request);
    }
}
