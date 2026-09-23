<?php

declare(strict_types=1);

namespace App\Logging;

/**
 * Minimal file logger. Writes one line per event; never logs secrets.
 */
final class Logger
{
    private const LEVELS = ['debug' => 0, 'info' => 1, 'warning' => 2, 'error' => 3];

    public function __construct(
        private readonly string $directory,
        private readonly string $level = 'info',
    ) {
    }

    public function debug(string $message, array $context = []): void
    {
        $this->write('debug', $message, $context);
    }

    public function info(string $message, array $context = []): void
    {
        $this->write('info', $message, $context);
    }

    public function warning(string $message, array $context = []): void
    {
        $this->write('warning', $message, $context);
    }

    public function error(string $message, array $context = []): void
    {
        $this->write('error', $message, $context);
    }

    private function write(string $level, string $message, array $context): void
    {
        if (self::LEVELS[$level] < self::LEVELS[$this->level]) {
            return;
        }

        if (!is_dir($this->directory)) {
            @mkdir($this->directory, 0777, true);
        }

        $line = sprintf(
            "[%s] %s.%s %s %s\n",
            date('Y-m-d H:i:s'),
            strtoupper($level),
            $this->pid(),
            $message,
            $context === [] ? '' : json_encode($context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
        );

        @file_put_contents($this->directory . '/ltolabel.log', $line, FILE_APPEND | LOCK_EX);
    }

    private function pid(): string
    {
        return function_exists('getmypid') ? (string) getmypid() : '0';
    }
}
