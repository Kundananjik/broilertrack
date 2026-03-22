<?php if ($metrics['batch'] === null): ?>
<section class="empty-state">
    <p>No batches found. <a href="add_batch.php">Create your first batch</a> to begin tracking.</p>
</section>
<?php else: ?>
<section class="filters">
    <form method="get" action="dashboard.php" class="inline-form">
        <label for="batch_id">Batch:</label>
        <select name="batch_id" id="batch_id">
            <?php foreach ($batches as $batch): ?>
                <option value="<?= (int)$batch['batch_id']; ?>" <?= isset($selectedBatchId) && (int)$selectedBatchId === (int)$batch['batch_id'] ? 'selected' : ''; ?>>
                    <?= htmlspecialchars($batch['batch_name'], ENT_QUOTES, 'UTF-8'); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <button type="submit">Apply</button>
    </form>
</section>

<section class="card-grid">
    <article class="card">
        <p class="label">Active Batch</p>
        <p class="value"><?= htmlspecialchars($metrics['batch']['batch_name'], ENT_QUOTES, 'UTF-8'); ?></p>
        <p class="muted"><?= htmlspecialchars($metrics['batch']['breed'], ENT_QUOTES, 'UTF-8'); ?></p>
    </article>
    <article class="card">
        <p class="label">Initial Chicks</p>
        <p class="value"><?= number_format($metrics['initial_chicks']); ?></p>
    </article>
    <article class="card">
        <p class="label">Current Alive Birds</p>
        <p class="value"><?= number_format($metrics['current_alive']); ?></p>
    </article>
    <article class="card">
        <p class="label">Mortality Rate</p>
        <p class="value"><?= number_format($metrics['mortality_rate'], 2); ?>%</p>
    </article>
    <article class="card">
        <p class="label">Total Feed Used (kg)</p>
        <p class="value"><?= number_format($metrics['total_feed_used'], 2); ?></p>
    </article>
    <article class="card">
        <p class="label">Average Bird Weight (kg)</p>
        <p class="value"><?= $metrics['average_weight'] !== null ? number_format($metrics['average_weight'], 2) : 'N/A'; ?></p>
    </article>
    <article class="card">
        <p class="label">Total Production Weight (kg)</p>
        <p class="value"><?= number_format($metrics['total_production_weight'], 2); ?></p>
    </article>
    <article class="card">
        <p class="label">Total Expenses</p>
        <p class="value">ZMW <?= number_format($metrics['total_expenses'], 2); ?></p>
    </article>
    <article class="card">
        <p class="label">Total Revenue</p>
        <p class="value">ZMW <?= number_format($metrics['total_revenue'], 2); ?></p>
    </article>
    <article class="card">
        <p class="label">Net Profit</p>
        <p class="value">ZMW <?= number_format($metrics['net_profit'], 2); ?></p>
    </article>
    <article class="card">
        <p class="label">Feed Conversion Ratio</p>
        <p class="value"><?= number_format($metrics['feed_conversion_ratio'], 2); ?></p>
    </article>
    <article class="card">
        <p class="label">Price per Bird</p>
        <p class="value">ZMW <?= number_format($metrics['cost_per_bird'], 2); ?></p>
    </article>
    <article class="card">
        <p class="label">Sales Rate</p>
        <p class="value"><?= number_format($metrics['sales_rate'], 2); ?>%</p>
    </article>
    <article class="card">
        <p class="label">Profit Rate</p>
        <p class="value"><?= number_format($metrics['profit_rate'], 2); ?>%</p>
    </article>
    <article class="card">
        <p class="label">Growth Rate</p>
        <p class="value"><?= number_format($metrics['growth_rate'], 2); ?>%</p>
    </article>
</section>

<?php
$collectionRate = (float)$collectionSummary['revenue_total'] > 0
    ? ((float)$collectionSummary['paid_total'] / (float)$collectionSummary['revenue_total']) * 100
    : 100.0;
?>
<section class="table-section">
    <div class="section-header">
        <h2>Collection Overview</h2>
    </div>
    <p class="muted">
        Paid: ZMW <?= number_format((float)$collectionSummary['paid_total'], 2); ?> |
        Outstanding: ZMW <?= number_format((float)$collectionSummary['balance_total'], 2); ?> |
        Collection Rate: <?= number_format($collectionRate, 2); ?>%
    </p>
    <?php if (!empty($overdueBalances)): ?>
        <div class="alert alert-error">There are <?= count($overdueBalances); ?> overdue balance record(s) older than 7 days.</div>
    <?php elseif ((float)$collectionSummary['balance_total'] > 0): ?>
        <div class="alert alert-error">There are unsettled sales balances that need follow-up.</div>
    <?php else: ?>
        <div class="alert alert-success">All recorded sales are fully settled.</div>
    <?php endif; ?>
    <?php if ($collectionRate < 65.0): ?>
        <div class="alert alert-error">Collection performance is low for this batch (below 65%).</div>
    <?php endif; ?>
</section>

<?php if (!empty($overdueBalances)): ?>
<section class="table-section">
    <h2>Overdue Balances</h2>
    <div class="table-responsive">
        <table class="table table-striped table-bordered align-middle">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Buyer</th>
                    <th>Revenue</th>
                    <th>Paid</th>
                    <th>Balance</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($overdueBalances as $balanceItem): ?>
                <tr>
                    <td><?= htmlspecialchars((string)$balanceItem['date'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?= htmlspecialchars((string)($balanceItem['buyer'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                    <td>ZMW <?= number_format((float)$balanceItem['total_revenue'], 2); ?></td>
                    <td>ZMW <?= number_format((float)$balanceItem['paid_amount'], 2); ?></td>
                    <td>ZMW <?= number_format((float)$balanceItem['balance_amount'], 2); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php endif; ?>

<section class="notes">
    <h2>Batch Notes</h2>
    <p><?= $metrics['batch']['notes'] ? nl2br(htmlspecialchars($metrics['batch']['notes'], ENT_QUOTES, 'UTF-8')) : 'No notes recorded.'; ?></p>
</section>
<?php endif; ?>
