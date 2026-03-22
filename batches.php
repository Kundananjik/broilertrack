<?php
declare(strict_types=1);

require_once __DIR__ . '/config/bootstrap.php';
require_once __DIR__ . '/controllers/BatchController.php';

require_admin();

$pdo = app_db();
$batchController = new BatchController($pdo);

$batchFeedback = [];
$editBatchId = isset($_GET['edit_id']) ? (int)$_GET['edit_id'] : 0;
$editingBatch = $editBatchId > 0 ? $batchController->find($editBatchId) : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $batchFeedback = ['success' => false, 'message' => 'Invalid form submission. Please try again.'];
    } else {
        $action = $_POST['action'] ?? '';
        if ($action === 'update_status') {
            $batchFeedback = $batchController->updateStatus($_POST);
        } elseif ($action === 'update_batch') {
            $batchFeedback = $batchController->update($_POST);
        } elseif ($action === 'delete_batch') {
            $batchFeedback = $batchController->delete($_POST);
        } else {
            $batchFeedback = ['success' => false, 'message' => 'Unknown action.'];
        }

        if ($batchFeedback['success']) {
            header('Location: batches.php');
            exit;
        }
    }
}

$batches = $batchController->list();
$pageTitle = 'Batches';
include __DIR__ . '/views/layout_header.php';
include __DIR__ . '/views/batches.php';
include __DIR__ . '/views/layout_footer.php';
