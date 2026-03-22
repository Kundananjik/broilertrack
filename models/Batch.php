<?php
declare(strict_types=1);

class Batch
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function all(): array
    {
        $stmt = $this->pdo->query('SELECT * FROM batches ORDER BY start_date DESC');
        return $stmt->fetchAll() ?: [];
    }

    public function find(int $batchId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM batches WHERE batch_id = :batch_id');
        $stmt->execute(['batch_id' => $batchId]);
        $batch = $stmt->fetch();
        return $batch ?: null;
    }

    public function latest(): ?array
    {
        $stmt = $this->pdo->query('SELECT * FROM batches ORDER BY start_date DESC LIMIT 1');
        $batch = $stmt->fetch();
        return $batch ?: null;
    }

    public function create(array $data): bool
    {
        $sql = 'INSERT INTO batches (batch_name, breed, start_date, expected_harvest_date, initial_chicks, chick_cost, total_chick_cost, current_alive, mortality_count, notes)
                VALUES (:batch_name, :breed, :start_date, :expected_harvest_date, :initial_chicks, :chick_cost, :total_chick_cost, :current_alive, :mortality_count, :notes)';
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            'batch_name' => $data['batch_name'],
            'breed' => $data['breed'],
            'start_date' => $data['start_date'],
            'expected_harvest_date' => $data['expected_harvest_date'],
            'initial_chicks' => $data['initial_chicks'],
            'chick_cost' => $data['chick_cost'],
            'total_chick_cost' => $data['total_chick_cost'],
            'current_alive' => $data['current_alive'],
            'mortality_count' => $data['mortality_count'],
            'notes' => $data['notes'],
        ]);
    }

    public function update(int $batchId, array $data): bool
    {
        $sql = 'UPDATE batches
                SET batch_name = :batch_name, breed = :breed, start_date = :start_date,
                    expected_harvest_date = :expected_harvest_date, initial_chicks = :initial_chicks,
                    chick_cost = :chick_cost, total_chick_cost = :total_chick_cost,
                    current_alive = :current_alive, mortality_count = :mortality_count, notes = :notes
                WHERE batch_id = :batch_id';
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            'batch_id' => $batchId,
            'batch_name' => $data['batch_name'],
            'breed' => $data['breed'],
            'start_date' => $data['start_date'],
            'expected_harvest_date' => $data['expected_harvest_date'],
            'initial_chicks' => $data['initial_chicks'],
            'chick_cost' => $data['chick_cost'],
            'total_chick_cost' => $data['total_chick_cost'],
            'current_alive' => $data['current_alive'],
            'mortality_count' => $data['mortality_count'],
            'notes' => $data['notes'],
        ]);
    }

    public function updateCounts(int $batchId, int $currentAlive, int $mortalityCount): bool
    {
        $sql = 'UPDATE batches SET current_alive = :current_alive, mortality_count = :mortality_count WHERE batch_id = :batch_id';
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            'current_alive' => $currentAlive,
            'mortality_count' => $mortalityCount,
            'batch_id' => $batchId,
        ]);
    }

    public function delete(int $batchId): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM batches WHERE batch_id = :batch_id');
        return $stmt->execute(['batch_id' => $batchId]);
    }
}
