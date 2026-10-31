<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../config/csrf.php';
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../controllers/SaleController.php';
require_once __DIR__ . '/../controllers/BatchController.php';
require_once __DIR__ . '/../controllers/UserController.php';
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
        notes TEXT,
        is_deleted INTEGER NOT NULL DEFAULT 0,
        deleted_at TEXT
    )');

    $pdo->exec('CREATE TABLE feed_usage (
        record_id INTEGER PRIMARY KEY AUTOINCREMENT,
        batch_id INTEGER NOT NULL,
        date TEXT NOT NULL,
        feed_type TEXT NOT NULL,
        feed_kg REAL NOT NULL,
        cost_per_kg REAL NOT NULL,
        total_cost REAL NOT NULL,
        is_deleted INTEGER NOT NULL DEFAULT 0,
        deleted_at TEXT
    )');

    $pdo->exec('CREATE TABLE sales (
        sale_id INTEGER PRIMARY KEY AUTOINCREMENT,
        batch_id INTEGER NOT NULL,
        date TEXT NOT NULL,
        birds_sold INTEGER NOT NULL,
        average_weight_kg REAL NOT NULL DEFAULT 0,
        price_per_bird REAL NOT NULL,
        total_weight REAL NOT NULL DEFAULT 0,
        total_revenue REAL NOT NULL,
        paid_amount REAL NOT NULL DEFAULT 0,
        balance_amount REAL NOT NULL DEFAULT 0,
        buyer TEXT,
        created_by INTEGER,
        is_deleted INTEGER NOT NULL DEFAULT 0,
        deleted_at TEXT
    )');

    $pdo->exec('CREATE TABLE users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT NOT NULL UNIQUE,
        password_hash TEXT NOT NULL,
        role TEXT NOT NULL,
        is_active INTEGER NOT NULL DEFAULT 1,
        must_change_password INTEGER NOT NULL DEFAULT 0,
        created_at TEXT DEFAULT CURRENT_TIMESTAMP
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

function test_report_filter_and_spreadsheet_validation(): void
{
    assert_true(is_valid_date_range('2026-01-01', '2026-12-31'), 'Valid report date ranges should be accepted.');
    assert_true(!is_valid_date_range('2026-02-30', '2026-03-01'), 'Invalid calendar dates should be rejected.');
    assert_true(!is_valid_date_range('2026-03-01', '2026-02-28'), 'Reversed report date ranges should be rejected.');
    assert_equals("'=SUM(A1:A2)", spreadsheet_safe_text('=SUM(A1:A2)'), 'Spreadsheet formulas should be treated as text.');
    assert_equals("'  @SUM(A1:A2)", spreadsheet_safe_text('  @SUM(A1:A2)'), 'Formula markers after whitespace should be treated as text.');
    assert_equals('Batch 12', spreadsheet_safe_text('Batch 12'), 'Ordinary spreadsheet text should be unchanged.');
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
        'price_per_bird' => 6.0,
        'buyer' => 'Buyer',
    ]);
    assert_true($result['success'] === true, 'Sale should save when inventory is available.');

    $remaining = (int)$pdo->query("SELECT current_alive FROM batches WHERE batch_id = {$batchId}")->fetchColumn();
    assert_equals(15, $remaining, 'current_alive should be decremented by birds_sold.');
}

function test_sale_payment_updates_paid_and_balance(): void
{
    $pdo = make_pdo();
    $batchId = seed_batch($pdo, 60, 20, 0);
    $controller = new SaleController($pdo);

    $createResult = $controller->store([
        'batch_id' => $batchId,
        'date' => '2026-02-11',
        'birds_sold' => 5,
        'price_per_bird' => 10.0,
        'buyer' => 'Buyer',
    ]);
    assert_true(($createResult['success'] ?? false) === true, 'Sale should save before adding a payment.');

    $saleId = (int)$createResult['sale_id'];
    $paymentResult = $controller->addPayment([
        'sale_id' => $saleId,
        'payment_date' => '2026-02-12',
        'payment_amount' => 30.0,
        'payment_notes' => 'Part payment',
    ]);
    assert_true(($paymentResult['success'] ?? false) === true, 'Payment should post successfully.');

    $row = $pdo->query("SELECT paid_amount, balance_amount FROM sales WHERE sale_id = {$saleId}")->fetch(PDO::FETCH_ASSOC);
    assert_equals(30.0, (float)$row['paid_amount'], 'Paid amount should increase after payment posting.');
    assert_equals(20.0, (float)$row['balance_amount'], 'Balance should decrease after payment posting.');
}

function test_sale_payment_cannot_exceed_balance(): void
{
    $pdo = make_pdo();
    $batchId = seed_batch($pdo, 60, 20, 0);
    $controller = new SaleController($pdo);

    $createResult = $controller->store([
        'batch_id' => $batchId,
        'date' => '2026-02-11',
        'birds_sold' => 5,
        'price_per_bird' => 10.0,
        'buyer' => 'Buyer',
    ]);
    assert_true(($createResult['success'] ?? false) === true, 'Sale should save before validating payment limits.');

    $saleId = (int)$createResult['sale_id'];
    $paymentResult = $controller->addPayment([
        'sale_id' => $saleId,
        'payment_date' => '2026-02-12',
        'payment_amount' => 51.0,
    ]);
    assert_true(($paymentResult['success'] ?? false) === false, 'Payment should fail when it exceeds current balance.');
}

