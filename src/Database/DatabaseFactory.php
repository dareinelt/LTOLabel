<?php

declare(strict_types=1);

namespace App\Database;

use App\Config\Config;
use PDO;

/**
 * Creates a Database connection from configuration. Supports SQLite (default,
 * single-installation) and MySQL/MariaDB.
 */
final class DatabaseFactory
{
    public function __construct(private readonly Config $config)
    {
    }

    public function create(): Database
    {
        $db = $this->config->database();
        $driver = strtolower((string) ($db['driver'] ?? 'sqlite'));

        $pdo = $driver === 'mysql'
            ? $this->createMySql($db['mysql'] ?? [])
            : $this->createSqlite($db['sqlite'] ?? []);

        return new Database($pdo);
    }

    /**
     * @param array<string, mixed> $c
     */
    private function createSqlite(array $c): PDO
    {
        $path = (string) ($c['path'] ?? 'data/ltolabel.sqlite');
        $fullPath = $this->resolvePath($path);

        $dir = dirname($fullPath);
        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }

        $pdo = new PDO('sqlite:' . $fullPath, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        $pdo->exec('PRAGMA foreign_keys = ON');

        return $pdo;
    }

    /**
     * @param array<string, mixed> $c
     */
    private function createMySql(array $c): PDO
    {
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            (string) ($c['host'] ?? '127.0.0.1'),
            (int) ($c['port'] ?? 3306),
            (string) ($c['database'] ?? 'ltolabel'),
            (string) ($c['charset'] ?? 'utf8mb4')
        );

        return new PDO($dsn, (string) ($c['user'] ?? 'ltolabel'), (string) ($c['password'] ?? ''), [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }

    private function resolvePath(string $path): string
    {
        if ($path === '' || $path[0] === '/' || $path[0] === '\\' || preg_match('/^[A-Za-z]:/', $path)) {
            return $path;
        }

        return rtrim($this->config->baseDir(), '/\\') . DIRECTORY_SEPARATOR . $path;
    }
}
