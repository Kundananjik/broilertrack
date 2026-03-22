<?php
declare(strict_types=1);

class GrowthRecord
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function forBatch(int $batchId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM growth_records WHERE batch_id = :batch_id AND is_deleted = 0 ORDER BY date DESC');
        $stmt->execute(['batch_id' => $batchId]);
        return $stmt->fetchAll() ?: [];
    }

    public function find(int $recordId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM growth_records WHERE record_id = :record_id AND is_deleted = 0');
        $stmt->execute(['record_id' => $recordId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(array $data): bool
    {
        $sql = 'INSERT INTO growth_records (batch_id, date, average_weight_kg, birds_sampled)
                VALUES (:batch_id, :date, :average_weight_kg, :birds_sampled)';
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            'batch_id' => $data['batch_id'],
            'date' => $data['date'],
            'average_weight_kg' => $data['average_weight_kg'],
            'birds_sampled' => $data['birds_sampled'],
        ]);
    }

    public function update(int $recordId, array $data): bool
    {
        $sql = 'UPDATE growth_records
                SET batch_id = :batch_id, date = :date, average_weight_kg = :average_weight_kg, birds_sampled = :birds_sampled
                WHERE record_id = :record_id AND is_deleted = 0';
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            'record_id' => $recordId,
            'batch_id' => $data['batch_id'],
            'date' => $data['date'],
            'average_weight_kg' => $data['average_weight_kg'],
            'birds_sampled' => $data['birds_sampled'],
        ]);
    }

    public function delete(int $recordId): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE growth_records
             SET is_deleted = 1, deleted_at = ' . $this->timestampExpression() . '
             WHERE record_id = :record_id AND is_deleted = 0'
        );
        return $stmt->execute(['record_id' => $recordId]);
    }

    public function latestAverageWeight(int $batchId): ?float
    {
        $stmt = $this->pdo->prepare('SELECT average_weight_kg FROM growth_records WHERE batch_id = :batch_id AND is_deleted = 0 ORDER BY date DESC LIMIT 1');
        $stmt->execute(['batch_id' => $batchId]);
        $value = $stmt->fetchColumn();
        return $value !== false ? (float)$value : null;
    }

    private function timestampExpression(): string
    {
        $driver = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        return $driver === 'sqlite' ? 'CURRENT_TIMESTAMP' : 'NOW()';
    }
}
