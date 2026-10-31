<?php
$maxTrendRevenue = max(array_column($salesTrend, 'revenue') ?: [0.0]);
?>
<section class="table-section sales-trend" aria-labelledby="sales-trend-title">
    <h2 id="sales-trend-title">Sales Trend: Last 6 Months</h2>
    <p class="muted">Revenue and birds sold by month for this batch.</p>
    <div class="trend-chart">
        <?php foreach ($salesTrend as $month): ?>
            <?php $barWidth = $maxTrendRevenue > 0 ? ((float)$month['revenue'] / $maxTrendRevenue) * 100 : 0; ?>
            <div class="trend-row">
                <span class="trend-month"><?= htmlspecialchars($month['label'], ENT_QUOTES, 'UTF-8'); ?></span>
                <div class="trend-track" role="img" aria-label="<?= htmlspecialchars($month['label'] . ': ZMW ' . number_format((float)$month['revenue'], 2) . ' revenue, ' . number_format((int)$month['birds_sold']) . ' birds sold', ENT_QUOTES, 'UTF-8'); ?>">
                    <span class="trend-bar" style="width: <?= number_format($barWidth, 2, '.', ''); ?>%"></span>
                </div>
                <span class="trend-total">ZMW <?= number_format((float)$month['revenue'], 2); ?></span>
                <span class="trend-birds"><?= number_format((int)$month['birds_sold']); ?> birds</span>
            </div>
        <?php endforeach; ?>
    </div>
</section>
