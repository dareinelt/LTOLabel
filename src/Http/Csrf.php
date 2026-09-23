<?php

declare(strict_types=1);

namespace App\Http;

/**
 * CSRF protection using a session-bound token.
 */
final class Csrf
{
    public function token(): string
    {
        $this->ensureSession();
        if (empty($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        }

        return (string) $_SESSION['_csrf'];
    }

    public function validate(?string $token): bool
    {
        $this->ensureSession();

        return is_string($token) && $token !== '' && hash_equals($this->token(), $token);
    }

    private function ensureSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public function field(): string
    {
        return '<input type="hidden" name="_csrf" value="' . e($this->token()) . '">';
    }
}
