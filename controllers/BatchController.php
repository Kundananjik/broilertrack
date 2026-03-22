<?php
declare(strict_types=1);

require_once __DIR__ . '/../models/Batch.php';
require_once __DIR__ . '/../models/Sale.php';
require_once __DIR__ . '/../config/helpers.php';

class BatchController
{
    private Batch $batchModel;
    private Sale $saleModel;

    public function __construct(PDO $pdo)
    {
        $this->batchModel = new Batch($pdo);
        $this->saleModel = new Sale($pdo);
    }

    public function list(): array
    {
        return $this->batchModel->all();
    }

    public function defaultBatchId(): ?int
    {
        $latest = $this->batchModel->latest();
        return $latest ? (int)$latest['batch_id'] : null;
    }

    public function find(int $batchId): ?array
    {
        return $this->batchModel->find($batchId);
    }

    public function create(array $input): array
    {
        $data = $this->validatedBatchData($input, true);
        if (isset($data['error'])) {
            return ['success' => false, 'message' => $data['error']];
        }

        $data['total_chick_cost'] = $data['initial_chicks'] * $data['chick_cost'];

        $result = $this->batchModel->create($data);

        return $result
            ? ['success' => true, 'message' => 'Batch created successfully.']
            : ['success' => false, 'message' => 'Failed to create batch.'];
    }

    public function update(array $input): array
    {
        $batchId = isset($input['batch_id']) ? (int)$input['batch_id'] : 0;
        if ($batchId <= 0) {
            return ['success' => false, 'message' => 'Select a batch to edit.'];
        }

        $batch = $this->batchModel->find($batchId);
        if (!$batch) {
            return ['success' => false, 'message' => 'Batch not found.'];
        }

        $data = $this->validatedBatchData($input, false);
        if (isset($data['error'])) {
            return ['success' => false, 'message' => $data['error']];
        }

        $birdsSold = $this->saleModel->birdsSoldByBatch($batchId);
        if ($birdsSold > $data['initial_chicks']) {
            return ['success' => false, 'message' => 'Initial chicks cannot be below already sold birds.'];
        }
        if ($data['current_alive'] + $data['mortality_count'] + $birdsSold > $data['initial_chicks']) {
            return ['success' => false, 'message' => 'Alive, sold, and mortality birds cannot exceed initial chicks.'];
        }

        $data['total_chick_cost'] = $data['initial_chicks'] * $data['chick_cost'];
        $updated = $this->batchModel->update($batchId, $data);
        return $updated
            ? ['success' => true, 'message' => 'Batch updated successfully.']
            : ['success' => false, 'message' => 'Unable to update batch.'];
    }

    public function delete(array $input): array
    {
        $batchId = isset($input['batch_id']) ? (int)$input['batch_id'] : 0;
        if ($batchId <= 0 || !$this->batchModel->find($batchId)) {
            return ['success' => false, 'message' => 'Batch not found.'];
        }

        $deleted = $this->batchModel->delete($batchId);
        return $deleted
            ? ['success' => true, 'message' => 'Batch deleted successfully.']
            : ['success' => false, 'message' => 'Unable to delete batch.'];
    }

    public function updateStatus(array $input): array
    {
        $batchId = isset($input['batch_id']) ? (int)$input['batch_id'] : 0;
        $currentAlive = isset($input['current_alive']) ? max(0, (int)$input['current_alive']) : 0;
        $mortality = isset($input['mortality_count']) ? max(0, (int)$input['mortality_count']) : 0;

        if ($batchId <= 0) {
            return ['success' => false, 'message' => 'Select a batch to update.'];
        }

        $batch = $this->batchModel->find($batchId);
        if (!$batch) {
            return ['success' => false, 'message' => 'Batch not found.'];
        }

        $birdsSold = $this->saleModel->birdsSoldByBatch($batchId);

        if ($currentAlive + $mortality + $birdsSold > (int)$batch['initial_chicks']) {
            return ['success' => false, 'message' => 'Alive birds, sold birds, and mortality cannot exceed initial chicks.'];
        }

        $result = $this->batchModel->updateCounts($batchId, $currentAlive, $mortality);

        return $result
            ? ['success' => true, 'message' => 'Batch counts updated.']
            : ['success' => false, 'message' => 'Unable to update batch counts.'];
    }

    private function validatedBatchData(array $input, bool $isCreate): array
    {
        $data = [
            'batch_name' => trim($input['batch_name'] ?? ''),
            'breed' => trim($input['breed'] ?? ''),
            'start_date' => $input['start_date'] ?? null,
            'expected_harvest_date' => $input['expected_harvest_date'] ?? null,
            'initial_chicks' => isset($input['initial_chicks']) ? (int)$input['initial_chicks'] : 0,
            'chick_cost' => isset($input['chick_cost']) ? (float)$input['chick_cost'] : 0.0,
            'notes' => trim($input['notes'] ?? ''),
            'current_alive' => isset($input['current_alive']) ? max(0, (int)$input['current_alive']) : 0,
            'mortality_count' => isset($input['mortality_count']) ? max(0, (int)$input['mortality_count']) : 0,
        ];

        if ($data['batch_name'] === '' || $data['breed'] === '') {
            return ['error' => 'Batch name and breed are required.'];
        }
        if (!is_valid_date($data['start_date']) || !is_valid_date($data['expected_harvest_date'])) {
            return ['error' => 'Provide valid start and harvest dates.'];
        }
        if ($data['initial_chicks'] <= 0 || $data['chick_cost'] <= 0) {
            return ['error' => 'Initial chicks and chick cost must be greater than zero.'];
        }
        if (strtotime($data['expected_harvest_date']) < strtotime($data['start_date'])) {
            return ['error' => 'Harvest date must be after start date.'];
        }

        if ($isCreate) {
            $data['current_alive'] = $data['initial_chicks'];
            $data['mortality_count'] = 0;
        }

        return $data;
    }

}
