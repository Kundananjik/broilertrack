<?php if (!empty($saleFeedback)): ?>
<div class="alert <?= $saleFeedback['success'] ? 'alert-success' : 'alert-error'; ?>">
    <?= htmlspecialchars($saleFeedback['message'], ENT_QUOTES, 'UTF-8'); ?>
</div>
<?php endif; ?>

<section class="form-section">
    <h2><?= $editingSale ? 'Edit Sale' : 'Record Sale'; ?></h2>
    <form method="post" class="form-grid">
        <?= csrf_field(); ?>
        <input type="hidden" name="action" value="<?= $editingSale ? 'update' : 'create'; ?>">
        <?php if ($editingSale): ?>
            <input type="hidden" name="sale_id" value="<?= (int)$editingSale['sale_id']; ?>">
        <?php endif; ?>
        <label>Batch
            <select name="batch_id" required>
                <option value="">Select batch</option>
                <?php foreach ($batches as $batch): ?>
                    <?php $isSelected = $editingSale ? ((int)$editingSale['batch_id'] === (int)$batch['batch_id']) : ($selectedBatchId === (int)$batch['batch_id']); ?>
                    <option value="<?= (int)$batch['batch_id']; ?>" <?= $isSelected ? 'selected' : ''; ?>>
                        <?= htmlspecialchars($batch['batch_name'], ENT_QUOTES, 'UTF-8'); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Date
            <input type="date" name="date" value="<?= htmlspecialchars($editingSale['date'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
        </label>
        <label>Birds Sold
            <input type="number" name="birds_sold" min="1" value="<?= htmlspecialchars((string)($editingSale['birds_sold'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" required>
        </label>
        <label>Average Weight (kg)
            <input type="number" step="0.01" name="average_weight_kg" min="0.01" value="<?= htmlspecialchars((string)($editingSale['average_weight_kg'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" required>
        </label>
        <label>Price per bird
            <input type="number" step="0.01" name="price_per_bird" min="0.01" value="<?= htmlspecialchars((string)($editingSale['price_per_bird'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" required>
        </label>
        <label>Buyer
            <input type="text" name="buyer" value="<?= htmlspecialchars($editingSale['buyer'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
        </label>
        <button type="submit"><?= $editingSale ? 'Update Sale' : 'Save Sale'; ?></button>
        <?php if ($editingSale): ?>
            <a href="sales.php?batch_id=<?= (int)$selectedBatchId; ?>">Cancel edit</a>
        <?php endif; ?>
    </form>
</section>

<section class="table-section">
    <div class="section-header">
        <h2>Sales History</h2>
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
                    <th>Birds Sold</th>
                    <th>Average Weight</th>
                    <th>Total Weight</th>
                    <th>Price per bird</th>
                    <th>Total Revenue</th>
                    <th>Buyer</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($sales as $sale): ?>
                <tr>
                    <td><?= htmlspecialchars($sale['date'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?= number_format((int)$sale['birds_sold']); ?></td>
                    <td><?= number_format((float)$sale['average_weight_kg'], 2); ?> kg</td>
                    <td><?= number_format((float)$sale['total_weight'], 2); ?> kg</td>
                    <td>ZMW <?= number_format((float)$sale['price_per_bird'], 2); ?></td>
                    <td>ZMW <?= number_format((float)$sale['total_revenue'], 2); ?></td>
                    <td><?= htmlspecialchars($sale['buyer'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                    <td>
                        <a href="sales.php?batch_id=<?= (int)$selectedBatchId; ?>&edit_id=<?= (int)$sale['sale_id']; ?>">Edit</a>
                        <form method="post" onsubmit="return confirm('Delete this sale record?');">
                            <?= csrf_field(); ?>
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="sale_id" value="<?= (int)$sale['sale_id']; ?>">
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


