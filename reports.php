<?php
declare(strict_types=1);

require_once __DIR__ . '/config/bootstrap.php';
require_once __DIR__ . '/controllers/BatchController.php';

require_admin();

$pdo = app_db();
$batchController = new BatchController($pdo);
$batches = $batchController->list();

$selectedBatchId = isset($_GET['batch_id']) ? (int)$_GET['batch_id'] : 0;
$dateFrom = trim((string)($_GET['date_from'] ?? ''));
$dateTo = trim((string)($_GET['date_to'] ?? ''));
$export = (string)($_GET['export'] ?? '');

$conditions = ['s.is_deleted = 0'];
$params = [];

if ($selectedBatchId > 0) {
    $conditions[] = 's.batch_id = :batch_id';
    $params['batch_id'] = $selectedBatchId;
}
if ($dateFrom !== '') {
    $conditions[] = 's.date >= :date_from';
    $params['date_from'] = $dateFrom;
}
if ($dateTo !== '') {
    $conditions[] = 's.date <= :date_to';
    $params['date_to'] = $dateTo;
}
$salesWhere = implode(' AND ', $conditions);

$expenseConditions = ['e.is_deleted = 0'];
$expenseParams = [];
if ($selectedBatchId > 0) {
    $expenseConditions[] = 'e.batch_id = :batch_id';
    $expenseParams['batch_id'] = $selectedBatchId;
}
if ($dateFrom !== '') {
    $expenseConditions[] = 'e.date >= :date_from';
    $expenseParams['date_from'] = $dateFrom;
}
if ($dateTo !== '') {
    $expenseConditions[] = 'e.date <= :date_to';
    $expenseParams['date_to'] = $dateTo;
}
$expenseWhere = implode(' AND ', $expenseConditions);

$summaryStmt = $pdo->prepare(
    "SELECT
        COALESCE(SUM(s.total_revenue), 0) AS total_revenue,
        COALESCE(SUM(s.paid_amount), 0) AS total_paid,
        COALESCE(SUM(s.balance_amount), 0) AS total_balance,
        COALESCE(SUM(s.birds_sold), 0) AS birds_sold
     FROM sales s
     WHERE {$salesWhere}"
);
$summaryStmt->execute($params);
$salesSummary = $summaryStmt->fetch() ?: ['total_revenue' => 0, 'total_paid' => 0, 'total_balance' => 0, 'birds_sold' => 0];

$expenseStmt = $pdo->prepare("SELECT COALESCE(SUM(e.total_cost), 0) AS total_expenses FROM expenses e WHERE {$expenseWhere}");
$expenseStmt->execute($expenseParams);
$expenseSummary = $expenseStmt->fetch() ?: ['total_expenses' => 0];

$salesStmt = $pdo->prepare(
    "SELECT s.sale_id, s.date, b.batch_name, s.birds_sold, s.price_per_bird, s.total_revenue, s.paid_amount, s.balance_amount, s.buyer
     FROM sales s
     INNER JOIN batches b ON b.batch_id = s.batch_id
     WHERE {$salesWhere}
     ORDER BY s.date DESC, s.sale_id DESC"
);
$salesStmt->execute($params);
$salesRows = $salesStmt->fetchAll() ?: [];

$totalRevenue = (float)$salesSummary['total_revenue'];
$totalPaid = (float)$salesSummary['total_paid'];
$totalBalance = (float)$salesSummary['total_balance'];
$totalExpenses = (float)$expenseSummary['total_expenses'];
$netProfit = $totalRevenue - $totalExpenses;

