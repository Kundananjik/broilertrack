<?php
declare(strict_types=1);

require_once __DIR__ . '/config/bootstrap.php';

app_start_session();
if (isset($_SESSION['user_id'])) {
    audit_log('auth', 'logout', 'user', (int)$_SESSION['user_id'], [
        'username' => (string)($_SESSION['username'] ?? ''),
    ]);
}
session_unset();
session_destroy();
header('Location: login.php');
exit;
