# BroilerTrack Management System

BroilerTrack is a lightweight PHP + MySQL dashboard for monitoring broiler chicken production cycles on a local XAMPP stack.

## Features
- Manage multiple broiler batches from chick placement to harvest.
- Log and manage expenses, feed usage, growth sampling, and broiler sales with create/edit/deactivate-style controls where applicable.
- Automatic profitability metrics (expenses, revenue, net profit, feed conversion, and unit costs) computed per batch using PHP only.
- Role-based access:
  - `admin`: full operational access + user management.
  - `salesperson`: sales module access + dedicated sales dashboard.
- Admin user lifecycle management:
  - Create users with role assignment.
  - Edit username/role.
  - Optional password reset during user edit.
  - Deactivate/reactivate user accounts.
- Session-protected interface with password hashing, CSRF validation on write actions, and login rate-limiting.

## Requirements
- PHP 8.x with the PDO MySQL extension (bundled with XAMPP).
- MySQL 5.7+ or 8.x.
- No Composer or JavaScript chart libraries are required.

## Installation (XAMPP)
1. Copy the `broilertrack` directory into `C:\xampp\htdocs`.
2. Start Apache and MySQL from the XAMPP Control Panel.
3. Create a database named `broilertrack`, then run `database/schema.sql` followed by `database/seed.sql`.
4. Update `config/database.php` if your MySQL credentials differ from the defaults.
5. Visit `http://localhost/broilertrack/login.php` to sign in.

## Default credentials
- Username: `admin`
- Password: `admin123`

After signing in as admin, create a batch, then use the sidebar modules to record expenses, feed usage, growth samples, and sales. Admin can also open `Users` to create and manage admin/salesperson accounts.

## Role access summary
- Admin pages: `add_batch.php`, `batches.php`, `expenses.php`, `feed_usage.php`, `growth_records.php`, `users.php`, `sales.php`, `index.php`
- Salesperson pages: `index.php` (sales dashboard view), `sales.php`

## Security notes
- Inactive users cannot log in.
- Admin cannot deactivate their own currently logged-in account.
- Username validation enforces a safe format and uniqueness.
- Passwords must be at least 8 characters.

## Quick test run
- Run `php tests/run.php` from the project root to execute lightweight validation and metrics tests.
