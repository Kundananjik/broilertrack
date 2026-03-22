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

function audit_log(string $module, string $action, string $entityType, ?int $entityId = null, array $details = []): void
{
    try {
        $pdo = app_db();
        $userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
        $username = (string)($_SESSION['username'] ?? 'guest');
        $ipAddress = substr((string)($_SERVER['REMOTE_ADDR'] ?? 'unknown'), 0, 45);
        $userAgent = substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255);

        $stmt = $pdo->prepare(
            'INSERT INTO audit_logs (user_id, username, module, action, entity_type, entity_id, details_json, ip_address, user_agent)
             VALUES (:user_id, :username, :module, :action, :entity_type, :entity_id, :details_json, :ip_address, :user_agent)'
        );
        $stmt->execute([
            'user_id' => $userId,
            'username' => $username,
            'module' => $module,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'details_json' => $details ? json_encode($details, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
        ]);
    } catch (Throwable $e) {
        // Logging must not break business flow.
    }
}
