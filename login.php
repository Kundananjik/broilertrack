<?php
declare(strict_types=1);

require_once __DIR__ . '/config/bootstrap.php';

require_guest();

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $error = 'Invalid form submission. Please try again.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = (string)($_POST['password'] ?? '');
        $ipAddress = substr((string)($_SERVER['REMOTE_ADDR'] ?? 'unknown'), 0, 45);
        $maxAttempts = 5;
        $windowMinutes = 15;
        $lockoutMinutes = 15;

        if ($username === '' || $password === '') {
            $error = 'Username and password are required.';
        } else {
            $pdo = app_db();

            $attemptStmt = $pdo->prepare('SELECT attempt_count, last_attempt, locked_until FROM login_attempts WHERE username = :username AND ip_address = :ip_address');
            $attemptStmt->execute(['username' => $username, 'ip_address' => $ipAddress]);
            $attempt = $attemptStmt->fetch();

            if ($attempt) {
                $lockedUntil = $attempt['locked_until'] ?? null;
                if ($lockedUntil !== null && strtotime((string)$lockedUntil) > time()) {
                    $error = 'Too many failed login attempts. Try again later.';
                } else {
                    $lastAttemptTs = isset($attempt['last_attempt']) ? strtotime((string)$attempt['last_attempt']) : false;
                    if ($lastAttemptTs !== false && $lastAttemptTs < strtotime("-{$windowMinutes} minutes")) {
                        $resetStmt = $pdo->prepare('UPDATE login_attempts SET attempt_count = 0, locked_until = NULL WHERE username = :username AND ip_address = :ip_address');
                        $resetStmt->execute(['username' => $username, 'ip_address' => $ipAddress]);
                        $attempt['attempt_count'] = 0;
                    }
                }
            }

            if ($error === '') {
            $stmt = $pdo->prepare('SELECT id, username, password_hash, role, is_active FROM users WHERE username = :username');
            $stmt->execute(['username' => $username]);
            $user = $stmt->fetch();

            if ($user && (int)$user['is_active'] === 1 && password_verify($password, $user['password_hash'])) {
                $clearAttempts = $pdo->prepare('DELETE FROM login_attempts WHERE username = :username AND ip_address = :ip_address');
                $clearAttempts->execute(['username' => $username, 'ip_address' => $ipAddress]);

                session_regenerate_id(true);
                $_SESSION['user_id'] = (int)$user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                audit_log('auth', 'login_success', 'user', (int)$user['id'], [
                    'username' => (string)$user['username'],
                    'role' => (string)$user['role'],
                ]);
                header('Location: dashboard.php');
                exit;
            } else {
                if ($user && (int)$user['is_active'] !== 1) {
                    $error = 'Account is inactive. Contact an administrator.';
                } else {
                    $error = 'Invalid credentials.';
                }

                $currentAttempts = isset($attempt['attempt_count']) ? (int)$attempt['attempt_count'] : 0;
                $newAttempts = $currentAttempts + 1;
                $lockedUntil = $newAttempts >= $maxAttempts
                    ? date('Y-m-d H:i:s', strtotime("+{$lockoutMinutes} minutes"))
                    : null;

                $upsert = $pdo->prepare(
                    'INSERT INTO login_attempts (username, ip_address, attempt_count, last_attempt, locked_until)
                     VALUES (:username, :ip_address, :attempt_count, NOW(), :locked_until)
                     ON DUPLICATE KEY UPDATE
                        attempt_count = VALUES(attempt_count),
                        last_attempt = NOW(),
                        locked_until = VALUES(locked_until)'
                );
                $upsert->execute([
                    'username' => $username,
                    'ip_address' => $ipAddress,
                    'attempt_count' => $newAttempts,
                    'locked_until' => $lockedUntil,
                ]);

                audit_log('auth', 'login_failed', 'user', $user ? (int)$user['id'] : null, [
                    'username' => $username,
                    'reason' => $error,
                    'attempt_count' => $newAttempts,
                ]);
            }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - BroilerTrack</title>
    <meta name="robots" content="noindex,nofollow,noarchive">
    <link rel="canonical" href="https://broilertrack.42web.io/login.php">
    <link rel="icon" type="image/png" href="assets/img/logo.png">
    <link rel="apple-touch-icon" href="assets/img/logo.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Sora:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="auth-page d-flex flex-column min-vh-100">
    <div class="auth-glow auth-glow-a" aria-hidden="true"></div>
    <div class="auth-glow auth-glow-b" aria-hidden="true"></div>

    <header class="auth-header container">
        <a href="index.php" class="auth-brand">
            <img src="assets/img/logo.png" alt="BroilerTrack logo" width="42" height="42">
            <span>BroilerTrack</span>
        </a>
        <a href="index.php" class="auth-top-link">Home</a>
    </header>

    <div class="auth-wrapper container flex-grow-1">
        <div class="auth-card">
            <h1>Sign In</h1>
            <p class="auth-subtitle">Access your sales and operations dashboard.</p>
            <?php if ($error !== ''): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>
            <form method="post" class="auth-form">
                <?= csrf_field(); ?>
                <label>Username
                    <input type="text" name="username" required autofocus>
                </label>
                <label>Password
                    <input type="password" name="password" required>
                </label>
                <button type="submit" class="auth-submit">Login</button>
            </form>
        </div>
    </div>
    <?php require __DIR__ . '/views/footer_content.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>
