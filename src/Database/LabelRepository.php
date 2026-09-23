<?php

declare(strict_types=1);

namespace App\Database;

use App\Label\Label;
use App\Label\MediaType;

/**
 * Persistence for labels. Uses prepared statements exclusively.
 */
final class LabelRepository
{
    public function __construct(private readonly Database $db)
    {
    }

    /**
     * @param array<string, mixed> $filters Supported keys: media_type, media_generation, search, printed, batch_id.
     * @return Label[]
     */
    public function findAll(array $filters = []): array
    {
        [$where, $params] = $this->buildWhere($filters);

        $rows = $this->db->query(
            'SELECT * FROM labels' . $where . ' ORDER BY created_at DESC, id DESC',
            $params
        )->fetchAll();

        return array_map(fn (array $row) => $this->hydrate($row), $rows);
    }

    public function countAll(): int
    {
        return (int) $this->db->query('SELECT COUNT(*) AS c FROM labels')->fetch()['c'];
    }

    /**
     * @return Label[]
     */
    public function recent(int $limit = 10): array
    {
        $rows = $this->db->query('SELECT * FROM labels ORDER BY id DESC LIMIT ' . max(1, $limit))->fetchAll();

        return array_map(fn (array $row) => $this->hydrate($row), $rows);
    }

    public function findById(int $id): ?Label
    {
        $row = $this->db->query('SELECT * FROM labels WHERE id = :id', ['id' => $id])->fetch();

        return $row === false ? null : $this->hydrate($row);
    }

    public function findByCode(string $code): ?Label
    {
        $row = $this->db->query('SELECT * FROM labels WHERE label_code = :code', ['code' => $code])->fetch();

        return $row === false ? null : $this->hydrate($row);
    }

    public function exists(string $code): bool
    {
        return $this->findByCode($code) !== null;
    }

    /**
     * @return string[] Existing label codes that intersect with the given codes.
     */
    public function findDuplicates(array $codes): array
    {
        if ($codes === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($codes), '?'));
        $rows = $this->db->query(
            'SELECT label_code FROM labels WHERE label_code IN (' . $placeholders . ')',
            array_values($codes)
        )->fetchAll();

        return array_column($rows, 'label_code');
    }

    public function save(Label $label): Label
    {
        $now = date('Y-m-d H:i:s');
        $this->db->execute(
            'INSERT INTO labels (label_code, media_type, media_generation, barcode_type, notes, location, status, batch_id, created_at, print_count)
             VALUES (:label_code, :media_type, :media_generation, :barcode_type, :notes, :location, :status, :batch_id, :created_at, 0)',
            [
                'label_code' => $label->labelCode,
                'media_type' => $label->mediaType->value,
                'media_generation' => $label->mediaGeneration,
                'barcode_type' => $label->barcodeType,
                'notes' => $label->notes,
                'location' => $label->location,
                'status' => null,
                'batch_id' => $label->batchId,
                'created_at' => $now,
            ]
        );

        return new Label(
            labelCode: $label->labelCode,
            mediaType: $label->mediaType,
            mediaGeneration: $label->mediaGeneration,
            barcodeType: $label->barcodeType,
            notes: $label->notes,
            location: $label->location,
            id: $this->db->lastInsertId(),
            createdAt: $now,
            printedAt: null,
            printCount: 0,
            batchId: $label->batchId,
        );
    }

    /**
     * @param Label[] $labels
     * @return Label[] persisted labels (with ids/timestamps)
     */
    public function saveMany(array $labels, ?int $batchId = null): array
    {
        $saved = [];
        $this->db->beginTransaction();
        try {
            foreach ($labels as $label) {
                $saved[] = $this->save($batchId !== null
                    ? new Label(
                        labelCode: $label->labelCode,
                        mediaType: $label->mediaType,
                        mediaGeneration: $label->mediaGeneration,
                        barcodeType: $label->barcodeType,
                        notes: $label->notes,
                        location: $label->location,
                        batchId: $batchId,
                    )
                    : $label);
            }
            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }

        return $saved;
    }

    public function deleteByCode(string $code): void
    {
        $this->db->execute('DELETE FROM labels WHERE label_code = :code', ['code' => $code]);
    }

    public function nextBatchId(): int
    {
        $row = $this->db->query('SELECT COALESCE(MAX(batch_id), 0) + 1 AS n FROM labels')->fetch();

        return (int) ($row['n'] ?? 1);
    }

    public function registerPrint(int $id): void
    {
        $this->db->execute(
            'UPDATE labels SET print_count = print_count + 1, printed_at = :now WHERE id = :id',
            ['now' => date('Y-m-d H:i:s'), 'id' => $id]
        );
    }

    /**
     * @return string[]
     */
    public function distinctGenerations(): array
    {
        $rows = $this->db->query(
            'SELECT DISTINCT media_generation FROM labels WHERE media_generation != \'\' ORDER BY media_generation'
        )->fetchAll();

        return array_column($rows, 'media_generation');
    }

    /**
     * @return Label[] Labels that have been printed, most recent first.
     */
    public function printHistory(int $limit = 100): array
    {
        $rows = $this->db->query(
            'SELECT * FROM labels WHERE print_count > 0 ORDER BY printed_at DESC, id DESC LIMIT ' . max(1, $limit)
        )->fetchAll();

        return array_map(fn (array $row) => $this->hydrate($row), $rows);
    }

    /**
     * @param array<string, mixed> $filters
     * @return array{0: string, 1: array<string, mixed>}
     */
    private function buildWhere(array $filters): array
    {
        $clauses = [];
        $params = [];

        if (!empty($filters['media_type']) && $filters['media_type'] !== 'ALL') {
            $clauses[] = 'media_type = :media_type';
            $params['media_type'] = $filters['media_type'];
        }

        if (!empty($filters['media_generation']) && $filters['media_generation'] !== 'ALL') {
            $clauses[] = 'media_generation = :media_generation';
            $params['media_generation'] = $filters['media_generation'];
        }

        if (!empty($filters['search'])) {
            $clauses[] = 'label_code LIKE :search';
            $params['search'] = '%' . $filters['search'] . '%';
        }

        if (isset($filters['printed'])) {
            if ($filters['printed'] === '1') {
                $clauses[] = 'print_count > 0';
            } elseif ($filters['printed'] === '0') {
                $clauses[] = 'print_count = 0';
            }
        }

        if (isset($filters['batch_id'])) {
            $clauses[] = 'batch_id = :batch_id';
            $params['batch_id'] = (int) $filters['batch_id'];
        }

        $where = $clauses === [] ? '' : ' WHERE ' . implode(' AND ', $clauses);

        return [$where, $params];
    }

    /**
     * @param array<string, mixed> $row
     */
    private function hydrate(array $row): Label
    {
        return new Label(
            labelCode: (string) $row['label_code'],
            mediaType: MediaType::from((string) $row['media_type']),
            mediaGeneration: (string) $row['media_generation'],
            barcodeType: (string) $row['barcode_type'],
            notes: isset($row['notes']) && $row['notes'] !== null ? (string) $row['notes'] : null,
            location: isset($row['location']) && $row['location'] !== null ? (string) $row['location'] : null,
            id: (int) $row['id'],
            createdAt: isset($row['created_at']) ? (string) $row['created_at'] : null,
            printedAt: isset($row['printed_at']) && $row['printed_at'] !== null ? (string) $row['printed_at'] : null,
            printCount: (int) $row['print_count'],
            batchId: isset($row['batch_id']) && $row['batch_id'] !== null ? (int) $row['batch_id'] : null,
        );
    }
}
