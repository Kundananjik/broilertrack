<?php
declare(strict_types=1);

require_once __DIR__ . '/../models/Expense.php';
require_once __DIR__ . '/../config/helpers.php';

class ExpenseController
{
    private Expense $expenseModel;

    public function __construct(PDO $pdo)
    {
        $this->expenseModel = new Expense($pdo);
    }

    public function list(int $batchId): array
    {
        return $this->expenseModel->forBatch($batchId);
    }

    public function totalCost(int $batchId): float
    {
        return $this->expenseModel->totalCostByBatch($batchId);
    }

    public function find(int $expenseId): ?array
    {
        return $this->expenseModel->find($expenseId);
    }

    public function store(array $input): array
    {
        $data = $this->validatedData($input);
        if (isset($data['error'])) {
            return ['success' => false, 'message' => $data['error']];
        }

        $saved = $this->expenseModel->create($data);
        if ($saved && function_exists('audit_log')) {
            audit_log('expenses', 'create', 'expense', null, [
                'batch_id' => $data['batch_id'],
                'category' => $data['category'],
                'total_cost' => $data['total_cost'],
            ]);
        }
        return $saved
            ? ['success' => true, 'message' => 'Expense recorded successfully.']
            : ['success' => false, 'message' => 'Failed to record expense.'];
    }

    public function update(array $input): array
    {
        $expenseId = isset($input['expense_id']) ? (int)$input['expense_id'] : 0;
        if ($expenseId <= 0 || !$this->expenseModel->find($expenseId)) {
            return ['success' => false, 'message' => 'Expense record not found.'];
        }

        $data = $this->validatedData($input);
        if (isset($data['error'])) {
            return ['success' => false, 'message' => $data['error']];
        }

        $saved = $this->expenseModel->update($expenseId, $data);
        if ($saved && function_exists('audit_log')) {
            audit_log('expenses', 'update', 'expense', $expenseId, [
                'batch_id' => $data['batch_id'],
                'category' => $data['category'],
                'total_cost' => $data['total_cost'],
            ]);
        }
        return $saved
            ? ['success' => true, 'message' => 'Expense updated successfully.']
            : ['success' => false, 'message' => 'Failed to update expense.'];
    }

    public function delete(array $input): array
    {
        $expenseId = isset($input['expense_id']) ? (int)$input['expense_id'] : 0;
        if ($expenseId <= 0 || !$this->expenseModel->find($expenseId)) {
            return ['success' => false, 'message' => 'Expense record not found.'];
        }

        $deleted = $this->expenseModel->delete($expenseId);
        if ($deleted && function_exists('audit_log')) {
            audit_log('expenses', 'delete', 'expense', $expenseId, []);
        }
        return $deleted
            ? ['success' => true, 'message' => 'Expense deleted successfully.']
            : ['success' => false, 'message' => 'Failed to delete expense.'];
    }

    private function validatedData(array $input): array
    {
        $data = [
            'batch_id' => isset($input['batch_id']) ? (int)$input['batch_id'] : 0,
            'date' => $input['date'] ?? null,
            'category' => trim($input['category'] ?? ''),
            'item_name' => trim($input['item_name'] ?? ''),
            'quantity' => isset($input['quantity']) ? (float)$input['quantity'] : 0.0,
            'unit_cost' => isset($input['unit_cost']) ? (float)$input['unit_cost'] : 0.0,
            'supplier' => trim($input['supplier'] ?? ''),
            'notes' => trim($input['notes'] ?? ''),
        ];

        if ($data['batch_id'] <= 0) {
            return ['error' => 'Choose a batch before logging expenses.'];
        }

        if ($data['category'] === '' || $data['item_name'] === '') {
            return ['error' => 'Category and item name are required.'];
        }

        if (!is_valid_date($data['date'])) {
            return ['error' => 'Enter a valid date.'];
        }

        if ($data['quantity'] <= 0 || $data['unit_cost'] <= 0) {
            return ['error' => 'Quantity and unit cost must be greater than zero.'];
        }

        $data['total_cost'] = $data['quantity'] * $data['unit_cost'];

        return $data;
    }

}
