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

        $expenseTotal = $this->scalar('SELECT COALESCE(SUM(total_cost), 0) FROM expenses WHERE batch_id = :batch_id', $batchId);
        $feedTotals = $this->row('SELECT COALESCE(SUM(feed_kg), 0) AS feed_kg, COALESCE(SUM(total_cost), 0) AS feed_cost FROM feed_usage WHERE batch_id = :batch_id', $batchId);
        $salesTotals = $this->row('SELECT COALESCE(SUM(total_revenue), 0) AS revenue, COALESCE(SUM(total_weight), 0) AS total_weight, COALESCE(SUM(birds_sold), 0) AS birds_sold FROM sales WHERE batch_id = :batch_id', $batchId);
        $latestWeight = $this->scalar('SELECT average_weight_kg FROM growth_records WHERE batch_id = :batch_id ORDER BY date DESC LIMIT 1', $batchId);

        $chickCost = (float)$batch['total_chick_cost'];
        $totalExpenses = $chickCost + $expenseTotal + $feedTotals['feed_cost'];
        $totalRevenue = $salesTotals['revenue'];
        $totalProductionWeight = $salesTotals['total_weight'];
        $birdsSold = (int)$salesTotals['birds_sold'];
        $mortalityRate = ((int)$batch['initial_chicks'] > 0)
            ? ((int)$batch['mortality_count'] / (int)$batch['initial_chicks']) * 100
            : 0.0;
        $feedConversion = $totalProductionWeight > 0 ? $feedTotals['feed_kg'] / $totalProductionWeight : 0.0;
        $costPerBird = $birdsSold > 0 ? $totalExpenses / $birdsSold : 0.0;
        $costPerKg = $totalProductionWeight > 0 ? $totalExpenses / $totalProductionWeight : 0.0;

        return [
            'batch' => $batch,
            'initial_chicks' => (int)$batch['initial_chicks'],
            'current_alive' => (int)$batch['current_alive'],
            'birds_sold' => $birdsSold,
            'mortality_rate' => $mortalityRate,
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

    public function recentSales(int $batchId, int $limit = 8): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT sale_id, date, birds_sold, price_per_bird, total_revenue, buyer
             FROM sales WHERE batch_id = :batch_id ORDER BY date DESC, sale_id DESC LIMIT :limit'
        );
        $stmt->bindValue(':batch_id', $batchId, PDO::PARAM_INT);
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
