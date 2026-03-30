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

        $data['created_by'] = $this->currentUserId();
        $result = $this->saleModel->createWithBatchUpdate($data);
        if (($result['success'] ?? false) && function_exists('audit_log')) {
            audit_log('sales', 'create', 'sale', isset($result['sale_id']) ? (int)$result['sale_id'] : null, [
                'batch_id' => $data['batch_id'],
                'birds_sold' => $data['birds_sold'],
                'total_revenue' => $data['total_revenue'],
                'paid_amount' => $data['paid_amount'],
                'balance_amount' => $data['balance_amount'],
            ]);
        }

        return $result;
    }

    public function update(array $input): array
    {
        if (!$this->isAdmin()) {
            return ['success' => false, 'message' => 'Only administrators can edit sale details.'];
        }

        $saleId = isset($input['sale_id']) ? (int)$input['sale_id'] : 0;
        if ($saleId <= 0 || !$this->saleModel->find($saleId)) {
            return ['success' => false, 'message' => 'Sale record not found.'];
        }

        $data = $this->validatedData($input);
        if (isset($data['error'])) {
            return ['success' => false, 'message' => $data['error']];
        }

        $result = $this->saleModel->updateWithBatchReconcile($saleId, $data, $this->salespersonOwnerId());
        if (($result['success'] ?? false) && function_exists('audit_log')) {
            audit_log('sales', 'update', 'sale', $saleId, [
                'batch_id' => $data['batch_id'],
                'birds_sold' => $data['birds_sold'],
                'total_revenue' => $data['total_revenue'],
            ]);
        }

        return $result;
    }

    public function delete(array $input): array
    {
        if (!$this->isAdmin()) {
            return ['success' => false, 'message' => 'Only administrators can delete sale records.'];
        }

        $saleId = isset($input['sale_id']) ? (int)$input['sale_id'] : 0;
        if ($saleId <= 0 || !$this->saleModel->find($saleId)) {
            return ['success' => false, 'message' => 'Sale record not found.'];
        }

        $result = $this->saleModel->deleteWithBatchRestore($saleId, $this->salespersonOwnerId());
        if (($result['success'] ?? false) && function_exists('audit_log')) {
            audit_log('sales', 'delete', 'sale', $saleId, []);
        }

        return $result;
    }

    public function addPayment(array $input): array
    {
        $saleId = isset($input['sale_id']) ? (int)$input['sale_id'] : 0;
        if ($saleId <= 0 || !$this->saleModel->find($saleId)) {
            return ['success' => false, 'message' => 'Sale record not found.'];
        }

        $paymentData = $this->validatedPaymentData($input);
        if (isset($paymentData['error'])) {
            return ['success' => false, 'message' => $paymentData['error']];
        }

        $paymentData['recorded_by'] = $this->currentUserId();
        $result = $this->saleModel->addPayment($saleId, $paymentData);
        if (($result['success'] ?? false) && function_exists('audit_log')) {
            audit_log('sales', 'add_payment', 'sale', $saleId, [
                'payment_id' => isset($result['payment_id']) ? (int)$result['payment_id'] : null,
                'payment_date' => $paymentData['payment_date'],
                'amount' => $paymentData['amount'],
                'notes' => $paymentData['notes'],
            ]);
        }

        return $result;
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

    private function validatedPaymentData(array $input): array
    {
        $paymentDate = $input['payment_date'] ?? null;
        $amount = isset($input['payment_amount']) ? (float)$input['payment_amount'] : 0.0;
        $notes = trim((string)($input['payment_notes'] ?? ''));

        if (!is_valid_date($paymentDate)) {
            return ['error' => 'Provide a valid payment date.'];
        }

        if ($amount <= 0) {
            return ['error' => 'Payment amount must be greater than zero.'];
        }

        return [
            'payment_date' => $paymentDate,
            'amount' => $amount,
            'notes' => $notes !== '' ? $notes : null,
        ];
    }

    private function currentUserId(): ?int
    {
        $userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
        return $userId > 0 ? $userId : null;
    }

    private function isAdmin(): bool
    {
        $role = (string)($_SESSION['role'] ?? '');
        return $role === 'admin';
    }
}
