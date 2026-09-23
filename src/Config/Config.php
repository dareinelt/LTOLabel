<?php

declare(strict_types=1);

namespace App\Config;

use Symfony\Component\Yaml\Yaml;

/**
 * Central configuration access.
 *
 * Loads config/config.yaml and overlays config/config.local.yaml (which is
 * gitignored) when present. All values are read-only and resolved relative to
 * the project base directory.
 */
final class Config
{
    /** @var array<string, mixed> */
    private array $data;

    public function __construct(private readonly string $baseDir)
    {
        $this->data = $this->load();
    }

    public function baseDir(): string
    {
        return $this->baseDir;
    }

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return $this->data;
    }

    /**
     * @return array<string, mixed>
     */
    public function database(): array
    {
        return $this->data['database'] ?? [];
    }

    /**
     * @return array<string, mixed>
     */
    public function logging(): array
    {
        return $this->data['logging'] ?? [];
    }

    /**
     * @return array<string, mixed>
     */
    public function page(): array
    {
        return $this->data['page'] ?? [];
    }

    public function printerDpi(): int
    {
        return (int) ($this->data['printer']['dpi'] ?? 600);
    }

    public function appName(): string
    {
        return (string) ($this->data['app']['name'] ?? 'LTOLabel');
    }

    public function timezone(): string
    {
        return (string) ($this->data['app']['timezone'] ?? 'UTC');
    }

    public function defaultProfileName(): string
    {
        return (string) ($this->data['default_profile'] ?? 'dell_ml3');
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function profiles(): array
    {
        return $this->data['profiles'] ?? [];
    }

    /**
     * @return array<string, mixed>
     */
    private function load(): array
    {
        $data = $this->parse($this->baseDir . '/config/config.yaml');

        $local = $this->baseDir . '/config/config.local.yaml';
        if (is_file($local)) {
            $data = array_replace_recursive($data, $this->parse($local));
        }

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    private function parse(string $file): array
    {
        if (!is_file($file)) {
            return [];
        }

        $data = Yaml::parseFile($file);

        return is_array($data) ? $data : [];
    }
}
