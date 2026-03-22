<?php if (!empty($expenseFeedback)): ?>
<div class="alert <?= $expenseFeedback['success'] ? 'alert-success' : 'alert-error'; ?>">
    <?= htmlspecialchars($expenseFeedback['message'], ENT_QUOTES, 'UTF-8'); ?>
</div>
<?php endif; ?>

<section class="form-section">
    <h2><?= $editingExpense ? 'Edit Expense' : 'Record Expense'; ?></h2>
    <form method="post" class="form-grid">
        <?= csrf_field(); ?>
        <input type="hidden" name="action" value="<?= $editingExpense ? 'update' : 'create'; ?>">
        <?php if ($editingExpense): ?>
            <input type="hidden" name="expense_id" value="<?= (int)$editingExpense['id']; ?>">
        <?php endif; ?>
        <label>Batch
            <select name="batch_id" required>
                <option value="">Select batch</option>
                <?php foreach ($batches as $batch): ?>
                    <?php $isSelected = $editingExpense ? ((int)$editingExpense['batch_id'] === (int)$batch['batch_id']) : ($selectedBatchId === (int)$batch['batch_id']); ?>
                    <option value="<?= (int)$batch['batch_id']; ?>" <?= $isSelected ? 'selected' : ''; ?>>
                        <?= htmlspecialchars($batch['batch_name'], ENT_QUOTES, 'UTF-8'); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Date
            <input type="date" name="date" value="<?= htmlspecialchars($editingExpense['date'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
        </label>
        <label>Category
            <input type="text" name="category" value="<?= htmlspecialchars($editingExpense['category'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
        </label>
        <label>Item Name
            <input type="text" name="item_name" value="<?= htmlspecialchars($editingExpense['item_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
        </label>
        <label>Quantity
            <input type="number" step="0.01" name="quantity" min="0.01" value="<?= htmlspecialchars((string)($editingExpense['quantity'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" required>
        </label>
        <label>Unit Cost
            <input type="number" step="0.01" name="unit_cost" min="0.01" value="<?= htmlspecialchars((string)($editingExpense['unit_cost'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" required>
        </label>
        <label>Supplier
            <input type="text" name="supplier" value="<?= htmlspecialchars($editingExpense['supplier'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
        </label>
        <label>Notes
            <textarea name="notes" rows="2"><?= htmlspecialchars($editingExpense['notes'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
        </label>
        <button type="submit"><?= $editingExpense ? 'Update Expense' : 'Save Expense'; ?></button>
        <?php if ($editingExpense): ?>
            <a class="btn-secondary" href="expenses.php?batch_id=<?= (int)$selectedBatchId; ?>">Cancel edit</a>
        <?php endif; ?>
    </form>
</section>

<section class="table-section">
    <div class="section-header">
        <h2>Expense History</h2>
        <form method="get" class="inline-form">
            <label for="batchFilter">Batch</label>
            <select name="batch_id" id="batchFilter" onchange="this.form.submit()">
                <?php foreach ($batches as $batch): ?>
                    <option value="<?= (int)$batch['batch_id']; ?>" <?= $selectedBatchId === (int)$batch['batch_id'] ? 'selected' : ''; ?>>
                        <?= htmlspecialchars($batch['batch_name'], ENT_QUOTES, 'UTF-8'); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>
    <p class="muted">Total expenses: ZMW <?= number_format($totalExpenseValue, 2); ?></p>
    <div class="table-responsive">
        <table class="table table-striped table-bordered align-middle">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Category</th>
                    <th>Item</th>
                    <th>Qty</th>
                    <th>Unit Cost</th>
                    <th>Total Cost</th>
                    <th>Supplier</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($expenses as $expense): ?>
                <tr>
                    <td><?= htmlspecialchars($expense['date'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?= htmlspecialchars($expense['category'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?= htmlspecialchars($expense['item_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?= number_format((float)$expense['quantity'], 2); ?></td>
                    <td>ZMW <?= number_format((float)$expense['unit_cost'], 2); ?></td>
                    <td>ZMW <?= number_format((float)$expense['total_cost'], 2); ?></td>
                    <td><?= htmlspecialchars($expense['supplier'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                    <td class="action-cell">
                        <a class="action-link" href="expenses.php?batch_id=<?= (int)$selectedBatchId; ?>&edit_id=<?= (int)$expense['id']; ?>">Edit</a>
                        <form method="post" onsubmit="return confirm('Delete this expense record?');">
                            <?= csrf_field(); ?>
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="expense_id" value="<?= (int)$expense['id']; ?>">
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

