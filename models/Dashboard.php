<?php
declare(strict_types=1);

class Dashboard
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function getMetrics(?int $batchId = null): array
    {
        $batch = $this->resolveBatch($batchId);

        if ($batch === null) {
            return [
                'batch' => null,
                'initial_chicks' => 0,
                'current_alive' => 0,
                'birds_sold' => 0,
                'mortality_rate' => 0.0,
                'sales_rate' => 0.0,
                'profit_rate' => 0.0,
                'growth_rate' => 0.0,
                'total_feed_used' => 0.0,
                'average_weight' => null,
                'total_production_weight' => 0.0,
                'total_expenses' => 0.0,
                'total_revenue' => 0.0,
                'avg_price_per_bird' => 0.0,
                'net_profit' => 0.0,
                'feed_conversion_ratio' => 0.0,
                'cost_per_bird' => 0.0,
                'cost_per_kg' => 0.0,
            ];
        }

        $batchId = (int)$batch['batch_id'];

        $expenseTotal = $this->scalar('SELECT COALESCE(SUM(total_cost), 0) FROM expenses WHERE batch_id = :batch_id AND is_deleted = 0', $batchId);
        $feedTotals = $this->row('SELECT COALESCE(SUM(feed_kg), 0) AS feed_kg, COALESCE(SUM(total_cost), 0) AS feed_cost FROM feed_usage WHERE batch_id = :batch_id AND is_deleted = 0', $batchId);
        $salesTotals = $this->row('SELECT COALESCE(SUM(total_revenue), 0) AS revenue, COALESCE(SUM(total_weight), 0) AS total_weight, COALESCE(SUM(birds_sold), 0) AS birds_sold FROM sales WHERE batch_id = :batch_id AND is_deleted = 0', $batchId);
        $latestWeight = $this->scalar('SELECT average_weight_kg FROM growth_records WHERE batch_id = :batch_id AND is_deleted = 0 ORDER BY date DESC LIMIT 1', $batchId);
        $previousWeight = $this->scalar('SELECT average_weight_kg FROM growth_records WHERE batch_id = :batch_id AND is_deleted = 0 ORDER BY date DESC LIMIT 1 OFFSET 1', $batchId);
        $latestPricePerBird = $this->scalar('SELECT price_per_bird FROM sales WHERE batch_id = :batch_id AND is_deleted = 0 ORDER BY date DESC, sale_id DESC LIMIT 1', $batchId);

        $chickCost = (float)$batch['total_chick_cost'];
        $totalExpenses = $chickCost + $expenseTotal + $feedTotals['feed_cost'];
        $totalRevenue = $salesTotals['revenue'];
        $totalProductionWeight = $salesTotals['total_weight'];
        $birdsSold = (int)$salesTotals['birds_sold'];
        $mortalityRate = ((int)$batch['initial_chicks'] > 0)
            ? ((int)$batch['mortality_count'] / (int)$batch['initial_chicks']) * 100
            : 0.0;
        $salesRate = ((int)$batch['initial_chicks'] > 0)
            ? ($birdsSold / (int)$batch['initial_chicks']) * 100
            : 0.0;
        $profitRate = $totalRevenue > 0 ? (($totalRevenue - $totalExpenses) / $totalRevenue) * 100 : 0.0;
        $growthRate = $previousWeight > 0 ? (($latestWeight - $previousWeight) / $previousWeight) * 100 : 0.0;
        $feedConversion = $totalProductionWeight > 0 ? $feedTotals['feed_kg'] / $totalProductionWeight : 0.0;
        $costPerBird = $latestPricePerBird > 0 ? $latestPricePerBird : 0.0;
        $costPerKg = $totalProductionWeight > 0 ? $totalExpenses / $totalProductionWeight : 0.0;

        return [
            'batch' => $batch,
            'initial_chicks' => (int)$batch['initial_chicks'],
            'current_alive' => (int)$batch['current_alive'],
            'birds_sold' => $birdsSold,
            'mortality_rate' => $mortalityRate,
            'sales_rate' => $salesRate,
            'profit_rate' => $profitRate,
            'growth_rate' => $growthRate,
            'total_feed_used' => $feedTotals['feed_kg'],
            'average_weight' => $latestWeight !== null ? (float)$latestWeight : null,
            'total_production_weight' => $totalProductionWeight,
            'total_expenses' => $totalExpenses,
            'total_revenue' => $totalRevenue,
            'avg_price_per_bird' => $birdsSold > 0 ? $totalRevenue / $birdsSold : 0.0,
            'net_profit' => $totalRevenue - $totalExpenses,
            'feed_conversion_ratio' => $feedConversion,
            'cost_per_bird' => $costPerBird,
            'cost_per_kg' => $costPerKg,
        ];
    }

    public function recentSales(int $batchId, int $limit = 8, ?int $createdBy = null): array
    {
        $sql = 'SELECT sale_id, date, birds_sold, price_per_bird, total_revenue, paid_amount, balance_amount, buyer
                FROM sales WHERE batch_id = :batch_id AND is_deleted = 0';
        if ($createdBy !== null) {
            $sql .= ' AND created_by = :created_by';
        }
        $sql .= ' ORDER BY date DESC, sale_id DESC LIMIT :limit';
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':batch_id', $batchId, PDO::PARAM_INT);
        if ($createdBy !== null) {
            $stmt->bindValue(':created_by', $createdBy, PDO::PARAM_INT);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll() ?: [];
    }

    public function collectionSummary(int $batchId, ?int $createdBy = null): array
    {
        $sql = 'SELECT
                    COALESCE(SUM(total_revenue), 0) AS revenue_total,
                    COALESCE(SUM(paid_amount), 0) AS paid_total,
                    COALESCE(SUM(balance_amount), 0) AS balance_total,
                    SUM(CASE WHEN balance_amount > 0 THEN 1 ELSE 0 END) AS balance_count
                FROM sales
                WHERE batch_id = :batch_id AND is_deleted = 0';
        $params = ['batch_id' => $batchId];
        if ($createdBy !== null) {
            $sql .= ' AND created_by = :created_by';
            $params['created_by'] = $createdBy;
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch() ?: [];

        return [
            'revenue_total' => (float)($row['revenue_total'] ?? 0),
            'paid_total' => (float)($row['paid_total'] ?? 0),
            'balance_total' => (float)($row['balance_total'] ?? 0),
            'balance_count' => (int)($row['balance_count'] ?? 0),
        ];
    }

    public function overdueBalances(int $batchId, int $days = 7, int $limit = 5, ?int $createdBy = null): array
    {
        $days = max(1, $days);
        $driver = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        $dateClause = $driver === 'sqlite'
            ? "date <= date('now', '-{$days} day')"
            : "date <= DATE_SUB(CURDATE(), INTERVAL {$days} DAY)";
        $ownerClause = $createdBy !== null ? ' AND created_by = :created_by' : '';

        $stmt = $this->pdo->prepare(
            "SELECT sale_id, date, buyer, total_revenue, paid_amount, balance_amount
             FROM sales
             WHERE batch_id = :batch_id AND is_deleted = 0 AND balance_amount > 0 AND {$dateClause}{$ownerClause}
             ORDER BY date ASC, sale_id ASC
             LIMIT :limit"
        );
        $stmt->bindValue(':batch_id', $batchId, PDO::PARAM_INT);
        if ($createdBy !== null) {
            $stmt->bindValue(':created_by', $createdBy, PDO::PARAM_INT);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll() ?: [];
    }

    private function resolveBatch(?int $batchId): ?array
    {
        if ($batchId !== null && $batchId > 0) {
            $stmt = $this->pdo->prepare('SELECT * FROM batches WHERE batch_id = :batch_id');
            $stmt->execute(['batch_id' => $batchId]);
            $result = $stmt->fetch();
            if ($result) {
                return $result;
            }
        }

        $stmt = $this->pdo->query('SELECT * FROM batches ORDER BY start_date DESC LIMIT 1');
        $batch = $stmt->fetch();
        return $batch ?: null;
    }

    private function scalar(string $sql, int $batchId): float
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['batch_id' => $batchId]);
        $value = $stmt->fetchColumn();
        return $value !== false ? (float)$value : 0.0;
    }

    private function row(string $sql, int $batchId): array
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['batch_id' => $batchId]);
        $row = $stmt->fetch();
        return $row ?: ['feed_kg' => 0, 'feed_cost' => 0, 'revenue' => 0, 'total_weight' => 0, 'birds_sold' => 0];
    }
}
