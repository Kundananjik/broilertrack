<?php if (!empty($batchFeedback)): ?>
<div class="alert <?= $batchFeedback['success'] ? 'alert-success' : 'alert-error'; ?>">
    <?= htmlspecialchars($batchFeedback['message'], ENT_QUOTES, 'UTF-8'); ?>
</div>
<?php endif; ?>

<section class="form-section">
    <h2>New Batch</h2>
    <form method="post" class="form-grid">
        <?= csrf_field(); ?>
        <label>Batch Name
            <input type="text" name="batch_name" required>
        </label>
        <label>Breed
            <input type="text" name="breed" required>
        </label>
        <label>Start Date
            <input type="date" name="start_date" required>
        </label>
        <label>Expected Harvest Date
            <input type="date" name="expected_harvest_date" required>
        </label>
        <label>Initial Chicks
            <input type="number" name="initial_chicks" min="1" required>
        </label>
        <label>Chick Cost (per bird)
            <input type="number" name="chick_cost" min="0" step="0.01" required>
        </label>
        <label>Notes
            <textarea name="notes" rows="3"></textarea>
        </label>
        <button type="submit">Save Batch</button>
    </form>
</section>
