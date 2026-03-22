<?php
declare(strict_types=1);

require_once __DIR__ . '/../models/Dashboard.php';
require_once __DIR__ . '/../models/Batch.php';

class DashboardController
{
    private Dashboard $dashboard;
    private Batch $batchModel;

    public function __construct(PDO $pdo)
    {
        $this->dashboard = new Dashboard($pdo);
        $this->batchModel = new Batch($pdo);
    }

    public function getMetrics(?int $batchId = null): array
    {
        return $this->dashboard->getMetrics($batchId);
    }

    public function listBatches(): array
    {
        return $this->batchModel->all();
    }

    public function recentSales(int $batchId, int $limit = 8): array
    {
        return $this->dashboard->recentSales($batchId, $limit);
    }
}
