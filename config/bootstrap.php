<?php
declare(strict_types=1);

date_default_timezone_set('Africa/Lusaka');

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/csrf.php';

function app_environment(): string
{
    $environment = strtolower(trim((string)(getenv('BROILERTRACK_ENV') ?: 'development')));
    if (!in_array($environment, ['development', 'production'], true)) {
        throw new RuntimeException('BROILERTRACK_ENV must be development or production.');
    }

    return $environment;
}

function app_is_https(): bool
{
    $isHttps = (!empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off')
        || (int)($_SERVER['SERVER_PORT'] ?? 0) === 443
        || (isset($_SERVER['REQUEST_SCHEME']) && strtolower((string)$_SERVER['REQUEST_SCHEME']) === 'https');

    $trustProxy = filter_var(
        getenv('BROILERTRACK_TRUST_PROXY_HTTPS') ?: 'false',
        FILTER_VALIDATE_BOOLEAN
    );

    return $isHttps || ($trustProxy && strtolower((string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https');
}

function app_production_base_url(): string
{
    $baseUrl = rtrim((string)(getenv('BROILERTRACK_BASE_URL') ?: ''), '/');
    $parts = parse_url($baseUrl);
    if (
        $parts === false
        || strtolower((string)($parts['scheme'] ?? '')) !== 'https'
        || empty($parts['host'])
        || isset($parts['user'])
        || isset($parts['pass'])
        || isset($parts['query'])
        || isset($parts['fragment'])
        || (isset($parts['path']) && $parts['path'] !== '' && $parts['path'] !== '/')
    ) {
        throw new RuntimeException('BROILERTRACK_BASE_URL must be the HTTPS origin used by this production installation.');
    }

    return $baseUrl;
}

function app_send_security_headers(): void
{
    if (headers_sent()) {
        return;
    }

    header_remove('X-Powered-By');
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), geolocation=(), microphone=()');
    header("Content-Security-Policy: default-src 'self'; base-uri 'self'; form-action 'self'; frame-ancestors 'none'; object-src 'none'; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com data:; img-src 'self' data:");
}

function app_start_session(): void
{
    $environment = app_environment();
    app_send_security_headers();

    if ($environment === 'production') {
        $baseUrl = app_production_base_url();
        $cookieSecure = getenv('BROILERTRACK_COOKIE_SECURE');
        if ($cookieSecure !== false && !filter_var($cookieSecure, FILTER_VALIDATE_BOOLEAN)) {
            throw new RuntimeException('BROILERTRACK_COOKIE_SECURE cannot be disabled in production.');
        }

        if (!app_is_https()) {
            $requestUri = (string)($_SERVER['REQUEST_URI'] ?? '/');
            if ($requestUri === '' || $requestUri[0] !== '/' || str_starts_with($requestUri, '//') || preg_match('/[\r\n]/', $requestUri)) {
                $requestUri = '/';
            }
            header('Location: ' . $baseUrl . $requestUri, true, 301);
            exit;
        }

        header('Strict-Transport-Security: max-age=31536000');
        ini_set('display_errors', '0');
        ini_set('display_startup_errors', '0');
        ini_set('log_errors', '1');
    }

    $isHttps = $environment === 'production' || app_is_https();
    $sessionOptions = [
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax',
        'cookie_secure' => $isHttps,
        'cookie_path' => '/',
    ];

    if (session_status() !== PHP_SESSION_ACTIVE) {
        ini_set('session.use_strict_mode', '1');
        ini_set('session.gc_maxlifetime', '43200');
        session_start($sessionOptions);
    }

    $now = time();
    $lastActivity = filter_var($_SESSION['last_activity_at'] ?? null, FILTER_VALIDATE_INT);
    $sessionStarted = filter_var($_SESSION['session_started_at'] ?? null, FILTER_VALIDATE_INT);
    if (
        ($lastActivity !== false && $lastActivity !== null && $now - $lastActivity > 1800)
        || ($sessionStarted !== false && $sessionStarted !== null && $now - $sessionStarted > 43200)
    ) {
        destroy_app_session();
        session_start($sessionOptions);
        session_regenerate_id(true);
        $_SESSION = [];
    }

    $_SESSION['session_started_at'] = $_SESSION['session_started_at'] ?? $now;
    $_SESSION['last_activity_at'] = $now;
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
