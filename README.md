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
- Transactional payment ledger using InnoDB.
- Dashboard metrics for revenue, expenses, profit, sales rate, mortality, and collections.
- Admin reporting (`reports.php`) with batch/date filters and CSV/Excel/PDF export.
- Audit logging (`audit_logs.php`) for operational actions.
- Soft-delete behavior for expenses, feed usage, and sales.
- Light/Dark theme toggle in the authenticated layout with `localStorage` persistence.
- Six-month revenue and birds-sold trend on both dashboards.
- Strong password requirements when administrators create users or change passwords.
- The seeded admin account must change its initial password before using protected pages.
- Modernized public pages (`index.php`, `login.php`) and responsive UI.

## Project layout and cleanup status
- The project root is the canonical application location for local development and deployment.
- The `htdocs/` directory is a legacy mirror kept only for compatibility with older deployment conventions; it should not be treated as the active app root.
- Weighing/growth module has been retired from active use:
  - No growth menu item in sidebar.
  - `growth_records.php` has been retired.
  - Sales entry no longer requires weighing inputs from users.

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
5. For local development, the DB defaults are `localhost`, database `broilertrack`, user `root`, and an empty password. For production, set `BROILERTRACK_ENV=production` and configure all four DB variables; the app fails fast rather than using local defaults if any are missing:
   - `BROILERTRACK_DB_HOST`
   - `BROILERTRACK_DB_NAME`
   - `BROILERTRACK_DB_USER`
   - `BROILERTRACK_DB_PASS`
6. Open `http://localhost/broilertrack/login.php`. After signing in with the development seed credentials, choose a new password to continue.

### Updating an existing installation
1. Back up the database.
2. Select the BroilerTrack database, then run `database/migrations/20261031_transactional_payments_and_admin_password.sql`.
3. Deploy the updated application files. The migration is safe to run again.
4. The seeded admin account is required to change its password on the next protected page load. Existing logins are also checked against this requirement.

## Development Seed Credentials
- Username: `admin`
- Password: `admin123`

These legacy seed credentials do not meet the new password requirements. After the first login, change the admin password when prompted before continuing. Password rules apply when a password is created or changed.

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
- Each authenticated request rechecks that the account is active and refreshes the user's current role from the database.
- CSRF protection on write actions.
- Password hashing for credentials.
- New or changed passwords must use at least 12 characters with uppercase and lowercase letters, a number, and a symbol, or a passphrase of at least 4 words and 20 characters.
- Login attempt rate-limiting / lockout.
- Inactive users cannot log in.
- Production deployments must explicitly configure a non-empty DB host, database name, user, and password.
- Admin cannot deactivate own active session account.

## Testing
- Run:
  - `php tests/run.php`
