<?php if (!empty($feedFeedback)): ?>
<div class="alert <?= $feedFeedback['success'] ? 'alert-success' : 'alert-error'; ?>">
    <?= htmlspecialchars($feedFeedback['message'], ENT_QUOTES, 'UTF-8'); ?>
</div>
<?php endif; ?>

<section class="form-section">
    <h2><?= $editingFeedRecord ? 'Edit Feed Record' : 'Log Feed Usage'; ?></h2>
    <form method="post" class="form-grid">
        <?= csrf_field(); ?>
        <input type="hidden" name="action" value="<?= $editingFeedRecord ? 'update' : 'create'; ?>">
        <?php if ($editingFeedRecord): ?>
            <input type="hidden" name="record_id" value="<?= (int)$editingFeedRecord['record_id']; ?>">
        <?php endif; ?>
        <label>Batch
            <select name="batch_id" required>
                <option value="">Select batch</option>
                <?php foreach ($batches as $batch): ?>
                    <?php $isSelected = $editingFeedRecord ? ((int)$editingFeedRecord['batch_id'] === (int)$batch['batch_id']) : ($selectedBatchId === (int)$batch['batch_id']); ?>
                    <option value="<?= (int)$batch['batch_id']; ?>" <?= $isSelected ? 'selected' : ''; ?>>
                        <?= htmlspecialchars($batch['batch_name'], ENT_QUOTES, 'UTF-8'); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Date
            <input type="date" name="date" value="<?= htmlspecialchars($editingFeedRecord['date'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
        </label>
        <label>Feed Type
            <input type="text" name="feed_type" value="<?= htmlspecialchars($editingFeedRecord['feed_type'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
        </label>
        <label>Feed (kg)
            <input type="number" step="0.01" name="feed_kg" min="0.01" value="<?= htmlspecialchars((string)($editingFeedRecord['feed_kg'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" required>
        </label>
        <label>Cost per kg
            <input type="number" step="0.01" name="cost_per_kg" min="0.01" value="<?= htmlspecialchars((string)($editingFeedRecord['cost_per_kg'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" required>
        </label>
        <button type="submit"><?= $editingFeedRecord ? 'Update Feed Record' : 'Save Feed Record'; ?></button>
        <?php if ($editingFeedRecord): ?>
            <a href="feed_usage.php?batch_id=<?= (int)$selectedBatchId; ?>">Cancel edit</a>
        <?php endif; ?>
    </form>
</section>

<section class="table-section">
    <div class="section-header">
        <h2>Feed History</h2>
        <form method="get" class="inline-form">
            <label>Batch
                <select name="batch_id" onchange="this.form.submit()">
                    <?php foreach ($batches as $batch): ?>
                        <option value="<?= (int)$batch['batch_id']; ?>" <?= $selectedBatchId === (int)$batch['batch_id'] ? 'selected' : ''; ?>>
                            <?= htmlspecialchars($batch['batch_name'], ENT_QUOTES, 'UTF-8'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
        </form>
    </div>
    <p class="muted">Total feed used: <?= number_format($totalFeedKg, 2); ?> kg (Cost ZMW <?= number_format($totalFeedCost, 2); ?>)</p>
    <div class="table-responsive">
        <table class="table table-striped table-bordered align-middle">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Feed Type</th>
                    <th>Quantity (kg)</th>
                    <th>Cost per kg</th>
                    <th>Total Cost</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($feedUsage as $record): ?>
                <tr>
                    <td><?= htmlspecialchars($record['date'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?= htmlspecialchars($record['feed_type'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?= number_format((float)$record['feed_kg'], 2); ?></td>
                    <td>ZMW <?= number_format((float)$record['cost_per_kg'], 2); ?></td>
                    <td>ZMW <?= number_format((float)$record['total_cost'], 2); ?></td>
                    <td class="action-cell">
                        <a class="action-link" href="feed_usage.php?batch_id=<?= (int)$selectedBatchId; ?>&edit_id=<?= (int)$record['record_id']; ?>">Edit</a>
                        <form method="post" onsubmit="return confirm('Delete this feed record?');">
                            <?= csrf_field(); ?>
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="record_id" value="<?= (int)$record['record_id']; ?>">
                            <input type="hidden" name="batch_id" value="<?= (int)$selectedBatchId; ?>">
                            <button type="submit" class="btn-danger">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>


