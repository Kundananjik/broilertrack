<?php if ($metrics['batch'] === null): ?>
<section class="empty-state">
    <p>No batches found. Please ask an administrator to create a batch before recording sales.</p>
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
        <p class="label">Birds Sold</p>
        <p class="value"><?= number_format((int)$metrics['birds_sold']); ?></p>
    </article>
    <article class="card">
        <p class="label">Total Revenue</p>
        <p class="value">ZMW <?= number_format((float)$metrics['total_revenue'], 2); ?></p>
    </article>
    <article class="card">
        <p class="label">Average Price per Bird</p>
        <p class="value">ZMW <?= number_format((float)$metrics['avg_price_per_bird'], 2); ?></p>
    </article>
    <article class="card">
        <p class="label">Current Alive Birds</p>
        <p class="value"><?= number_format((int)$metrics['current_alive']); ?></p>
    </article>
</section>

<section class="table-section">
    <div class="section-header">
        <h2>Recent Sales</h2>
        <a class="btn-secondary" href="sales.php?batch_id=<?= (int)$metrics['batch']['batch_id']; ?>">Open Sales Page</a>
    </div>
    <div class="table-responsive">
        <table class="table table-striped table-bordered align-middle">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Birds Sold</th>
                    <th>Price per bird</th>
                    <th>Total Revenue</th>
                    <th>Buyer</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($recentSales as $sale): ?>
                <tr>
                    <td><?= htmlspecialchars((string)$sale['date'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?= number_format((int)$sale['birds_sold']); ?></td>
                    <td>ZMW <?= number_format((float)$sale['price_per_bird'], 2); ?></td>
                    <td>ZMW <?= number_format((float)$sale['total_revenue'], 2); ?></td>
                    <td><?= htmlspecialchars((string)($sale['buyer'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php endif; ?>
