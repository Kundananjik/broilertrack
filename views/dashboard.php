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
        <p class="label">Cost per Bird</p>
        <p class="value">ZMW <?= number_format($metrics['cost_per_bird'], 2); ?></p>
    </article>
    <article class="card">
        <p class="label">Cost per Kilogram</p>
        <p class="value">ZMW <?= number_format($metrics['cost_per_kg'], 2); ?></p>
    </article>
</section>

<section class="notes">
    <h2>Batch Notes</h2>
    <p><?= $metrics['batch']['notes'] ? nl2br(htmlspecialchars($metrics['batch']['notes'], ENT_QUOTES, 'UTF-8')) : 'No notes recorded.'; ?></p>
</section>
<?php endif; ?>