if ($export === 'csv') {
    $filename = 'broilertrack_report_' . date('Ymd_His') . '.csv';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=' . $filename);

    $out = fopen('php://output', 'w');
    if ($out !== false) {
        fputcsv($out, ['Metric', 'Value']);
        fputcsv($out, ['Total Revenue', number_format($totalRevenue, 2, '.', '')]);
        fputcsv($out, ['Total Paid', number_format($totalPaid, 2, '.', '')]);
        fputcsv($out, ['Outstanding Balance', number_format($totalBalance, 2, '.', '')]);
        fputcsv($out, ['Total Expenses', number_format($totalExpenses, 2, '.', '')]);
        fputcsv($out, ['Net Profit', number_format($netProfit, 2, '.', '')]);
        fputcsv($out, []);
        fputcsv($out, ['Sale ID', 'Date', 'Batch', 'Birds Sold', 'Price per Bird', 'Revenue', 'Paid', 'Balance', 'Buyer']);
        foreach ($salesRows as $row) {
            fputcsv($out, [
                (int)$row['sale_id'],
                (string)$row['date'],
                (string)$row['batch_name'],
                (int)$row['birds_sold'],
                number_format((float)$row['price_per_bird'], 2, '.', ''),
                number_format((float)$row['total_revenue'], 2, '.', ''),
                number_format((float)$row['paid_amount'], 2, '.', ''),
                number_format((float)$row['balance_amount'], 2, '.', ''),
                (string)($row['buyer'] ?? ''),
            ]);
        }
        fclose($out);
    }
    exit;
}

$pageTitle = 'Reports';
include __DIR__ . '/views/layout_header.php';
?>
<section class="form-section">
    <h2>Sales & Collection Report</h2>
    <form method="get" class="form-grid">
        <label>Batch
            <select name="batch_id">
                <option value="0">All batches</option>
                <?php foreach ($batches as $batch): ?>
                    <option value="<?= (int)$batch['batch_id']; ?>" <?= $selectedBatchId === (int)$batch['batch_id'] ? 'selected' : ''; ?>>
                        <?= htmlspecialchars((string)$batch['batch_name'], ENT_QUOTES, 'UTF-8'); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>From
            <input type="date" name="date_from" value="<?= htmlspecialchars($dateFrom, ENT_QUOTES, 'UTF-8'); ?>">
        </label>
        <label>To
            <input type="date" name="date_to" value="<?= htmlspecialchars($dateTo, ENT_QUOTES, 'UTF-8'); ?>">
        </label>
        <button type="submit">Apply Filters</button>
    </form>
    <p class="muted">
        <a class="btn-secondary" href="reports.php?batch_id=<?= (int)$selectedBatchId; ?>&date_from=<?= urlencode($dateFrom); ?>&date_to=<?= urlencode($dateTo); ?>&export=csv">Export CSV</a>
    </p>
</section>

<section class="card-grid">
    <article class="card">
        <p class="label">Total Revenue</p>
        <p class="value">ZMW <?= number_format($totalRevenue, 2); ?></p>
    </article>
    <article class="card">
        <p class="label">Total Paid</p>
        <p class="value">ZMW <?= number_format($totalPaid, 2); ?></p>
    </article>
    <article class="card">
        <p class="label">Outstanding Balance</p>
        <p class="value">ZMW <?= number_format($totalBalance, 2); ?></p>
    </article>
    <article class="card">
        <p class="label">Total Expenses</p>
        <p class="value">ZMW <?= number_format($totalExpenses, 2); ?></p>
    </article>
    <article class="card">
        <p class="label">Net Profit</p>
        <p class="value">ZMW <?= number_format($netProfit, 2); ?></p>
    </article>
</section>

<section class="table-section">
    <h2>Sales Records</h2>
    <div class="table-responsive">
        <table class="table table-striped table-bordered align-middle">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Batch</th>
                    <th>Birds Sold</th>
                    <th>Price/Bird</th>
                    <th>Revenue</th>
                    <th>Paid</th>
                    <th>Balance</th>
                    <th>Buyer</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($salesRows as $row): ?>
                    <tr>
                        <td><?= htmlspecialchars((string)$row['date'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?= htmlspecialchars((string)$row['batch_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?= number_format((int)$row['birds_sold']); ?></td>
                        <td>ZMW <?= number_format((float)$row['price_per_bird'], 2); ?></td>
                        <td>ZMW <?= number_format((float)$row['total_revenue'], 2); ?></td>
                        <td>ZMW <?= number_format((float)$row['paid_amount'], 2); ?></td>
                        <td>ZMW <?= number_format((float)$row['balance_amount'], 2); ?></td>
                        <td><?= htmlspecialchars((string)($row['buyer'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php include __DIR__ . '/views/layout_footer.php'; ?>
