<?php if (!empty($batchFeedback)): ?>
<div class="alert <?= $batchFeedback['success'] ? 'alert-success' : 'alert-error'; ?>">
    <?= htmlspecialchars($batchFeedback['message'], ENT_QUOTES, 'UTF-8'); ?>
</div>
<?php endif; ?>

<section class="table-section">
    <h2>Batch Overview</h2>
    <div class="table-responsive">
        <table class="table table-striped table-bordered align-middle">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Breed</th>
                    <th>Start</th>
                    <th>Harvest</th>
                    <th>Initial</th>
                    <th>Alive</th>
                    <th>Mortality</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($batches as $batch): ?>
                <tr>
                    <td><?= htmlspecialchars($batch['batch_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?= htmlspecialchars($batch['breed'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?= htmlspecialchars($batch['start_date'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?= htmlspecialchars($batch['expected_harvest_date'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?= number_format((int)$batch['initial_chicks']); ?></td>
                    <td><?= number_format((int)$batch['current_alive']); ?></td>
                    <td><?= number_format((int)$batch['mortality_count']); ?></td>
                    <td class="action-cell">
                        <a class="action-link" href="batches.php?edit_id=<?= (int)$batch['batch_id']; ?>">Edit</a>
                        <form method="post" onsubmit="return confirm('Delete this batch and all related records?');">
                            <?= csrf_field(); ?>
                            <input type="hidden" name="action" value="delete_batch">
                            <input type="hidden" name="batch_id" value="<?= (int)$batch['batch_id']; ?>">
                            <button type="submit" class="btn-danger">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<?php if ($editingBatch): ?>
<section class="form-section">
    <h2>Edit Batch Details</h2>
    <form method="post" class="form-grid">
        <?= csrf_field(); ?>
        <input type="hidden" name="action" value="update_batch">
        <input type="hidden" name="batch_id" value="<?= (int)$editingBatch['batch_id']; ?>">
        <label>Batch Name
            <input type="text" name="batch_name" value="<?= htmlspecialchars($editingBatch['batch_name'], ENT_QUOTES, 'UTF-8'); ?>" required>
        </label>
        <label>Breed
            <input type="text" name="breed" value="<?= htmlspecialchars($editingBatch['breed'], ENT_QUOTES, 'UTF-8'); ?>" required>
        </label>
        <label>Start Date
            <input type="date" name="start_date" value="<?= htmlspecialchars($editingBatch['start_date'], ENT_QUOTES, 'UTF-8'); ?>" required>
        </label>
        <label>Expected Harvest Date
            <input type="date" name="expected_harvest_date" value="<?= htmlspecialchars($editingBatch['expected_harvest_date'], ENT_QUOTES, 'UTF-8'); ?>" required>
        </label>
        <label>Initial Chicks
            <input type="number" name="initial_chicks" min="1" value="<?= (int)$editingBatch['initial_chicks']; ?>" required>
        </label>
        <label>Chick Cost (per bird)
            <input type="number" name="chick_cost" min="0.01" step="0.01" value="<?= htmlspecialchars((string)$editingBatch['chick_cost'], ENT_QUOTES, 'UTF-8'); ?>" required>
        </label>
        <label>Current Alive Birds
            <input type="number" name="current_alive" min="0" value="<?= (int)$editingBatch['current_alive']; ?>" required>
        </label>
        <label>Mortality Count
            <input type="number" name="mortality_count" min="0" value="<?= (int)$editingBatch['mortality_count']; ?>" required>
        </label>
        <label>Notes
            <textarea name="notes" rows="3"><?= htmlspecialchars($editingBatch['notes'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
        </label>
        <button type="submit">Update Batch</button>
        <a class="btn-secondary" href="batches.php">Cancel edit</a>
    </form>
</section>
<?php endif; ?>

<section class="form-section">
    <h2>Update Mortality & Alive Birds</h2>
    <form method="post" class="form-grid">
        <?= csrf_field(); ?>
        <input type="hidden" name="action" value="update_status">
        <label>Batch
            <select name="batch_id" required>
                <option value="">Select batch</option>
                <?php foreach ($batches as $batch): ?>
                    <option value="<?= (int)$batch['batch_id']; ?>" <?= ($editingBatch && (int)$editingBatch['batch_id'] === (int)$batch['batch_id']) ? 'selected' : ''; ?>>
                        <?= htmlspecialchars($batch['batch_name'], ENT_QUOTES, 'UTF-8'); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Current Alive Birds
            <input type="number" name="current_alive" min="0" required>
        </label>
        <label>Mortality Count
            <input type="number" name="mortality_count" min="0" required>
        </label>
        <button type="submit">Update</button>
    </form>
</section>


