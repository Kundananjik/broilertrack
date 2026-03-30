# BroilerTrack Management System

BroilerTrack is a lightweight PHP + MySQL dashboard for broiler batch operations, sales collection, and farm financial reporting.

## Features
- Batch management from chick placement to sales.
- Expense and feed usage tracking.
- Sales workflow with shared visibility across all sales personnel.
- Payment posting ledger (`sales_payments`) for collection updates:
  - Post payment entries per sale.
  - `paid_amount` and `balance_amount` are recalculated safely.
  - Overpayment is blocked by validation.
- Dashboard metrics for revenue, expenses, profit, sales rate, mortality, and collections.
- Admin reporting (`reports.php`) with batch/date filters and CSV/Excel/PDF export.
- Audit logging (`audit_logs.php`) for operational actions.
- Soft-delete behavior for expenses, feed usage, and sales.
- Light/Dark theme toggle in the authenticated layout with `localStorage` persistence.
- Modernized public pages (`index.php`, `login.php`) and responsive UI.

## Removed Features
- Weighing/growth module has been retired from active use:
  - No growth menu item in sidebar.
  - `growth_records.php` is retired.
  - Sales entry no longer requires weighing inputs from users.

## Role Access
- `admin`
  - Full operational access.
  - User management.
  - Sale detail edit/delete permissions.
- `salesperson`
  - Sales module access.
  - Sales dashboard access.
  - Can view all sales and post payments.
  - Cannot edit/delete sale detail records.

## Requirements
- PHP 8.x with PDO MySQL extension.
- MySQL/MariaDB.
- No Composer required.

## Installation (XAMPP)
1. Copy the project folder to `C:\xampp\htdocs\broilertrack`.
2. Start Apache and MySQL.
3. Create database `broilertrack`.
4. Run `database/schema.sql`, then `database/seed.sql`.
5. Update DB settings in `config/database.php` or set env vars:
   - `BROILERTRACK_DB_HOST`
   - `BROILERTRACK_DB_NAME`
   - `BROILERTRACK_DB_USER`
   - `BROILERTRACK_DB_PASS`
6. Open `http://localhost/broilertrack/login.php`.

## Default Credentials
- Username: `admin`
- Password: `admin123`

## Pages
- Public:
  - `index.php`
  - `login.php`
- Admin:
  - `dashboard.php`
  - `add_batch.php`
  - `batches.php`
  - `expenses.php`
  - `feed_usage.php`
  - `users.php`
  - `sales.php`
  - `reports.php`
  - `audit_logs.php`
- Salesperson:
  - `dashboard.php` (sales dashboard view)
  - `sales.php`

## Security Notes
- Session-protected routes.
- CSRF protection on write actions.
- Password hashing for credentials.
- Login attempt rate-limiting / lockout.
- Inactive users cannot log in.
- Admin cannot deactivate own active session account.

## Testing
- Run:
  - `php tests/run.php`
