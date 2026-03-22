<?php
declare(strict_types=1);

require_once __DIR__ . '/config/bootstrap.php';
require_once __DIR__ . '/controllers/BatchController.php';

require_admin();

$pdo = app_db();
$batchController = new BatchController($pdo);

$batchFeedback = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $batchFeedback = ['success' => false, 'message' => 'Invalid form submission. Please try again.'];
    } else {
        $batchFeedback = $batchController->create($_POST);
        if ($batchFeedback['success']) {
            header('Location: add_batch.php?msg=created');
            exit;
        }
    }
}

if (isset($_GET['msg']) && $_GET['msg'] === 'created') {
    $batchFeedback = ['success' => true, 'message' => 'Batch created successfully.'];
}

$pageTitle = 'Add Batch';
$batches = $batchController->list();
include __DIR__ . '/views/layout_header.php';
include __DIR__ . '/views/add_batch.php';
include __DIR__ . '/views/layout_footer.php';
