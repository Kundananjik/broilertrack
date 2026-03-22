<?php
declare(strict_types=1);

require_once __DIR__ . '/config/bootstrap.php';
require_once __DIR__ . '/controllers/BatchController.php';
require_once __DIR__ . '/controllers/FeedController.php';

require_admin();

$pdo = app_db();
$batchController = new BatchController($pdo);
$feedController = new FeedController($pdo);

$batches = $batchController->list();
$defaultBatchId = $batchController->defaultBatchId();
$selectedBatchId = isset($_REQUEST['batch_id']) ? (int)$_REQUEST['batch_id'] : ($defaultBatchId ?? 0);
$editRecordId = isset($_GET['edit_id']) ? (int)$_GET['edit_id'] : 0;
$editingFeedRecord = $editRecordId > 0 ? $feedController->find($editRecordId) : null;

$feedFeedback = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $feedFeedback = ['success' => false, 'message' => 'Invalid form submission. Please try again.'];
    } else {
        $action = $_POST['action'] ?? 'create';
        if ($action === 'update') {
            $feedFeedback = $feedController->update($_POST);
        } elseif ($action === 'delete') {
            $feedFeedback = $feedController->delete($_POST);
        } else {
            $feedFeedback = $feedController->store($_POST);
        }

        if ($feedFeedback['success']) {
            $redirectBatchId = isset($_POST['batch_id']) ? (int)$_POST['batch_id'] : $selectedBatchId;
            header('Location: feed_usage.php?batch_id=' . $redirectBatchId);
            exit;
        }
    }
}

$feedUsage = $selectedBatchId > 0 ? $feedController->list($selectedBatchId) : [];
$totalFeedKg = $selectedBatchId > 0 ? $feedController->totalFeedKg($selectedBatchId) : 0.0;
$totalFeedCost = $selectedBatchId > 0 ? $feedController->totalFeedCost($selectedBatchId) : 0.0;

$pageTitle = 'Feed Usage';
include __DIR__ . '/views/layout_header.php';
include __DIR__ . '/views/feed_usage.php';
include __DIR__ . '/views/layout_footer.php';
