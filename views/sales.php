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
        <table class="table table-striped table-bordered align-middle sales-history-table">
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
                            <a
                                class="btn-primary action-icon-btn"
                                href="sales.php?batch_id=<?= (int)$selectedBatchId; ?>&edit_id=<?= (int)$sale['sale_id']; ?>"
                                aria-label="Edit sale"
                                title="Edit sale"
                            >
                                <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                                    <path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zm2.92 2.33H5v-.92l9.06-9.06.92.92L5.92 19.58zM20.71 7.04c.39-.39.39-1.02 0-1.41L18.37 3.3a.9959.9959 0 0 0-1.41 0L15.13 5.13l3.75 3.75 1.83-1.84z"/>
                                </svg>
                            </a>
                            <form method="post" class="inline-form" onsubmit="return confirm('Delete this sale record?');">
                                <?= csrf_field(); ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="sale_id" value="<?= (int)$sale['sale_id']; ?>">
                                <input type="hidden" name="batch_id" value="<?= (int)$selectedBatchId; ?>">
                                <button type="submit" class="btn-danger action-icon-btn" aria-label="Delete sale" title="Delete sale">
                                    <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                                        <path d="M6 7h12v14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2V7zm3 3v9h2v-9H9zm4 0v9h2v-9h-2zM9 2h6l1 2h5v2H3V4h5l1-2z"/>
                                    </svg>
                                </button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>


