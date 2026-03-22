<?php
declare(strict_types=1);

class Expense
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function forBatch(int $batchId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM expenses WHERE batch_id = :batch_id ORDER BY date DESC');
        $stmt->execute(['batch_id' => $batchId]);
        return $stmt->fetchAll() ?: [];
    }

    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM expenses WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(array $data): bool
    {
        $sql = 'INSERT INTO expenses (batch_id, date, category, item_name, quantity, unit_cost, total_cost, supplier, notes)
                VALUES (:batch_id, :date, :category, :item_name, :quantity, :unit_cost, :total_cost, :supplier, :notes)';
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            'batch_id' => $data['batch_id'],
            'date' => $data['date'],
            'category' => $data['category'],
            'item_name' => $data['item_name'],
            'quantity' => $data['quantity'],
            'unit_cost' => $data['unit_cost'],
            'total_cost' => $data['total_cost'],
            'supplier' => $data['supplier'],
            'notes' => $data['notes'],
        ]);
    }

    public function update(int $id, array $data): bool
    {
        $sql = 'UPDATE expenses
                SET batch_id = :batch_id, date = :date, category = :category, item_name = :item_name,
                    quantity = :quantity, unit_cost = :unit_cost, total_cost = :total_cost, supplier = :supplier, notes = :notes
                WHERE id = :id';
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            'id' => $id,
            'batch_id' => $data['batch_id'],
            'date' => $data['date'],
            'category' => $data['category'],
            'item_name' => $data['item_name'],
            'quantity' => $data['quantity'],
            'unit_cost' => $data['unit_cost'],
            'total_cost' => $data['total_cost'],
            'supplier' => $data['supplier'],
            'notes' => $data['notes'],
        ]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM expenses WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }

    public function totalCostByBatch(int $batchId): float
    {
        $stmt = $this->pdo->prepare('SELECT COALESCE(SUM(total_cost), 0) FROM expenses WHERE batch_id = :batch_id');
        $stmt->execute(['batch_id' => $batchId]);
        return (float)$stmt->fetchColumn();
    }
}
