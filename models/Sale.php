<?php
declare(strict_types=1);

class Sale
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function forBatch(int $batchId, ?int $createdBy = null): array
    {
        $sql = 'SELECT * FROM sales WHERE batch_id = :batch_id AND is_deleted = 0';
        $params = ['batch_id' => $batchId];
        if ($createdBy !== null) {
            $sql .= ' AND created_by = :created_by';
            $params['created_by'] = $createdBy;
        }
        $sql .= ' ORDER BY date DESC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll() ?: [];
    }

    public function find(int $saleId, ?int $createdBy = null): ?array
    {
        $sql = 'SELECT * FROM sales WHERE sale_id = :sale_id AND is_deleted = 0';
        $params = ['sale_id' => $saleId];
        if ($createdBy !== null) {
            $sql .= ' AND created_by = :created_by';
            $params['created_by'] = $createdBy;
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(array $data): bool
    {
        $sql = 'INSERT INTO sales (batch_id, date, birds_sold, average_weight_kg, price_per_bird, total_weight, total_revenue, paid_amount, balance_amount, buyer, created_by)
                VALUES (:batch_id, :date, :birds_sold, :average_weight_kg, :price_per_bird, :total_weight, :total_revenue, :paid_amount, :balance_amount, :buyer, :created_by)';
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            'batch_id' => $data['batch_id'],
            'date' => $data['date'],
            'birds_sold' => $data['birds_sold'],
            'average_weight_kg' => $data['average_weight_kg'],
            'price_per_bird' => $data['price_per_bird'],
            'total_weight' => $data['total_weight'],
            'total_revenue' => $data['total_revenue'],
            'paid_amount' => $data['paid_amount'],
            'balance_amount' => $data['balance_amount'],
            'buyer' => $data['buyer'],
            'created_by' => $data['created_by'] ?? null,
        ]);
    }

    public function createWithBatchUpdate(array $data): array
    {
        try {
            $this->pdo->beginTransaction();

            $alive = $this->currentAliveForUpdate((int)$data['batch_id']);
            if ($alive === null) {
                $this->pdo->rollBack();
                return ['success' => false, 'message' => 'Selected batch was not found.'];
            }

            if ((int)$data['birds_sold'] > $alive) {
                $this->pdo->rollBack();
                return ['success' => false, 'message' => 'Birds sold cannot exceed current alive birds.'];
            }

            $saved = $this->create($data);
            if (!$saved) {
                $this->pdo->rollBack();
                return ['success' => false, 'message' => 'Unable to record sale.'];
            }

            $updateStmt = $this->pdo->prepare('UPDATE batches SET current_alive = current_alive - :birds_sold WHERE batch_id = :batch_id');
            $updated = $updateStmt->execute([
                'birds_sold' => (int)$data['birds_sold'],
                'batch_id' => (int)$data['batch_id'],
            ]);

            if (!$updated) {
                $this->pdo->rollBack();
                return ['success' => false, 'message' => 'Unable to update batch inventory.'];
            }

            $saleId = (int)$this->pdo->lastInsertId();
            $this->pdo->commit();

            return ['success' => true, 'message' => 'Sale recorded successfully.', 'sale_id' => $saleId];
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            return ['success' => false, 'message' => 'Unable to record sale.'];
        }
    }

    public function updateWithBatchReconcile(int $saleId, array $data, ?int $createdBy = null): array
    {
        try {
            $this->pdo->beginTransaction();

            $existing = $this->saleForUpdate($saleId, $createdBy);
            if ($existing === null) {
                $this->pdo->rollBack();
                return ['success' => false, 'message' => 'Sale record was not found.'];
            }

            $oldBatchId = (int)$existing['batch_id'];
            $oldBirdsSold = (int)$existing['birds_sold'];
            $newBatchId = (int)$data['batch_id'];
            $newBirdsSold = (int)$data['birds_sold'];

            if ($oldBatchId === $newBatchId) {
                $alive = $this->currentAliveForUpdate($newBatchId);
                if ($alive === null) {
                    $this->pdo->rollBack();
                    return ['success' => false, 'message' => 'Selected batch was not found.'];
                }

                $available = $alive + $oldBirdsSold;
                if ($newBirdsSold > $available) {
                    $this->pdo->rollBack();
                    return ['success' => false, 'message' => 'Birds sold cannot exceed current alive birds.'];
                }

                $adjustment = $oldBirdsSold - $newBirdsSold;
                if ($adjustment !== 0) {
                    $updateBatch = $this->pdo->prepare('UPDATE batches SET current_alive = current_alive + :adjustment WHERE batch_id = :batch_id');
                    $updateBatch->execute(['adjustment' => $adjustment, 'batch_id' => $newBatchId]);
                }
            } else {
                $oldAlive = $this->currentAliveForUpdate($oldBatchId);
                $newAlive = $this->currentAliveForUpdate($newBatchId);

                if ($oldAlive === null || $newAlive === null) {
                    $this->pdo->rollBack();
                    return ['success' => false, 'message' => 'Selected batch was not found.'];
                }

                if ($newBirdsSold > $newAlive) {
                    $this->pdo->rollBack();
                    return ['success' => false, 'message' => 'Birds sold cannot exceed current alive birds.'];
                }

                $restoreOld = $this->pdo->prepare('UPDATE batches SET current_alive = current_alive + :birds WHERE batch_id = :batch_id');
                $restoreOld->execute(['birds' => $oldBirdsSold, 'batch_id' => $oldBatchId]);

                $deductNew = $this->pdo->prepare('UPDATE batches SET current_alive = current_alive - :birds WHERE batch_id = :batch_id');
                $deductNew->execute(['birds' => $newBirdsSold, 'batch_id' => $newBatchId]);
            }

            $updateSale = $this->pdo->prepare(
                'UPDATE sales
                 SET batch_id = :batch_id, date = :date, birds_sold = :birds_sold, average_weight_kg = :average_weight_kg,
                     price_per_bird = :price_per_bird, total_weight = :total_weight, total_revenue = :total_revenue,
                     paid_amount = :paid_amount, balance_amount = :balance_amount, buyer = :buyer
                 WHERE sale_id = :sale_id AND is_deleted = 0'
            );
            $updated = $updateSale->execute([
                'sale_id' => $saleId,
                'batch_id' => $data['batch_id'],
                'date' => $data['date'],
                'birds_sold' => $data['birds_sold'],
                'average_weight_kg' => $data['average_weight_kg'],
                'price_per_bird' => $data['price_per_bird'],
                'total_weight' => $data['total_weight'],
                'total_revenue' => $data['total_revenue'],
                'paid_amount' => $data['paid_amount'],
                'balance_amount' => $data['balance_amount'],
                'buyer' => $data['buyer'],
            ]);

            if (!$updated) {
                $this->pdo->rollBack();
                return ['success' => false, 'message' => 'Unable to update sale.'];
            }

            $this->pdo->commit();
            return ['success' => true, 'message' => 'Sale updated successfully.', 'sale_id' => $saleId];
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            return ['success' => false, 'message' => 'Unable to update sale.'];
        }
    }

    public function deleteWithBatchRestore(int $saleId, ?int $createdBy = null): array
    {
        try {
            $this->pdo->beginTransaction();

            $existing = $this->saleForUpdate($saleId, $createdBy);
            if ($existing === null) {
                $this->pdo->rollBack();
                return ['success' => false, 'message' => 'Sale record was not found.'];
            }

            $restore = $this->pdo->prepare('UPDATE batches SET current_alive = current_alive + :birds_sold WHERE batch_id = :batch_id');
            $restore->execute([
                'birds_sold' => (int)$existing['birds_sold'],
                'batch_id' => (int)$existing['batch_id'],
            ]);

            $delete = $this->pdo->prepare(
                'UPDATE sales
                 SET is_deleted = 1, deleted_at = ' . $this->timestampExpression() . '
                 WHERE sale_id = :sale_id AND is_deleted = 0'
            );
            $deleted = $delete->execute(['sale_id' => $saleId]);

            if (!$deleted) {
                $this->pdo->rollBack();
                return ['success' => false, 'message' => 'Unable to delete sale.'];
            }

            $this->pdo->commit();
            return ['success' => true, 'message' => 'Sale deleted successfully.', 'sale_id' => $saleId];
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            return ['success' => false, 'message' => 'Unable to delete sale.'];
        }
    }

    public function revenueByBatch(int $batchId): float
    {
        $stmt = $this->pdo->prepare('SELECT COALESCE(SUM(total_revenue), 0) FROM sales WHERE batch_id = :batch_id AND is_deleted = 0');
        $stmt->execute(['batch_id' => $batchId]);
        return (float)$stmt->fetchColumn();
    }

    public function totalWeightByBatch(int $batchId): float
    {
        $stmt = $this->pdo->prepare('SELECT COALESCE(SUM(total_weight), 0) FROM sales WHERE batch_id = :batch_id AND is_deleted = 0');
        $stmt->execute(['batch_id' => $batchId]);
        return (float)$stmt->fetchColumn();
    }

    public function birdsSoldByBatch(int $batchId): int
    {
        $stmt = $this->pdo->prepare('SELECT COALESCE(SUM(birds_sold), 0) FROM sales WHERE batch_id = :batch_id AND is_deleted = 0');
        $stmt->execute(['batch_id' => $batchId]);
        return (int)$stmt->fetchColumn();
    }

    private function saleForUpdate(int $saleId, ?int $createdBy = null): ?array
    {
        $driver = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        $sql = 'SELECT * FROM sales WHERE sale_id = :sale_id AND is_deleted = 0';
        $params = ['sale_id' => $saleId];
        if ($createdBy !== null) {
            $sql .= ' AND created_by = :created_by';
            $params['created_by'] = $createdBy;
        }
        if ($driver !== 'sqlite') {
            $sql .= ' FOR UPDATE';
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    private function currentAliveForUpdate(int $batchId): ?int
    {
        $driver = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        $sql = $driver === 'sqlite'
            ? 'SELECT current_alive FROM batches WHERE batch_id = :batch_id'
            : 'SELECT current_alive FROM batches WHERE batch_id = :batch_id FOR UPDATE';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['batch_id' => $batchId]);
        $value = $stmt->fetchColumn();

        return $value !== false ? (int)$value : null;
    }

    private function timestampExpression(): string
    {
        $driver = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        return $driver === 'sqlite' ? 'CURRENT_TIMESTAMP' : 'NOW()';
    }
}
