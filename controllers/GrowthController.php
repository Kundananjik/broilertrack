<?php
declare(strict_types=1);

require_once __DIR__ . '/../models/GrowthRecord.php';
require_once __DIR__ . '/../config/helpers.php';

class GrowthController
{
    private GrowthRecord $growthModel;

    public function __construct(PDO $pdo)
    {
        $this->growthModel = new GrowthRecord($pdo);
    }

    public function list(int $batchId): array
    {
        return $this->growthModel->forBatch($batchId);
    }

    public function find(int $recordId): ?array
    {
        return $this->growthModel->find($recordId);
    }

    public function store(array $input): array
    {
        $data = $this->validatedData($input);
        if (isset($data['error'])) {
            return ['success' => false, 'message' => $data['error']];
        }

        $saved = $this->growthModel->create($data);

        return $saved
            ? ['success' => true, 'message' => 'Growth record saved.']
            : ['success' => false, 'message' => 'Unable to save growth record.'];
    }

    public function update(array $input): array
    {
        $recordId = isset($input['record_id']) ? (int)$input['record_id'] : 0;
        if ($recordId <= 0 || !$this->growthModel->find($recordId)) {
            return ['success' => false, 'message' => 'Growth record not found.'];
        }

        $data = $this->validatedData($input);
        if (isset($data['error'])) {
            return ['success' => false, 'message' => $data['error']];
        }

        $saved = $this->growthModel->update($recordId, $data);
        return $saved
            ? ['success' => true, 'message' => 'Growth record updated successfully.']
            : ['success' => false, 'message' => 'Unable to update growth record.'];
    }

    public function delete(array $input): array
    {
        $recordId = isset($input['record_id']) ? (int)$input['record_id'] : 0;
        if ($recordId <= 0 || !$this->growthModel->find($recordId)) {
            return ['success' => false, 'message' => 'Growth record not found.'];
        }

        $deleted = $this->growthModel->delete($recordId);
        return $deleted
            ? ['success' => true, 'message' => 'Growth record deleted successfully.']
            : ['success' => false, 'message' => 'Unable to delete growth record.'];
    }

    private function validatedData(array $input): array
    {
        $data = [
            'batch_id' => isset($input['batch_id']) ? (int)$input['batch_id'] : 0,
            'date' => $input['date'] ?? null,
            'average_weight_kg' => isset($input['average_weight_kg']) ? (float)$input['average_weight_kg'] : 0.0,
            'birds_sampled' => isset($input['birds_sampled']) ? (int)$input['birds_sampled'] : 0,
        ];

        if ($data['batch_id'] <= 0) {
            return ['error' => 'Select a batch first.'];
        }

        if (!is_valid_date($data['date'])) {
            return ['error' => 'Provide a valid sampling date.'];
        }

        if ($data['average_weight_kg'] <= 0 || $data['birds_sampled'] <= 0) {
            return ['error' => 'Average weight and birds sampled must be greater than zero.'];
        }

        return $data;
    }

}
