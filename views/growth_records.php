<?php if (!empty($growthFeedback)): ?>
<div class="alert <?= $growthFeedback['success'] ? 'alert-success' : 'alert-error'; ?>">
    <?= htmlspecialchars($growthFeedback['message'], ENT_QUOTES, 'UTF-8'); ?>
</div>
<?php endif; ?>

<section class="form-section">
    <h2><?= $editingGrowthRecord ? 'Edit Growth Sample' : 'Record Growth Sample'; ?></h2>
    <form method="post" class="form-grid">
        <?= csrf_field(); ?>
        <input type="hidden" name="action" value="<?= $editingGrowthRecord ? 'update' : 'create'; ?>">
        <?php if ($editingGrowthRecord): ?>
            <input type="hidden" name="record_id" value="<?= (int)$editingGrowthRecord['record_id']; ?>">
        <?php endif; ?>
        <label>Batch
            <select name="batch_id" required>
                <option value="">Select batch</option>
                <?php foreach ($batches as $batch): ?>
                    <?php $isSelected = $editingGrowthRecord ? ((int)$editingGrowthRecord['batch_id'] === (int)$batch['batch_id']) : ($selectedBatchId === (int)$batch['batch_id']); ?>
                    <option value="<?= (int)$batch['batch_id']; ?>" <?= $isSelected ? 'selected' : ''; ?>>
                        <?= htmlspecialchars($batch['batch_name'], ENT_QUOTES, 'UTF-8'); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Date
            <input type="date" name="date" value="<?= htmlspecialchars($editingGrowthRecord['date'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
        </label>
        <label>Average Weight (kg)
            <input type="number" step="0.01" name="average_weight_kg" min="0.01" value="<?= htmlspecialchars((string)($editingGrowthRecord['average_weight_kg'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" required>
        </label>
        <label>Birds Sampled
            <input type="number" name="birds_sampled" min="1" value="<?= htmlspecialchars((string)($editingGrowthRecord['birds_sampled'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" required>
        </label>
        <button type="submit"><?= $editingGrowthRecord ? 'Update Growth Data' : 'Save Growth Data'; ?></button>
        <?php if ($editingGrowthRecord): ?>
            <a href="growth_records.php?batch_id=<?= (int)$selectedBatchId; ?>">Cancel edit</a>
        <?php endif; ?>
    </form>
</section>

<section class="table-section">
    <div class="section-header">
        <h2>Growth History</h2>
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
    <div class="table-responsive">
        <table class="table table-striped table-bordered align-middle">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Average Weight (kg)</th>
                    <th>Birds Sampled</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($growthRecords as $record): ?>
                <tr>
                    <td><?= htmlspecialchars($record['date'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?= number_format((float)$record['average_weight_kg'], 2); ?></td>
                    <td><?= number_format((int)$record['birds_sampled']); ?></td>
                    <td>
                        <a href="growth_records.php?batch_id=<?= (int)$selectedBatchId; ?>&edit_id=<?= (int)$record['record_id']; ?>">Edit</a>
                        <form method="post" onsubmit="return confirm('Delete this growth record?');">
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


