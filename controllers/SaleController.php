<?php
declare(strict_types=1);

require_once __DIR__ . '/../models/Sale.php';
require_once __DIR__ . '/../config/helpers.php';

class SaleController
{
    private Sale $saleModel;

    public function __construct(PDO $pdo)
    {
        $this->saleModel = new Sale($pdo);
    }

    public function list(int $batchId): array
    {
        return $this->saleModel->forBatch($batchId);
    }

    public function find(int $saleId): ?array
    {
        return $this->saleModel->find($saleId);
    }

    public function store(array $input): array
    {
        $data = $this->validatedData($input);
        if (isset($data['error'])) {
            return ['success' => false, 'message' => $data['error']];
        }

        return $this->saleModel->createWithBatchUpdate($data);
    }

    public function update(array $input): array
    {
        $saleId = isset($input['sale_id']) ? (int)$input['sale_id'] : 0;
        if ($saleId <= 0 || !$this->saleModel->find($saleId)) {
            return ['success' => false, 'message' => 'Sale record not found.'];
        }

        $data = $this->validatedData($input);
        if (isset($data['error'])) {
            return ['success' => false, 'message' => $data['error']];
        }

        return $this->saleModel->updateWithBatchReconcile($saleId, $data);
    }

    public function delete(array $input): array
    {
        $saleId = isset($input['sale_id']) ? (int)$input['sale_id'] : 0;
        if ($saleId <= 0 || !$this->saleModel->find($saleId)) {
            return ['success' => false, 'message' => 'Sale record not found.'];
        }

        return $this->saleModel->deleteWithBatchRestore($saleId);
    }

    private function validatedData(array $input): array
    {
        $data = [
            'batch_id' => isset($input['batch_id']) ? (int)$input['batch_id'] : 0,
            'date' => $input['date'] ?? null,
            'birds_sold' => isset($input['birds_sold']) ? (int)$input['birds_sold'] : 0,
            'average_weight_kg' => isset($input['average_weight_kg']) ? (float)$input['average_weight_kg'] : 0.0,
            'price_per_bird' => isset($input['price_per_bird']) ? (float)$input['price_per_bird'] : 0.0,
            'buyer' => trim($input['buyer'] ?? ''),
        ];

        if ($data['batch_id'] <= 0) {
            return ['error' => 'Select a batch first.'];
        }

        if (!is_valid_date($data['date'])) {
            return ['error' => 'Provide a valid sale date.'];
        }

        if ($data['birds_sold'] <= 0 || $data['average_weight_kg'] <= 0 || $data['price_per_bird'] <= 0) {
            return ['error' => 'Birds sold, weight, and price per bird must be greater than zero.'];
        }

        $data['total_weight'] = $data['birds_sold'] * $data['average_weight_kg'];
        $data['total_revenue'] = $data['birds_sold'] * $data['price_per_bird'];

        return $data;
    }

}
