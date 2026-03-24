<?php
declare(strict_types=1);

require_once __DIR__ . '/config/bootstrap.php';
require_once __DIR__ . '/controllers/BatchController.php';

require_admin();

function pdf_escape(string $value): string
{
    return str_replace(
        ['\\', '(', ')'],
        ['\\\\', '\\(', '\\)'],
        $value
    );
}

function pdf_truncate(string $value, int $length): string
{
    if ($length <= 0) {
        return '';
    }
    if (strlen($value) <= $length) {
        return $value;
    }
    if ($length === 1) {
        return '.';
    }
    return substr($value, 0, $length - 1) . '.';
}

function build_report_pdf(string $title, array $summaryLines, array $salesRows): string
{
    $lineLimit = 52;
    $pages = [[]];
    $pageIndex = 0;
    $addLine = static function (string $line) use (&$pages, &$pageIndex, $lineLimit): void {
        if (count($pages[$pageIndex]) >= $lineLimit) {
            $pages[] = [];
            $pageIndex++;
        }
        $pages[$pageIndex][] = $line;
    };

    $addLine($title);
    $addLine('Generated: ' . date('Y-m-d H:i:s'));
    $addLine('');
    foreach ($summaryLines as $line) {
        $addLine($line);
    }
    $addLine('');
    $addLine('Sales Records');
    $addLine('Date       Batch              Birds  Price    Revenue   Paid      Balance   Buyer');
    $addLine(str_repeat('-', 90));
    foreach ($salesRows as $row) {
        $line = sprintf(
            '%-10s %-18s %5d %8.2f %9.2f %9.2f %9.2f %-20s',
            (string)$row['date'],
            pdf_truncate((string)$row['batch_name'], 18),
            (int)$row['birds_sold'],
            (float)$row['price_per_bird'],
            (float)$row['total_revenue'],
            (float)$row['paid_amount'],
            (float)$row['balance_amount'],
            pdf_truncate((string)($row['buyer'] ?? ''), 20)
        );
        $addLine($line);
    }

    $objects = [];
    $objects[1] = '<< /Type /Catalog /Pages 2 0 R >>';
    $objects[3] = '<< /Type /Font /Subtype /Type1 /BaseFont /Courier >>';

    $kids = [];
    $nextObjectId = 4;
    foreach ($pages as $pageLines) {
        $content = "BT\n/F1 10 Tf\n14 TL\n40 800 Td\n";
        foreach ($pageLines as $index => $line) {
            if ($index > 0) {
                $content .= "T*\n";
            }
            $content .= '(' . pdf_escape($line) . ") Tj\n";
        }
        $content .= "ET";

        $contentObjectId = $nextObjectId++;
        $pageObjectId = $nextObjectId++;

        $objects[$contentObjectId] = "<< /Length " . strlen($content) . " >>\nstream\n" . $content . "\nendstream";
        $objects[$pageObjectId] = "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 3 0 R >> >> /Contents {$contentObjectId} 0 R >>";
        $kids[] = "{$pageObjectId} 0 R";
    }

    $objects[2] = '<< /Type /Pages /Kids [ ' . implode(' ', $kids) . ' ] /Count ' . count($kids) . ' >>';
    ksort($objects);

    $pdf = "%PDF-1.4\n";
    $offsets = [0];
    foreach ($objects as $id => $body) {
        $offsets[$id] = strlen($pdf);
        $pdf .= $id . " 0 obj\n" . $body . "\nendobj\n";
    }

    $maxId = (int)max(array_keys($objects));
    $xrefPos = strlen($pdf);
    $pdf .= "xref\n";
    $pdf .= "0 " . ($maxId + 1) . "\n";
    $pdf .= "0000000000 65535 f \n";
    for ($i = 1; $i <= $maxId; $i++) {
        $offset = $offsets[$i] ?? 0;
        $pdf .= sprintf("%010d 00000 n \n", $offset);
    }
    $pdf .= "trailer\n<< /Size " . ($maxId + 1) . " /Root 1 0 R >>\n";
    $pdf .= "startxref\n" . $xrefPos . "\n%%EOF";

    return $pdf;
}

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

$feedConditions = ['f.is_deleted = 0'];
$feedParams = [];
if ($selectedBatchId > 0) {
    $feedConditions[] = 'f.batch_id = :batch_id';
    $feedParams['batch_id'] = $selectedBatchId;
}
if ($dateFrom !== '') {
    $feedConditions[] = 'f.date >= :date_from';
    $feedParams['date_from'] = $dateFrom;
}
if ($dateTo !== '') {
    $feedConditions[] = 'f.date <= :date_to';
    $feedParams['date_to'] = $dateTo;
}
$feedWhere = implode(' AND ', $feedConditions);

$chickConditions = ['1 = 1'];
$chickParams = [];
if ($selectedBatchId > 0) {
    $chickConditions[] = 'b.batch_id = :batch_id';
    $chickParams['batch_id'] = $selectedBatchId;
}
if ($dateFrom !== '') {
    $chickConditions[] = 'b.start_date >= :date_from';
    $chickParams['date_from'] = $dateFrom;
}
if ($dateTo !== '') {
    $chickConditions[] = 'b.start_date <= :date_to';
    $chickParams['date_to'] = $dateTo;
}
$chickWhere = implode(' AND ', $chickConditions);

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

