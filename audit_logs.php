<?php
declare(strict_types=1);

require_once __DIR__ . '/config/bootstrap.php';

require_admin();

$pdo = app_db();
$module = trim((string)($_GET['module'] ?? ''));
$action = trim((string)($_GET['action'] ?? ''));
$dateFrom = trim((string)($_GET['date_from'] ?? ''));
$dateTo = trim((string)($_GET['date_to'] ?? ''));

$conditions = ['1=1'];
$params = [];
if ($module !== '') {
    $conditions[] = 'module = :module';
    $params['module'] = $module;
}
if ($action !== '') {
    $conditions[] = 'action = :action';
    $params['action'] = $action;
}
if ($dateFrom !== '') {
    $conditions[] = 'DATE(created_at) >= :date_from';
    $params['date_from'] = $dateFrom;
}
if ($dateTo !== '') {
    $conditions[] = 'DATE(created_at) <= :date_to';
    $params['date_to'] = $dateTo;
}

$where = implode(' AND ', $conditions);
$stmt = $pdo->prepare(
    "SELECT id, username, module, action, entity_type, entity_id, details_json, ip_address, created_at
     FROM audit_logs
     WHERE {$where}
     ORDER BY id DESC
     LIMIT 300"
);
$stmt->execute($params);
$logs = $stmt->fetchAll() ?: [];

$moduleRows = $pdo->query('SELECT DISTINCT module FROM audit_logs ORDER BY module ASC');
$actionRows = $pdo->query('SELECT DISTINCT action FROM audit_logs ORDER BY action ASC');
$modules = $moduleRows ? ($moduleRows->fetchAll(PDO::FETCH_COLUMN) ?: []) : [];
$actions = $actionRows ? ($actionRows->fetchAll(PDO::FETCH_COLUMN) ?: []) : [];

$pageTitle = 'Audit Logs';
include __DIR__ . '/views/layout_header.php';
?>
<section class="form-section">
    <h2>Audit Activity</h2>
    <form method="get" class="form-grid">
        <label>Module
            <select name="module">
                <option value="">All modules</option>
                <?php foreach ($modules as $m): ?>
                    <option value="<?= htmlspecialchars((string)$m, ENT_QUOTES, 'UTF-8'); ?>" <?= $module === (string)$m ? 'selected' : ''; ?>>
                        <?= htmlspecialchars((string)$m, ENT_QUOTES, 'UTF-8'); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Action
            <select name="action">
                <option value="">All actions</option>
                <?php foreach ($actions as $a): ?>
                    <option value="<?= htmlspecialchars((string)$a, ENT_QUOTES, 'UTF-8'); ?>" <?= $action === (string)$a ? 'selected' : ''; ?>>
                        <?= htmlspecialchars((string)$a, ENT_QUOTES, 'UTF-8'); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>From
            <input type="date" name="date_from" value="<?= htmlspecialchars($dateFrom, ENT_QUOTES, 'UTF-8'); ?>">
        </label>
        <label>To
            <input type="date" name="date_to" value="<?= htmlspecialchars($dateTo, ENT_QUOTES, 'UTF-8'); ?>">
        </label>
        <button type="submit">Filter Logs</button>
    </form>
</section>

<section class="table-section audit-log-section">
    <div class="table-responsive">
        <table class="table table-striped table-bordered align-middle audit-table">
            <thead>
                <tr>
                    <th>Time</th>
                    <th>User</th>
                    <th>Module</th>
                    <th>Action</th>
                    <th>Entity</th>
                    <th>Details</th>
                    <th>IP</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($logs as $log): ?>
                <tr>
                    <td><?= htmlspecialchars((string)$log['created_at'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?= htmlspecialchars((string)$log['username'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?= htmlspecialchars((string)$log['module'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?= htmlspecialchars((string)$log['action'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?= htmlspecialchars((string)$log['entity_type'], ENT_QUOTES, 'UTF-8'); ?>#<?= (int)($log['entity_id'] ?? 0); ?></td>
                    <td><code><?= htmlspecialchars((string)($log['details_json'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></code></td>
                    <td><?= htmlspecialchars((string)($log['ip_address'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php include __DIR__ . '/views/layout_footer.php'; ?>
