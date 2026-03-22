<?php
declare(strict_types=1);

require_once __DIR__ . '/config/bootstrap.php';
require_once __DIR__ . '/controllers/BatchController.php';
require_once __DIR__ . '/controllers/ExpenseController.php';

require_admin();

$pdo = app_db();
$batchController = new BatchController($pdo);
$expenseController = new ExpenseController($pdo);

$batches = $batchController->list();
$defaultBatchId = $batchController->defaultBatchId();
$selectedBatchId = isset($_REQUEST['batch_id']) ? (int)$_REQUEST['batch_id'] : ($defaultBatchId ?? 0);
$editExpenseId = isset($_GET['edit_id']) ? (int)$_GET['edit_id'] : 0;
$editingExpense = $editExpenseId > 0 ? $expenseController->find($editExpenseId) : null;

$expenseFeedback = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $expenseFeedback = ['success' => false, 'message' => 'Invalid form submission. Please try again.'];
    } else {
        $action = $_POST['action'] ?? 'create';
        if ($action === 'update') {
            $expenseFeedback = $expenseController->update($_POST);
        } elseif ($action === 'delete') {
            $expenseFeedback = $expenseController->delete($_POST);
        } else {
            $expenseFeedback = $expenseController->store($_POST);
        }

        if ($expenseFeedback['success']) {
            $redirectBatchId = isset($_POST['batch_id']) ? (int)$_POST['batch_id'] : $selectedBatchId;
            header('Location: expenses.php?batch_id=' . $redirectBatchId);
            exit;
        }
    }
}

$expenses = $selectedBatchId > 0 ? $expenseController->list($selectedBatchId) : [];
$totalExpenseValue = $selectedBatchId > 0 ? $expenseController->totalCost($selectedBatchId) : 0.0;

$pageTitle = 'Expenses';
include __DIR__ . '/views/layout_header.php';
include __DIR__ . '/views/expenses.php';
include __DIR__ . '/views/layout_footer.php';
