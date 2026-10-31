<?php
declare(strict_types=1);

date_default_timezone_set('Africa/Lusaka');

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/csrf.php';

function app_start_session(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        ini_set('session.use_strict_mode', '1');
        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || ((int)($_SERVER['SERVER_PORT'] ?? 0) === 443)
            || (isset($_SERVER['REQUEST_SCHEME']) && strtolower($_SERVER['REQUEST_SCHEME']) === 'https');

        $forwardedProto = strtolower($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '');
        $forwardedSsl = strtolower($_SERVER['HTTP_X_FORWARDED_SSL'] ?? '');
        $forwardedScheme = strtolower($_SERVER['HTTP_X_FORWARDED_SCHEME'] ?? '');
        $frontEndHttps = strtolower($_SERVER['HTTP_FRONT_END_HTTPS'] ?? '');
        $cfVisitor = $_SERVER['HTTP_CF_VISITOR'] ?? '';
        $cfIsHttps = false;
        if ($cfVisitor !== '') {
            $cfData = json_decode($cfVisitor, true);
            if (is_array($cfData) && isset($cfData['scheme'])) {
                $cfIsHttps = strtolower((string)$cfData['scheme']) === 'https';
            }
        }

        $isHttps = $isHttps
            || $forwardedProto === 'https'
            || $forwardedSsl === 'on'
            || $forwardedScheme === 'https'
            || $frontEndHttps === 'on'
            || $cfIsHttps;

        $cookieSecure = getenv('BROILERTRACK_COOKIE_SECURE');
        if ($cookieSecure !== false) {
            $cookieSecure = filter_var($cookieSecure, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($cookieSecure !== null) {
                $isHttps = $cookieSecure;
            }
        }

        session_start([
            'cookie_httponly' => true,
            'cookie_samesite' => 'Lax',
            'cookie_secure' => $isHttps,
            'cookie_path' => '/',
        ]);
    }
}

function require_auth(bool $allowPasswordChange = false): void
{
    app_start_session();
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }

    $user = refresh_authenticated_user(app_db(), (int)$_SESSION['user_id']);
    if ($user === null) {
        destroy_app_session();
        header('Location: login.php');
        exit;
    }

    $mustChangePassword = (int)$user['must_change_password'] === 1;
    if ($mustChangePassword) {
        $_SESSION['must_change_password'] = true;
        if (!$allowPasswordChange) {
            header('Location: change_password.php');
            exit;
        }
    } else {
        unset($_SESSION['must_change_password']);
    }
}

function refresh_authenticated_user(PDO $pdo, int $userId): ?array
{
    $stmt = $pdo->prepare('SELECT role, is_active, must_change_password FROM users WHERE id = :id');
    $stmt->execute(['id' => $userId]);
    $user = $stmt->fetch();
    if (!$user || (int)$user['is_active'] !== 1) {
        return null;
    }

    $_SESSION['role'] = (string)$user['role'];
    return $user;
}

function destroy_app_session(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $cookie = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires' => time() - 42000,
            'path' => $cookie['path'],
            'domain' => $cookie['domain'],
            'secure' => $cookie['secure'],
            'httponly' => $cookie['httponly'],
            'samesite' => $cookie['samesite'] ?? 'Lax',
        ]);
    }
    session_destroy();
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
        app_start_session();
        $pdo = app_db();
        $userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
        $username = (string)($_SESSION['username'] ?? 'guest');
        $ipAddress = substr((string)($_SERVER['REMOTE_ADDR'] ?? 'unknown'), 0, 45);
        $userAgent = substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255);
        $detailsJson = null;
        if (!empty($details)) {
            try {
                $detailsJson = json_encode($details, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
            } catch (JsonException $jsonException) {
                $detailsJson = json_encode(['_warning' => 'details_encode_failed']);
                error_log('Audit log details_json encode failed: ' . $jsonException->getMessage());
            }
        }

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
            'details_json' => $detailsJson,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
        ]);
    } catch (Throwable $e) {
        error_log('Audit log insert failed: ' . $e->getMessage());
    }
}
