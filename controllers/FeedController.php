<?php
declare(strict_types=1);

require_once __DIR__ . '/../models/FeedUsage.php';
require_once __DIR__ . '/../config/helpers.php';

class FeedController
{
    private FeedUsage $feedModel;

    public function __construct(PDO $pdo)
    {
        $this->feedModel = new FeedUsage($pdo);
    }

    public function list(int $batchId): array
    {
        return $this->feedModel->forBatch($batchId);
    }

    public function totalFeedKg(int $batchId): float
    {
        return $this->feedModel->totalFeedKg($batchId);
    }

    public function totalFeedCost(int $batchId): float
    {
        return $this->feedModel->totalFeedCost($batchId);
    }

    public function find(int $recordId): ?array
    {
        return $this->feedModel->find($recordId);
    }

    public function store(array $input): array
    {
        $data = $this->validatedData($input);
        if (isset($data['error'])) {
            return ['success' => false, 'message' => $data['error']];
        }

        $saved = $this->feedModel->create($data);
        if ($saved && function_exists('audit_log')) {
            audit_log('feed_usage', 'create', 'feed_record', null, [
                'batch_id' => $data['batch_id'],
                'feed_type' => $data['feed_type'],
                'total_cost' => $data['total_cost'],
            ]);
        }

        return $saved
            ? ['success' => true, 'message' => 'Feed usage recorded.']
            : ['success' => false, 'message' => 'Unable to save feed usage.'];
    }

    public function update(array $input): array
    {
        $recordId = isset($input['record_id']) ? (int)$input['record_id'] : 0;
        if ($recordId <= 0 || !$this->feedModel->find($recordId)) {
            return ['success' => false, 'message' => 'Feed record not found.'];
        }

        $data = $this->validatedData($input);
        if (isset($data['error'])) {
            return ['success' => false, 'message' => $data['error']];
        }

        $saved = $this->feedModel->update($recordId, $data);
        if ($saved && function_exists('audit_log')) {
            audit_log('feed_usage', 'update', 'feed_record', $recordId, [
                'batch_id' => $data['batch_id'],
                'feed_type' => $data['feed_type'],
                'total_cost' => $data['total_cost'],
            ]);
        }
        return $saved
            ? ['success' => true, 'message' => 'Feed usage updated successfully.']
            : ['success' => false, 'message' => 'Unable to update feed usage.'];
    }

    public function delete(array $input): array
    {
        $recordId = isset($input['record_id']) ? (int)$input['record_id'] : 0;
        if ($recordId <= 0 || !$this->feedModel->find($recordId)) {
            return ['success' => false, 'message' => 'Feed record not found.'];
        }

        $deleted = $this->feedModel->delete($recordId);
        if ($deleted && function_exists('audit_log')) {
            audit_log('feed_usage', 'delete', 'feed_record', $recordId, []);
        }
        return $deleted
            ? ['success' => true, 'message' => 'Feed usage deleted successfully.']
            : ['success' => false, 'message' => 'Unable to delete feed usage.'];
    }

    private function validatedData(array $input): array
    {
        $data = [
            'batch_id' => isset($input['batch_id']) ? (int)$input['batch_id'] : 0,
            'date' => $input['date'] ?? null,
            'feed_type' => trim($input['feed_type'] ?? ''),
            'feed_kg' => isset($input['feed_kg']) ? (float)$input['feed_kg'] : 0.0,
            'cost_per_kg' => isset($input['cost_per_kg']) ? (float)$input['cost_per_kg'] : 0.0,
        ];

        if ($data['batch_id'] <= 0) {
            return ['error' => 'Choose a batch before logging feed usage.'];
        }

        if ($data['feed_type'] === '' || !is_valid_date($data['date'])) {
            return ['error' => 'Provide feed type and a valid date.'];
        }

        if ($data['feed_kg'] <= 0 || $data['cost_per_kg'] <= 0) {
            return ['error' => 'Feed quantity and cost must be greater than zero.'];
        }

        $data['total_cost'] = $data['feed_kg'] * $data['cost_per_kg'];
        return $data;
    }

}
