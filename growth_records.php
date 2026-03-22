<?php
declare(strict_types=1);

require_once __DIR__ . '/config/bootstrap.php';
require_once __DIR__ . '/controllers/BatchController.php';
require_once __DIR__ . '/controllers/GrowthController.php';

require_admin();

$pdo = app_db();
$batchController = new BatchController($pdo);
$growthController = new GrowthController($pdo);

$batches = $batchController->list();
$defaultBatchId = $batchController->defaultBatchId();
$selectedBatchId = isset($_REQUEST['batch_id']) ? (int)$_REQUEST['batch_id'] : ($defaultBatchId ?? 0);
$editRecordId = isset($_GET['edit_id']) ? (int)$_GET['edit_id'] : 0;
$editingGrowthRecord = $editRecordId > 0 ? $growthController->find($editRecordId) : null;

$growthFeedback = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $growthFeedback = ['success' => false, 'message' => 'Invalid form submission. Please try again.'];
    } else {
        $action = $_POST['action'] ?? 'create';
        if ($action === 'update') {
            $growthFeedback = $growthController->update($_POST);
        } elseif ($action === 'delete') {
            $growthFeedback = $growthController->delete($_POST);
        } else {
            $growthFeedback = $growthController->store($_POST);
        }

        if ($growthFeedback['success']) {
            $redirectBatchId = isset($_POST['batch_id']) ? (int)$_POST['batch_id'] : $selectedBatchId;
            header('Location: growth_records.php?batch_id=' . $redirectBatchId);
            exit;
        }
    }
}

$growthRecords = $selectedBatchId > 0 ? $growthController->list($selectedBatchId) : [];

$pageTitle = 'Growth Records';
include __DIR__ . '/views/layout_header.php';
include __DIR__ . '/views/growth_records.php';
include __DIR__ . '/views/layout_footer.php';