$feedExpenseStmt = $pdo->prepare("SELECT COALESCE(SUM(f.total_cost), 0) AS feed_expenses FROM feed_usage f WHERE {$feedWhere}");
$feedExpenseStmt->execute($feedParams);
$feedExpenseSummary = $feedExpenseStmt->fetch() ?: ['feed_expenses' => 0];

$chickCostStmt = $pdo->prepare("SELECT COALESCE(SUM(b.total_chick_cost), 0) AS chick_cost FROM batches b WHERE {$chickWhere}");
$chickCostStmt->execute($chickParams);
$chickCostSummary = $chickCostStmt->fetch() ?: ['chick_cost' => 0];

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
$operationalExpenses = (float)$expenseSummary['total_expenses'];
$feedExpenses = (float)$feedExpenseSummary['feed_expenses'];
$chickCost = (float)$chickCostSummary['chick_cost'];
$totalExpenses = $operationalExpenses + $feedExpenses + $chickCost;
$netProfit = $totalRevenue - $totalExpenses;

if (in_array($export, ['csv', 'excel', 'pdf'], true)) {
    $summaryRows = [
        ['Total Revenue', number_format($totalRevenue, 2, '.', '')],
        ['Total Paid', number_format($totalPaid, 2, '.', '')],
        ['Outstanding Balance', number_format($totalBalance, 2, '.', '')],
        ['Expense Entries', number_format($operationalExpenses, 2, '.', '')],
        ['Feed Expenses', number_format($feedExpenses, 2, '.', '')],
        ['Chick Cost', number_format($chickCost, 2, '.', '')],
        ['Total Expenses', number_format($totalExpenses, 2, '.', '')],
        ['Net Profit', number_format($netProfit, 2, '.', '')],
    ];

    if ($export === 'csv') {
        $filename = 'broilertrack_report_' . date('Ymd_His') . '.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $filename);

        $out = fopen('php://output', 'w');
        if ($out !== false) {
            fputcsv($out, ['Metric', 'Value']);
            foreach ($summaryRows as $summaryRow) {
                fputcsv($out, $summaryRow);
            }
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

    if ($export === 'excel') {
        $filename = 'broilertrack_report_' . date('Ymd_His') . '.xls';
        header('Content-Type: application/vnd.ms-excel; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $filename);

        echo "<table border='1'>";
        echo "<tr><th colspan='2'>Report Summary</th></tr>";
        foreach ($summaryRows as $summaryRow) {
            echo '<tr><td>' . htmlspecialchars($summaryRow[0], ENT_QUOTES, 'UTF-8') . '</td><td>' . htmlspecialchars($summaryRow[1], ENT_QUOTES, 'UTF-8') . "</td></tr>";
        }
        echo "</table><br>";
        echo "<table border='1'>";
        echo "<tr><th>Sale ID</th><th>Date</th><th>Batch</th><th>Birds Sold</th><th>Price per Bird</th><th>Revenue</th><th>Paid</th><th>Balance</th><th>Buyer</th></tr>";
        foreach ($salesRows as $row) {
            echo '<tr>';
            echo '<td>' . (int)$row['sale_id'] . '</td>';
            echo '<td>' . htmlspecialchars((string)$row['date'], ENT_QUOTES, 'UTF-8') . '</td>';
            echo '<td>' . htmlspecialchars((string)$row['batch_name'], ENT_QUOTES, 'UTF-8') . '</td>';
            echo '<td>' . (int)$row['birds_sold'] . '</td>';
            echo '<td>' . number_format((float)$row['price_per_bird'], 2, '.', '') . '</td>';
            echo '<td>' . number_format((float)$row['total_revenue'], 2, '.', '') . '</td>';
            echo '<td>' . number_format((float)$row['paid_amount'], 2, '.', '') . '</td>';
            echo '<td>' . number_format((float)$row['balance_amount'], 2, '.', '') . '</td>';
            echo '<td>' . htmlspecialchars((string)($row['buyer'] ?? ''), ENT_QUOTES, 'UTF-8') . '</td>';
            echo '</tr>';
        }
        echo "</table>";
        exit;
    }

    $summaryLines = [];
    foreach ($summaryRows as $summaryRow) {
        $summaryLines[] = $summaryRow[0] . ': ZMW ' . $summaryRow[1];
    }
    $pdfContent = build_report_pdf('BroilerTrack Report', $summaryLines, $salesRows);
    $filename = 'broilertrack_report_' . date('Ymd_His') . '.pdf';
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename=' . $filename);
    header('Content-Length: ' . strlen($pdfContent));
    echo $pdfContent;
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
        <a class="btn-secondary" href="reports.php?batch_id=<?= (int)$selectedBatchId; ?>&date_from=<?= urlencode($dateFrom); ?>&date_to=<?= urlencode($dateTo); ?>&export=excel">Export Excel</a>
        <a class="btn-secondary" href="reports.php?batch_id=<?= (int)$selectedBatchId; ?>&date_from=<?= urlencode($dateFrom); ?>&date_to=<?= urlencode($dateTo); ?>&export=pdf">Export PDF</a>
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
        <p class="label">Expense Entries</p>
        <p class="value">ZMW <?= number_format($operationalExpenses, 2); ?></p>
    </article>
    <article class="card">
        <p class="label">Feed Expenses</p>
        <p class="value">ZMW <?= number_format($feedExpenses, 2); ?></p>
    </article>
    <article class="card">
        <p class="label">Chick Cost</p>
        <p class="value">ZMW <?= number_format($chickCost, 2); ?></p>
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