function test_sale_payment_rolls_back_if_sale_update_fails(): void
{
    $pdo = make_pdo();
    $batchId = seed_batch($pdo, 60, 20, 0);
    $controller = new SaleController($pdo);
    $createResult = $controller->store([
        'batch_id' => $batchId,
        'date' => '2026-02-11',
        'birds_sold' => 5,
        'price_per_bird' => 10.0,
        'buyer' => 'Buyer',
    ]);
    assert_true(($createResult['success'] ?? false) === true, 'Sale should save before testing payment rollback.');
    $saleId = (int)$createResult['sale_id'];
    $pdo->exec("CREATE TRIGGER reject_sale_payment_update BEFORE UPDATE ON sales
        BEGIN SELECT RAISE(ABORT, 'forced payment update failure'); END");

    $paymentResult = $controller->addPayment([
        'sale_id' => $saleId,
        'payment_date' => '2026-02-12',
        'payment_amount' => 30.0,
    ]);
    assert_true(($paymentResult['success'] ?? false) === false, 'Payment should fail when the sale update fails.');
    assert_equals(0, (int)$pdo->query("SELECT COUNT(*) FROM sales_payments WHERE sale_id = {$saleId}")->fetchColumn(), 'Failed payment update should roll back the payment ledger insert.');
    $sale = $pdo->query("SELECT paid_amount, balance_amount FROM sales WHERE sale_id = {$saleId}")->fetch(PDO::FETCH_ASSOC);
    assert_equals(0.0, (float)$sale['paid_amount'], 'Failed payment update should leave paid total unchanged.');
    assert_equals(50.0, (float)$sale['balance_amount'], 'Failed payment update should leave balance unchanged.');
}

function test_sale_updates_require_admin(): void
{
    $pdo = make_pdo();
    $batchId = seed_batch($pdo, 60, 20, 0);
    $controller = new SaleController($pdo);
    $_SESSION['role'] = 'salesperson';

    $createResult = $controller->store([
        'batch_id' => $batchId,
        'date' => '2026-02-11',
        'birds_sold' => 5,
        'price_per_bird' => 10.0,
        'buyer' => 'Buyer',
    ]);
    assert_true(($createResult['success'] ?? false) === true, 'Sale should be creatable by a salesperson.');

    $saleId = (int)$createResult['sale_id'];
    $updateResult = $controller->update([
        'sale_id' => $saleId,
        'batch_id' => $batchId,
        'date' => '2026-02-11',
        'birds_sold' => 6,
        'price_per_bird' => 10.0,
        'buyer' => 'Buyer',
    ]);
    assert_true(($updateResult['success'] ?? false) === false, 'Salesperson should not be able to edit sale detail records.');

    $deleteResult = $controller->delete(['sale_id' => $saleId]);
    assert_true(($deleteResult['success'] ?? false) === false, 'Salesperson should not be able to delete sale detail records.');
}

function test_batch_status_considers_sold_birds(): void
{
    $pdo = make_pdo();
    $batchId = seed_batch($pdo, 100, 70, 0);
    $pdo->exec("INSERT INTO sales (batch_id, date, birds_sold, price_per_bird, total_revenue, buyer) VALUES ({$batchId}, '2026-02-01', 30, 6.0, 180.0, 'X')");

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
    $pdo->exec("INSERT INTO sales (batch_id, date, birds_sold, price_per_bird, total_revenue, buyer) VALUES ({$batchId}, '2026-02-01', 40, 6.0, 240, 'Buyer')");

    $dashboard = new Dashboard($pdo);
    $metrics = $dashboard->getMetrics($batchId);

    assert_equals(180.0, (float)$metrics['total_expenses'], 'Total expenses should include chick, feed, and expense costs.');
    assert_equals(240.0, (float)$metrics['total_revenue'], 'Total revenue should match sum of sales revenue.');
    assert_equals(60.0, (float)$metrics['net_profit'], 'Net profit should be revenue minus expenses.');
}

function test_dashboard_sales_trend(): void
{
    $pdo = make_pdo();
    $batchId = seed_batch($pdo);
    $today = new DateTimeImmutable('today');
    $firstMonth = $today->modify('first day of this month')->modify('-5 months');
    $insert = $pdo->prepare(
        'INSERT INTO sales (batch_id, date, birds_sold, price_per_bird, total_revenue, buyer)
         VALUES (:batch_id, :date, :birds_sold, :price_per_bird, :total_revenue, :buyer)'
    );
    foreach ([[$firstMonth, 3, 30.0], [$today->modify('first day of this month'), 2, 20.0]] as [$date, $birdsSold, $revenue]) {
        $insert->execute([
            'batch_id' => $batchId,
            'date' => $date->format('Y-m-d'),
            'birds_sold' => $birdsSold,
            'price_per_bird' => 10,
            'total_revenue' => $revenue,
            'buyer' => 'Buyer',
        ]);
    }

    $trend = (new Dashboard($pdo))->salesTrend($batchId);
    assert_equals(6, count($trend), 'Sales trend should include six calendar months.');
    assert_equals($firstMonth->format('Y-m'), $trend[0]['month'], 'Sales trend should start five months before the current month.');
    assert_equals(30.0, $trend[0]['revenue'], 'Sales trend should aggregate revenue for each month.');
    assert_equals(3, $trend[0]['birds_sold'], 'Sales trend should aggregate birds sold for each month.');
    assert_equals(20.0, $trend[5]['revenue'], 'Sales trend should include current-month sales.');
}

function test_user_password_policy(): void
{
    $pdo = make_pdo();
    $controller = new UserController($pdo);
    $baseInput = [
        'username' => 'farmuser',
        'role' => 'salesperson',
    ];

    $weakResult = $controller->create($baseInput + [
        'password' => 'admin123',
        'confirm_password' => 'admin123',
    ]);
    assert_true(!$weakResult['success'], 'User creation should reject weak passwords.');

    $createResult = $controller->create($baseInput + [
        'password' => 'Strong#Pass2026',
        'confirm_password' => 'Strong#Pass2026',
    ]);
    assert_true($createResult['success'], 'User creation should accept a complex password.');
    $userId = (int)$pdo->lastInsertId();

    $weakUpdate = $controller->update([
        'user_id' => $userId,
        'username' => 'farmuser',
        'role' => 'salesperson',
        'password' => 'simplepass',
        'confirm_password' => 'simplepass',
    ]);
    assert_true(!$weakUpdate['success'], 'Password changes should reject weak passwords.');

    $passphraseUpdate = $controller->update([
        'user_id' => $userId,
        'username' => 'farmuser',
        'role' => 'salesperson',
        'password' => 'correct horse battery staple',
        'confirm_password' => 'correct horse battery staple',
    ]);
    assert_true($passphraseUpdate['success'], 'Password changes should accept a long multi-word passphrase.');
}

function test_forced_password_change_clears_requirement(): void
{
    $pdo = make_pdo();
    $stmt = $pdo->prepare(
        'INSERT INTO users (username, password_hash, role, must_change_password)
         VALUES (:username, :password_hash, :role, 1)'
    );
    $stmt->execute([
        'username' => 'admin',
        'password_hash' => password_hash('admin123', PASSWORD_DEFAULT),
        'role' => 'admin',
    ]);
    $userId = (int)$pdo->lastInsertId();

    $result = (new UserController($pdo))->changeOwnPassword([
        'password' => 'Strong#Pass2026',
        'confirm_password' => 'Strong#Pass2026',
    ], $userId);
    assert_true($result['success'], 'Forced password change should accept a compliant new password.');

    $user = $pdo->query("SELECT password_hash, must_change_password FROM users WHERE id = {$userId}")->fetch(PDO::FETCH_ASSOC);
    assert_true(password_verify('Strong#Pass2026', $user['password_hash']), 'Forced password change should save the new password hash.');
    assert_equals(0, (int)$user['must_change_password'], 'Forced password change should clear the requirement flag.');
}

function test_authenticated_user_state_is_refreshed(): void
{
    $pdo = make_pdo();
    $stmt = $pdo->prepare(
        'INSERT INTO users (username, password_hash, role, is_active, must_change_password)
         VALUES (:username, :password_hash, :role, :is_active, :must_change_password)'
    );
    $stmt->execute([
        'username' => 'authuser',
        'password_hash' => password_hash('Strong#Pass2026', PASSWORD_DEFAULT),
        'role' => 'salesperson',
        'is_active' => 1,
        'must_change_password' => 1,
    ]);
    $userId = (int)$pdo->lastInsertId();

    $_SESSION['user_id'] = $userId;
    $_SESSION['role'] = 'admin';
    $user = refresh_authenticated_user($pdo, $userId);
    assert_equals('salesperson', $_SESSION['role'], 'Authenticated role should refresh from the database.');
    assert_equals(1, (int)$user['must_change_password'], 'Password-change requirement should refresh from the database.');

    $pdo->prepare('UPDATE users SET is_active = 0 WHERE id = :id')->execute(['id' => $userId]);
    assert_equals(null, refresh_authenticated_user($pdo, $userId), 'Disabled accounts should no longer have an authenticated user state.');
}

try {
    test_csrf();
    test_report_filter_and_spreadsheet_validation();
    test_sale_inventory_guard();
    test_sale_decrements_current_alive();
    test_sale_payment_updates_paid_and_balance();
    test_sale_payment_cannot_exceed_balance();
    test_sale_payment_rolls_back_if_sale_update_fails();
    test_batch_status_considers_sold_birds();
    test_dashboard_metrics();
    test_dashboard_sales_trend();
    test_user_password_policy();
    test_forced_password_change_clears_requirement();
    test_authenticated_user_state_is_refreshed();
    echo "All tests passed.\n";
} catch (Throwable $exception) {
    fwrite(STDERR, "Test failure: " . $exception->getMessage() . "\n");
    exit(1);
}
