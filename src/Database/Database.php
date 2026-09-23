<?php

declare(strict_types=1);

namespace App\Database;

use PDO;
use PDOStatement;

/**
 * Thin wrapper around PDO providing the query helpers used by repositories.
 */
final class Database
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function pdo(): PDO
    {
        return $this->pdo;
    }

    public function driver(): string
    {
        return (string) $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    }

    /**
     * @param array<int|string, mixed> $params
     */
    public function query(string $sql, array $params = []): PDOStatement
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt;
    }

    /**
     * @param array<int|string, mixed> $params
     */
    public function execute(string $sql, array $params = []): int
    {
        return $this->query($sql, $params)->rowCount();
    }

    public function lastInsertId(): int
    {
        return (int) $this->pdo->lastInsertId();
    }

    public function beginTransaction(): bool
    {
        return $this->pdo->beginTransaction();
    }

    public function commit(): bool
    {
        return $this->pdo->commit();
    }

    public function rollBack(): bool
    {
        return $this->pdo->rollBack();
    }
}
