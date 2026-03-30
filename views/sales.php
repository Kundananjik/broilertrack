<?php if (!empty($saleFeedback)): ?>
<div class="alert <?= $saleFeedback['success'] ? 'alert-success' : 'alert-error'; ?>">
    <?= htmlspecialchars($saleFeedback['message'], ENT_QUOTES, 'UTF-8'); ?>
</div>
<?php endif; ?>
<?php $isAdmin = (($_SESSION['role'] ?? '') === 'admin'); ?>

<section class="form-section">
    <h2><?= $editingSale ? 'Edit Sale Details' : 'Record Sale'; ?></h2>
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
        <label>Price per bird
            <input type="number" step="0.01" name="price_per_bird" min="0.01" value="<?= htmlspecialchars((string)($editingSale['price_per_bird'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" required>
        </label>
        <label>Buyer
            <input type="text" name="buyer" value="<?= htmlspecialchars($editingSale['buyer'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
        </label>
        <button type="submit"><?= $editingSale ? 'Update Sale Details' : 'Save Sale'; ?></button>
        <?php if ($editingSale): ?>
            <a href="sales.php?batch_id=<?= (int)$selectedBatchId; ?>">Cancel edit</a>
        <?php endif; ?>
    </form>
    <p class="muted">Paid and balance are updated through payment entries in the table below.</p>
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
                    <th>Price per bird</th>
                    <th>Total Revenue</th>
                    <th>Paid</th>
                    <th>Balance</th>
                    <th>Buyer</th>
                    <th>Record Payment</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($sales as $sale): ?>
                <tr>
                    <td><?= htmlspecialchars($sale['date'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?= number_format((int)$sale['birds_sold']); ?></td>
                    <td>ZMW <?= number_format((float)$sale['price_per_bird'], 2); ?></td>
                    <td>ZMW <?= number_format((float)$sale['total_revenue'], 2); ?></td>
                    <td>ZMW <?= number_format((float)$sale['paid_amount'], 2); ?></td>
                    <td>ZMW <?= number_format((float)$sale['balance_amount'], 2); ?></td>
                    <td><?= htmlspecialchars($sale['buyer'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                    <td>
                        <form method="post" class="inline-form" onsubmit="const btn=this.querySelector('button[type=&quot;submit&quot;]'); if(btn){btn.disabled=true; btn.textContent='Posting...';}">
                            <?= csrf_field(); ?>
                            <input type="hidden" name="action" value="add_payment">
                            <input type="hidden" name="sale_id" value="<?= (int)$sale['sale_id']; ?>">
                            <input type="hidden" name="batch_id" value="<?= (int)$selectedBatchId; ?>">
                            <input type="date" name="payment_date" value="<?= htmlspecialchars(date('Y-m-d'), ENT_QUOTES, 'UTF-8'); ?>" required>
                            <input type="number" step="0.01" min="0.01" max="<?= htmlspecialchars(number_format((float)$sale['balance_amount'], 2, '.', ''), ENT_QUOTES, 'UTF-8'); ?>" name="payment_amount" placeholder="Amount" required>
                            <input type="text" name="payment_notes" maxlength="255" placeholder="Notes (optional)">
                            <button type="submit" <?= (float)$sale['balance_amount'] <= 0 ? 'disabled' : ''; ?>>
                                <?= (float)$sale['balance_amount'] <= 0 ? 'Paid in Full' : 'Post Payment'; ?>
                            </button>
                        </form>
                    </td>
                    <td class="action-cell">
                        <?php if ($isAdmin): ?>
                            <a class="action-link" href="sales.php?batch_id=<?= (int)$selectedBatchId; ?>&edit_id=<?= (int)$sale['sale_id']; ?>">Edit</a>
                            <form method="post" onsubmit="return confirm('Delete this sale record?');">
                                <?= csrf_field(); ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="sale_id" value="<?= (int)$sale['sale_id']; ?>">
                                <input type="hidden" name="batch_id" value="<?= (int)$selectedBatchId; ?>">
                                <button type="submit" class="btn-danger">Delete</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>


