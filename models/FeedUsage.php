<?php
declare(strict_types=1);

class FeedUsage
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function forBatch(int $batchId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM feed_usage WHERE batch_id = :batch_id ORDER BY date DESC');
        $stmt->execute(['batch_id' => $batchId]);
        return $stmt->fetchAll() ?: [];
    }

    public function find(int $recordId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM feed_usage WHERE record_id = :record_id');
        $stmt->execute(['record_id' => $recordId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(array $data): bool
    {
        $sql = 'INSERT INTO feed_usage (batch_id, date, feed_type, feed_kg, cost_per_kg, total_cost)
                VALUES (:batch_id, :date, :feed_type, :feed_kg, :cost_per_kg, :total_cost)';
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            'batch_id' => $data['batch_id'],
            'date' => $data['date'],
            'feed_type' => $data['feed_type'],
            'feed_kg' => $data['feed_kg'],
            'cost_per_kg' => $data['cost_per_kg'],
            'total_cost' => $data['total_cost'],
        ]);
    }

    public function update(int $recordId, array $data): bool
    {
        $sql = 'UPDATE feed_usage
                SET batch_id = :batch_id, date = :date, feed_type = :feed_type, feed_kg = :feed_kg,
                    cost_per_kg = :cost_per_kg, total_cost = :total_cost
                WHERE record_id = :record_id';
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            'record_id' => $recordId,
            'batch_id' => $data['batch_id'],
            'date' => $data['date'],
            'feed_type' => $data['feed_type'],
            'feed_kg' => $data['feed_kg'],
            'cost_per_kg' => $data['cost_per_kg'],
            'total_cost' => $data['total_cost'],
        ]);
    }

    public function delete(int $recordId): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM feed_usage WHERE record_id = :record_id');
        return $stmt->execute(['record_id' => $recordId]);
    }

    public function totalFeedKg(int $batchId): float
    {
        $stmt = $this->pdo->prepare('SELECT COALESCE(SUM(feed_kg), 0) FROM feed_usage WHERE batch_id = :batch_id');
        $stmt->execute(['batch_id' => $batchId]);
        return (float)$stmt->fetchColumn();
    }

    public function totalFeedCost(int $batchId): float
    {
        $stmt = $this->pdo->prepare('SELECT COALESCE(SUM(total_cost), 0) FROM feed_usage WHERE batch_id = :batch_id');
        $stmt->execute(['batch_id' => $batchId]);
        return (float)$stmt->fetchColumn();
    }
}
