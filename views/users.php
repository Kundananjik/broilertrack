<?php if (!empty($userFeedback)): ?>
<div class="alert <?= $userFeedback['success'] ? 'alert-success' : 'alert-error'; ?>">
    <?= htmlspecialchars($userFeedback['message'], ENT_QUOTES, 'UTF-8'); ?>
</div>
<?php endif; ?>

<section class="form-section">
    <h2><?= $editingUser ? 'Edit User' : 'Create User'; ?></h2>
    <form method="post" class="form-grid">
        <?= csrf_field(); ?>
        <input type="hidden" name="action" value="<?= $editingUser ? 'update' : 'create'; ?>">
        <?php if ($editingUser): ?>
            <input type="hidden" name="user_id" value="<?= (int)$editingUser['id']; ?>">
        <?php endif; ?>
        <label>Username
            <input type="text" name="username" value="<?= htmlspecialchars((string)($editingUser['username'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" required>
        </label>
        <label>Role
            <select name="role" required>
                <option value="">Select role</option>
                <option value="admin" <?= isset($editingUser) && (string)$editingUser['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                <option value="salesperson" <?= isset($editingUser) && (string)$editingUser['role'] === 'salesperson' ? 'selected' : ''; ?>>Sales Person</option>
            </select>
        </label>
        <label>Password
            <input type="password" name="password" minlength="8" <?= $editingUser ? '' : 'required'; ?>>
        </label>
        <label>Confirm Password
            <input type="password" name="confirm_password" minlength="8" <?= $editingUser ? '' : 'required'; ?>>
        </label>
        <button type="submit"><?= $editingUser ? 'Update User' : 'Create User'; ?></button>
        <?php if ($editingUser): ?>
            <a class="btn-secondary" href="users.php">Cancel edit</a>
        <?php endif; ?>
    </form>
</section>

<section class="table-section">
    <h2>Existing Users</h2>
    <div class="table-responsive">
        <table class="table table-striped table-bordered align-middle">
            <thead>
                <tr>
                    <th>Username</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($users as $user): ?>
                <tr>
                    <td><?= htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?= htmlspecialchars(ucfirst((string)$user['role']), ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?= (int)$user['is_active'] === 1 ? 'Active' : 'Inactive'; ?></td>
                    <td><?= htmlspecialchars((string)$user['created_at'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td class="action-cell">
                        <a class="action-link" href="users.php?edit_id=<?= (int)$user['id']; ?>">Edit</a>
                        <?php if ((int)$user['is_active'] === 1): ?>
                        <form method="post" onsubmit="return confirm('Deactivate this user?');">
                            <?= csrf_field(); ?>
                            <input type="hidden" name="action" value="deactivate">
                            <input type="hidden" name="user_id" value="<?= (int)$user['id']; ?>">
                            <button type="submit" class="btn-danger">Deactivate</button>
                        </form>
                        <?php else: ?>
                        <form method="post">
                            <?= csrf_field(); ?>
                            <input type="hidden" name="action" value="reactivate">
                            <input type="hidden" name="user_id" value="<?= (int)$user['id']; ?>">
                            <button type="submit" class="btn-secondary">Reactivate</button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
