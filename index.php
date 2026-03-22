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
if ($isSalesperson && $metrics['batch'] !== null) {
    $recentSales = $dashboardController->recentSales((int)$metrics['batch']['batch_id']);
}

$pageTitle = $isSalesperson ? 'Sales Dashboard' : 'Dashboard';
include __DIR__ . '/views/layout_header.php';
include __DIR__ . ($isSalesperson ? '/views/sales_dashboard.php' : '/views/dashboard.php');
include __DIR__ . '/views/layout_footer.php';
