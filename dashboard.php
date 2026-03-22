<?php
declare(strict_types=1);

require_once __DIR__ . '/config/bootstrap.php';
require_once __DIR__ . '/controllers/DashboardController.php';

require_auth();

$pdo = app_db();
$dashboardController = new DashboardController($pdo);

$selectedBatchId = isset($_GET['batch_id']) ? (int)$_GET['batch_id'] : null;
$metrics = $dashboardController->getMetrics($selectedBatchId);
$batches = $dashboardController->listBatches();
$isSalesperson = current_user_role() === 'salesperson';
$recentSales = [];
$collectionSummary = [
    'revenue_total' => 0.0,
    'paid_total' => 0.0,
    'balance_total' => 0.0,
    'balance_count' => 0,
];
$overdueBalances = [];
if ($isSalesperson && $metrics['batch'] !== null) {
    $batchId = (int)$metrics['batch']['batch_id'];
    $ownerId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
    $recentSales = $dashboardController->recentSales($batchId, 8, $ownerId > 0 ? $ownerId : -1);
    $collectionSummary = $dashboardController->collectionSummary($batchId, $ownerId > 0 ? $ownerId : -1);
    $overdueBalances = $dashboardController->overdueBalances($batchId, 7, 6, $ownerId > 0 ? $ownerId : -1);
} elseif ($metrics['batch'] !== null) {
    $batchId = (int)$metrics['batch']['batch_id'];
    $collectionSummary = $dashboardController->collectionSummary($batchId);
    $overdueBalances = $dashboardController->overdueBalances($batchId, 7, 6);
}

$pageTitle = $isSalesperson ? 'Sales Dashboard' : 'Dashboard';
include __DIR__ . '/views/layout_header.php';
include __DIR__ . ($isSalesperson ? '/views/sales_dashboard.php' : '/views/dashboard.php');
include __DIR__ . '/views/layout_footer.php';
