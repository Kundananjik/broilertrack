<?php
declare(strict_types=1);

require_once __DIR__ . '/config/bootstrap.php';
require_once __DIR__ . '/controllers/BatchController.php';
require_once __DIR__ . '/controllers/SaleController.php';

require_roles(['admin', 'salesperson']);

$pdo = app_db();
$batchController = new BatchController($pdo);
$saleController = new SaleController($pdo);

$batches = $batchController->list();
$defaultBatchId = $batchController->defaultBatchId();
$selectedBatchId = isset($_REQUEST['batch_id']) ? (int)$_REQUEST['batch_id'] : ($defaultBatchId ?? 0);
$editSaleId = isset($_GET['edit_id']) ? (int)$_GET['edit_id'] : 0;
$isAdmin = current_user_role() === 'admin';
$editingSale = ($isAdmin && $editSaleId > 0) ? $saleController->find($editSaleId) : null;

$saleFeedback = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $saleFeedback = ['success' => false, 'message' => 'Invalid form submission. Please try again.'];
    } else {
        $action = $_POST['action'] ?? 'create';
        if ($action === 'update') {
            $saleFeedback = $saleController->update($_POST);
        } elseif ($action === 'delete') {
            $saleFeedback = $saleController->delete($_POST);
        } elseif ($action === 'add_payment') {
            $saleFeedback = $saleController->addPayment($_POST);
        } else {
            $saleFeedback = $saleController->store($_POST);
        }

        if ($saleFeedback['success']) {
            $redirectBatchId = isset($_POST['batch_id']) ? (int)$_POST['batch_id'] : $selectedBatchId;
            header('Location: sales.php?batch_id=' . $redirectBatchId);
            exit;
        }
    }
}

$sales = $selectedBatchId > 0 ? $saleController->list($selectedBatchId) : [];

$pageTitle = 'Sales';
include __DIR__ . '/views/layout_header.php';
include __DIR__ . '/views/sales.php';
include __DIR__ . '/views/layout_footer.php';
