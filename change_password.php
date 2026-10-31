<?php
declare(strict_types=1);

require_once __DIR__ . '/config/bootstrap.php';
require_once __DIR__ . '/controllers/UserController.php';

require_auth(true);
if (empty($_SESSION['must_change_password'])) {
    header('Location: dashboard.php');
    exit;
}

$feedback = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $feedback = ['success' => false, 'message' => 'Invalid form submission. Please try again.'];
    } else {
        $controller = new UserController(app_db());
        $feedback = $controller->changeOwnPassword($_POST, (int)$_SESSION['user_id']);
        if ($feedback['success']) {
            session_regenerate_id(true);
            unset($_SESSION['csrf_token']);
            unset($_SESSION['must_change_password']);
            header('Location: dashboard.php');
            exit;
        }
    }
}

$pageTitle = 'Change Password';
include __DIR__ . '/views/layout_header.php';
?>
<?php if (!empty($feedback)): ?>
<div class="alert <?= $feedback['success'] ? 'alert-success' : 'alert-error'; ?>">
    <?= htmlspecialchars($feedback['message'], ENT_QUOTES, 'UTF-8'); ?>
</div>
<?php endif; ?>

<section class="form-section">
    <h2>Update your password to continue</h2>
    <p class="muted">Your account is using the initial password. Choose a new password before accessing the application.</p>
    <form method="post" class="form-grid">
        <?= csrf_field(); ?>
        <label>New password
            <input type="password" name="password" minlength="12" autocomplete="new-password" required>
        </label>
        <label>Confirm new password
            <input type="password" name="confirm_password" minlength="12" autocomplete="new-password" required>
        </label>
        <p class="muted form-help">Use at least 12 characters with uppercase and lowercase letters, a number, and a symbol, or a passphrase of at least 4 words and 20 characters.</p>
        <button type="submit">Change Password</button>
    </form>
</section>
<?php include __DIR__ . '/views/layout_footer.php'; ?>
