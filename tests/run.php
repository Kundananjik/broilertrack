<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../config/csrf.php';
require_once __DIR__ . '/../controllers/SaleController.php';
require_once __DIR__ . '/../controllers/BatchController.php';
require_once __DIR__ . '/../models/Dashboard.php';

function assert_true(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function assert_equals($expected, $actual, string $message): void
{
    if ($expected !== $actual) {
        throw new RuntimeException($message . " Expected: " . var_export($expected, true) . ", Actual: " . var_export($actual, true));
    }
}

function make_pdo(): PDO
{
    $pdo = new PDO('sqlite::memory:');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    $pdo->exec('CREATE TABLE batches (
        batch_id INTEGER PRIMARY KEY AUTOINCREMENT,
        batch_name TEXT NOT NULL,
        breed TEXT NOT NULL,
        start_date TEXT NOT NULL,
        expected_harvest_date TEXT NOT NULL,
        initial_chicks INTEGER NOT NULL,
        chick_cost REAL NOT NULL,
        total_chick_cost REAL NOT NULL,
        current_alive INTEGER NOT NULL,
        mortality_count INTEGER NOT NULL,
        notes TEXT
    )');

    $pdo->exec('CREATE TABLE expenses (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        batch_id INTEGER NOT NULL,
        date TEXT NOT NULL,
        category TEXT NOT NULL,
        item_name TEXT NOT NULL,
        quantity REAL NOT NULL,
        unit_cost REAL NOT NULL,
        total_cost REAL NOT NULL,
        supplier TEXT,
        notes TEXT
    )');

    $pdo->exec('CREATE TABLE feed_usage (
        record_id INTEGER PRIMARY KEY AUTOINCREMENT,
        batch_id INTEGER NOT NULL,
        date TEXT NOT NULL,
        feed_type TEXT NOT NULL,
        feed_kg REAL NOT NULL,
        cost_per_kg REAL NOT NULL,
        total_cost REAL NOT NULL
    )');

    $pdo->exec('CREATE TABLE growth_records (
        record_id INTEGER PRIMARY KEY AUTOINCREMENT,
        batch_id INTEGER NOT NULL,
        date TEXT NOT NULL,
        average_weight_kg REAL NOT NULL,
        birds_sampled INTEGER NOT NULL
    )');

    $pdo->exec('CREATE TABLE sales (
        sale_id INTEGER PRIMARY KEY AUTOINCREMENT,
        batch_id INTEGER NOT NULL,
        date TEXT NOT NULL,
        birds_sold INTEGER NOT NULL,
        average_weight_kg REAL NOT NULL,
        price_per_bird REAL NOT NULL,
        total_weight REAL NOT NULL,
        total_revenue REAL NOT NULL,
        buyer TEXT
    )');

    return $pdo;
}

function seed_batch(PDO $pdo, int $initial = 100, int $alive = 100, int $mortality = 0): int
{
    $stmt = $pdo->prepare('INSERT INTO batches (batch_name, breed, start_date, expected_harvest_date, initial_chicks, chick_cost, total_chick_cost, current_alive, mortality_count, notes)
            VALUES (:batch_name, :breed, :start_date, :expected_harvest_date, :initial_chicks, :chick_cost, :total_chick_cost, :current_alive, :mortality_count, :notes)');
    $stmt->execute([
        'batch_name' => 'T1',
        'breed' => 'Cobb',
        'start_date' => '2026-01-01',
        'expected_harvest_date' => '2026-02-15',
        'initial_chicks' => $initial,
        'chick_cost' => 1.00,
        'total_chick_cost' => (float)$initial,
        'current_alive' => $alive,
        'mortality_count' => $mortality,
        'notes' => '',
    ]);

    return (int)$pdo->lastInsertId();
}

function test_csrf(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    $_SESSION = [];
    $token = csrf_token();
    $_POST['csrf_token'] = $token;
    assert_true(csrf_verify(), 'CSRF verification should pass with the generated token.');
}

function test_sale_inventory_guard(): void
{
    $pdo = make_pdo();
    $batchId = seed_batch($pdo, 50, 10, 0);
    $controller = new SaleController($pdo);

    $result = $controller->store([
        'batch_id' => $batchId,
        'date' => '2026-02-10',
        'birds_sold' => 11,
        'average_weight_kg' => 2.0,
        'price_per_bird' => 6.0,
        'buyer' => 'Buyer',
    ]);

    assert_true($result['success'] === false, 'Sale should fail when birds sold exceed current alive.');
}

function test_sale_decrements_current_alive(): void
{
    $pdo = make_pdo();
    $batchId = seed_batch($pdo, 60, 20, 0);
    $controller = new SaleController($pdo);

    $result = $controller->store([
        'batch_id' => $batchId,
        'date' => '2026-02-10',
        'birds_sold' => 5,
        'average_weight_kg' => 2.0,
        'price_per_bird' => 6.0,
        'buyer' => 'Buyer',
    ]);
    assert_true($result['success'] === true, 'Sale should save when inventory is available.');

    $remaining = (int)$pdo->query("SELECT current_alive FROM batches WHERE batch_id = {$batchId}")->fetchColumn();
    assert_equals(15, $remaining, 'current_alive should be decremented by birds_sold.');
}

function test_batch_status_considers_sold_birds(): void
{
    $pdo = make_pdo();
    $batchId = seed_batch($pdo, 100, 70, 0);
    $pdo->exec("INSERT INTO sales (batch_id, date, birds_sold, average_weight_kg, price_per_bird, total_weight, total_revenue, buyer) VALUES ({$batchId}, '2026-02-01', 30, 2.0, 6.0, 60.0, 180.0, 'X')");

    $controller = new BatchController($pdo);
    $result = $controller->updateStatus([
        'batch_id' => $batchId,
        'current_alive' => 80,
        'mortality_count' => 0,
    ]);

    assert_true($result['success'] === false, 'Batch status update should fail when alive + sold + mortality exceed initial.');
}

function test_dashboard_metrics(): void
{
    $pdo = make_pdo();
    $batchId = seed_batch($pdo, 50, 45, 5);

    $pdo->exec("INSERT INTO expenses (batch_id, date, category, item_name, quantity, unit_cost, total_cost, supplier, notes) VALUES ({$batchId}, '2026-01-02', 'Utilities', 'Power', 1, 10, 10, '', '')");
    $pdo->exec("INSERT INTO feed_usage (batch_id, date, feed_type, feed_kg, cost_per_kg, total_cost) VALUES ({$batchId}, '2026-01-03', 'Starter', 100, 1.2, 120)");
    $pdo->exec("INSERT INTO growth_records (batch_id, date, average_weight_kg, birds_sampled) VALUES ({$batchId}, '2026-01-10', 1.500, 10)");
    $pdo->exec("INSERT INTO sales (batch_id, date, birds_sold, average_weight_kg, price_per_bird, total_weight, total_revenue, buyer) VALUES ({$batchId}, '2026-02-01', 40, 2.0, 6.0, 80, 240, 'Buyer')");

    $dashboard = new Dashboard($pdo);
    $metrics = $dashboard->getMetrics($batchId);

    assert_equals(180.0, (float)$metrics['total_expenses'], 'Total expenses should include chick, feed, and expense costs.');
    assert_equals(240.0, (float)$metrics['total_revenue'], 'Total revenue should match sum of sales revenue.');
    assert_equals(60.0, (float)$metrics['net_profit'], 'Net profit should be revenue minus expenses.');
}

try {
    test_csrf();
    test_sale_inventory_guard();
    test_sale_decrements_current_alive();
    test_batch_status_considers_sold_birds();
    test_dashboard_metrics();
    echo "All tests passed.\n";
} catch (Throwable $exception) {
    fwrite(STDERR, "Test failure: " . $exception->getMessage() . "\n");
    exit(1);
}

