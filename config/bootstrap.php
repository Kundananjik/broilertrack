<?php
declare(strict_types=1);

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/csrf.php';

function app_start_session(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
}

function require_auth(): void
{
    app_start_session();
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }
}

function require_guest(): void
{
    app_start_session();
    if (isset($_SESSION['user_id'])) {
        header('Location: dashboard.php');
        exit;
    }
}

function current_user_role(): string
{
    app_start_session();
    return (string)($_SESSION['role'] ?? '');
}

function require_roles(array $allowedRoles): void
{
    require_auth();
    $role = current_user_role();
    if (!in_array($role, $allowedRoles, true)) {
        http_response_code(403);
        exit('Forbidden');
    }
}

function require_admin(): void
{
    require_roles(['admin']);
}

function app_db(): PDO
{
    static $pdo = null;
    if (!$pdo instanceof PDO) {
        $database = new Database();
        $pdo = $database->getConnection();
    }

    return $pdo;
}
