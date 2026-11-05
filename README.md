# Msantha Poultry Management System

A mobile-first Progressive Web Application (PWA) built specifically for poultry farm operations in Malawi (Msantha).

## Architecture & Technology Stack
- **Backend:** PHP 8.2+ (MVC architecture, zero heavy frameworks, PSR-12 compliant, `declare(strict_types=1);` enforced).
- **Database:** MariaDB 10.4+ / MySQL 8.0+ (`database/schema.sql`, InnoDB, utf8mb4, integer Malawi Kwacha currency).
- **Frontend:** HTML5, Bootstrap 5.3, vanilla JavaScript (ES modules), Chart.js.
- **Offline / PWA:** Service worker (`public/sw.js`), web app manifest (`public/manifest.webmanifest`), IndexedDB offline sync queue.
- **Timezone:** `Africa/Blantyre`.
- **Currency:** Malawi Kwacha (MWK), integer storage only.

---

## Directory Structure
```
/public            index.php (front controller), assets/ (css, js, img), manifest.webmanifest, sw.js
/app
  /Core            Router, Request, Response, DB, Auth, Csrf, Validator, View, Logger, Phone, DateParser
  /Controllers     HomeController, HealthController (modular controllers)
  /Models          Thin PDO data models
  /Services        SmsService, PayChanguService, ImportService, ReportService, PricingService
  /Views           layouts/ (main.php) + module views
  /lang            en.php (English dictionary), ny.php (Chichewa prepared)
/config            app.php, database.php
/database          schema.sql, seeds/, migrations/
/storage           logs/, imports/ (data pack), cache/
/tests             PHPUnit Unit & Feature tests
/cron              sms_queue.php, daily_reminders.php
```

---

## Local Setup (XAMPP on Windows)
1. Ensure Apache and MySQL are running in XAMPP.
2. In MySQL, ensure the database exists:
   ```sql
   CREATE DATABASE IF NOT EXISTS msantha_poultry CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```
3. Copy `.env.example` to `.env` and verify the settings:
   ```ini
   APP_NAME="Msantha Poultry Management System"
   APP_ENV=local
   APP_DEBUG=true
   APP_URL=http://localhost/remix-msantha-poultry-farm-manager%20(1)/public
   APP_TIMEZONE=Africa/Blantyre
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=msantha_poultry
   DB_USERNAME=root
   DB_PASSWORD=
   ```
4. Run Database Migrations & Seeders:
   ```bash
   "C:\xampp\php\php.exe" cron/migrate.php --fresh --seed --owner-password="YourStrongPassword"
   ```
5. Run tests:
   ```bash
   "C:\xampp\php\php.exe" vendor/bin/phpunit
   ```
6. Access in browser:
   `http://localhost/remix-msantha-poultry-farm-manager%20(1)/public/`

---

## Database Migrations & SQL Views
The database is managed with an idempotent CLI migration runner:
- `php cron/migrate.php`: Runs all pending migrations.
- `php cron/migrate.php --fresh`: Drops all views and tables (restricted to `APP_ENV=local` and requires typing the database name to confirm).
- `php cron/migrate.php --seed`: Seeds baseline roles, species, breeds, business units, feed types, sms templates, and allocation rules.
- `php cron/migrate.php --create-owner`: Prompts securely for owner credentials using a hidden prompt (passwords are never printed or stored in code).

---

## Setting Owner Password Interactively
To set or change the farm owner password without exposing it in command history or files:
```powershell
& "C:\xampp\php\php.exe" cron/migrate.php --create-owner
```
This launches a secure, hidden password prompt:
1. Prompts `Enter new password for owner (hidden): ` (keystrokes masked).
2. Prompts `Confirm owner password (hidden): `.
3. Hashes with `Argon2id` and updates the `owner` account.

---

## Database Backup (mysqldump)
To take a full backup of the production database using the XAMPP MariaDB binary:

### PowerShell:
```powershell
& "C:\xampp\mysql\bin\mysqldump.exe" -u root -p msantha_poultry --routines --triggers --single-transaction > "C:\xampp\htdocs\msantha poultry managment system\storage\backups\msantha_poultry_backup.sql"
```

### Windows CMD:
```cmd
"C:\xampp\mysql\bin\mysqldump.exe" -u root -p msantha_poultry --routines --triggers --single-transaction > "C:\xampp\htdocs\msantha poultry managment system\storage\backups\msantha_poultry_backup.sql"
```

---

## Isolated Test Database
PHPUnit test runs are isolated to `msantha_poultry_test` and **never** touch the production `msantha_poultry` database.
- Database: `msantha_poultry_test`
- Automated tests generate throwaway users within transactions and automatically rollback changes.

---

## Phase 3: Authentication, Roles & Audit Logging
- **Login:** Supports login by username or normalized phone number (`+265...` or `099...`) with `Argon2id` password verification.
- **Throttling:** 5 consecutive failed attempts locks the IP/user combination for 15 minutes.
- **Forgot Password via SMS:** Generates a 6-digit verification code (valid 10 minutes, max 3 attempts) queued to `sms_outbox`.
- **Role Permissions:** Enforces the permission matrix (`config/permissions.php`) across `owner`, `manager`, `worker`, `accountant`, `viewer`. Workers are strictly prohibited from opening Finance or Admin modules.
- **CSRF Protection:** Every POST request is validated via `CsrfMiddleware`; invalid or expired tokens return HTTP 419.
- **Audit Logging:** Every create, update, delete, login, and logout writes to `audit_log` with sensitive fields redacted. Owner and manager can inspect the audit log with filters at `/admin/audit`.

### Key Computed SQL Views:
- **`v_flock_status`**: Event-sourced live flock status calculating `current_stock` (`start_count + additions - deaths - sales - culls - consumed`), cumulative `deaths_todate`, `mortality_rate_percent`, and age in days and weeks.
- **`v_incubation_jobs`**: Real-time batch calculation of `balance` (`cost - amount_paid`) and `hatch_percent` (`(hatched / eggs_incubated) * 100`, returns `NULL` when `eggs_incubated = 0` to prevent division-by-zero).
- **`v_customer_balances`**: Consolidates incubation and credit sales balances into aggregate customer debt/overpayment flags.
- **`v_cash_month_summary`**: Aggregates cash book receipts and expenditure by business unit and year-month with Excel reconciliation metrics.

---

## cPanel Deployment Guide
1. **Upload Files:**
   - Upload the project files to your cPanel hosting account.
   - For a primary domain, upload the contents so that `public/` is either mapped as the web root (e.g. `public_html`) or use the root `.htaccess` provided in this repository.
2. **Configure Web Root & .htaccess Protection:**
   - The root `.htaccess` ensures all requests are forwarded to `public/index.php` while blocking web access to `/app`, `/config`, `/storage`, `/database`, `/tests`, and `.env`.
3. **Database Configuration:**
   - In cPanel, create a MySQL database and user.
   - Grant all privileges to the user on the database.
   - In `.env`, set `DB_DATABASE`, `DB_USERNAME`, and `DB_PASSWORD`.
4. **Permissions:**
   - Ensure `storage/logs` and `storage/cache` have write permissions (0775 or 0755 depending on host setup).
5. **Set PHP Version:**
   - In cPanel MultiPHP Manager, select **PHP 8.2** (or 8.1+). Ensure extensions `pdo_mysql`, `mbstring`, `curl`, and `json` are enabled.
6. **Verify Installation:**
   - Visit `https://your-domain.com/health`.
   - Response will be: `{"ok":true,"db":true,"timestamp":"...","timezone":"Africa/Blantyre"}`.

---

## Phase 3 & Part A: Authentication, Family Roles & Single Active Session
- **Family Accounts:**
  - `ik.chipeta`: Owner / MD (Overseer of everything, sees full audit log, can export CSV, exclusive authority over owner role and MD account).
  - `isaac.chipeta`: Manager with `users.change_roles` permission (can manage users, force logout, reset passwords; cannot modify MD or assign owner role).
  - `sikizwayo`: Manager.
  - `mwawi`: Accountant.
  - `mtisunge`: Worker (operational data entry only, forbidden from Finance and Admin).
- **Single Active Session Per Account:**
  - Tracked in `user_sessions` with 15-minute active collision lock. Attempted logins on a second device within 15 minutes of activity are refused with a clear advisory and audited (`refused_second_session`).
  - Explicit logout immediately clears the active session record.
  - 30-minute idle timeout automatically ends inactive sessions.
  - Admin/MD can force-logout any user with an active session from Admin → Users.
- **Forced Password Change on First Login:**
  - Flag `must_change_password = 1` intercepts all post-login traffic and enforces setting a personal password of at least 10 characters.
- **Append-Only Audit Log:**
  - Enforced at database level via MariaDB/MySQL triggers (`trg_audit_log_no_update` and `trg_audit_log_no_delete`). Any attempt to alter or delete audit history fails with a database exception.

---

## Phase 10: PayChangu Online Payments Gateway
- **Checkout Initiation:** Generates unique `tx_ref` (`MSP-YYYYMMDD-<random>`), records pending transaction, and redirects to PayChangu checkout.
- **Cryptographic Webhook Verification:** Validates `HMAC-SHA256` signature via `hash_equals(hash_hmac('sha256', $body, $secret), $signature)`.
- **Idempotent Fulfillment:** Ensures each transaction is processed once only in `webhook_events`, automatically distributes 25/15/30/20/10 statutory allocations, creates cash book receipt, and dispatches `PAYMENT_RECEIPT` SMS.
- **Polling Return Screen:** Safe user return page with automatic verification polling.
- **Admin Payments Center:** Filterable transactions table with manual "Re-verify" button.

---

## Phase 11: Operational Dashboard & Reports
- **Executive Dashboard:** Live stock by breed, weekly mortality rates, active incubating batches with countdown timers, multi-business cash net values, and dynamic alerts.
- **8 Standardized Reports:**
  1. Monthly Profit & Loss (P&L) per Business Unit
  2. Mortality % per Flock
  3. Hatch Rate % by Breed / Customer / Month
  4. Egg Productivity & Tray Calculations
  5. Feed Cost Efficiency per Bird & Egg
  6. Aged Debtors (0-30, 31-60, 61-90, 90+ days)
  7. Sales Revenue Breakdown by Item
  8. Statutory Incubation Allocations (25/15/30/20/10)
- **Exporting:** Direct streaming CSV download and clean print stylesheets across all 8 reports.

---

## Phase 12: Mobile, PWA & Offline Sync
- **Web App Manifest:** `public/manifest.webmanifest` configured for standalone mobile installation with 192px and 512px icons.
- **Service Worker (`public/sw.js`):** Network-First caching for HTML pages, Cache-First for static assets, strictly bypassing authentication and write endpoints.
- **Offline Entry Queue (IndexedDB):** Stores deaths, eggs, and sales offline with client UUIDs.
- **Conflict-Safe Sync (`/api/sync`):** Idempotent batch processing with `offline_sync_log` ensuring zero duplicate entries upon network reconnection.
- **Visible Sync Indicator:** Real-time "Pending Sync (n)" badge in topbar with auto-sync on browser `online` event.

---

## Phase 13: Security Hardening & Nightly Backups
- **Security Headers:** Strict enforcement of `X-Frame-Options: SAMEORIGIN`, `X-Content-Type-Options: nosniff`, `Referrer-Policy: strict-origin-when-cross-origin`, and `Content-Security-Policy`.
- **Nightly Backup Cron (`cron/backup.php`):** Automated database dump with 14-day retention policy auto-purging old snapshots.
- **Owner Backup Dashboard:** `/admin/backups` screen allowing instantaneous snapshot creation and secure direct downloads.
- **Path Traversal Protection:** Whitelist regex validation on all downloadable backup filenames.
- **End-to-End System Test Suite:** Complete end-to-end simulation covering import, flock dynamics, daily entries, incubation intake, PayChangu payments, SMS queueing, reporting, and backup creation.

---

## Phase 14: Deployment & Reconciliation
- **Deployment Manual:** Complete cPanel and Linux installation guide in `DEPLOY.md`.
- **User & Operations Manual:** Comprehensive workflow guide for Owner, Manager, Worker, Accountant, and Viewer in `USER_GUIDE.md`.
- **Reconciliation Certification:** `DATA_RECONCILIATION_REPORT.md` validating all 42 Excel data pack files (13,454 rows) with zero unexplained differences.

  - Owner-only CSV export at `/admin/audit/export`.

---

## Phase 4: Legacy Data Pack Import Engine
The system includes a dedicated import service (`App\Services\ImportService`), web administration screen (`/admin/import`), and CLI runner (`cron/import.php`):

### Running Data Imports via CLI:
```powershell
# Dry Run (validation & reconciliation check without database writes)
& "C:\xampp\php\php.exe" cron/import.php incubation_01 --dry-run
& "C:\xampp\php\php.exe" cron/import.php incubation_02 --dry-run

# Live Import
& "C:\xampp\php\php.exe" cron/import.php incubation_01
& "C:\xampp\php\php.exe" cron/import.php incubation_02

# Import all Phase 4 files
& "C:\xampp\php\php.exe" cron/import.php all
```

### Phase 4 Reconciliation Results:
- **`incubation_01_batches_2023-2026.csv`**:
  - `rows_in_csv`: 981
  - `rows_imported`: 819 batches
  - `rows_skipped`: 162 rows (2 header/title, 45 month-end summaries, 1 year label, 114 empty/formula residues)
  - `reconciliation`: **PASS** (`981 = 819 + 162`)
  - `total_eggs_incubated`: 39,378
  - `total_cost`: MWK 7,401,445
  - `total_paid`: MWK 4,889,710
  - `customers_deduced`: 162 unique entities (Msantha flagged as `is_internal = 1`, phones normalized to E.164 `+265...`, location parsed, name variants stored in `legacy_names` JSON).
- **`incubation_02_charges_analysis_and_support_activities.csv`**:
  - `rows_in_csv`: 328
  - `rows_imported`: 225 records (220 receipt allocations + 5 supportive activities)
  - `rows_skipped`: 108 rows (3 header rows, 105 empty rows)
  - `reconciliation`: **PASS** (`328 = 220 + 5 + 103 empty + 3 headers`)
  - `total_paid_allocated`: MWK 2,554,855 split into Electricity (25%), Water (15%), Labour (30%), Telephone (20%), Profit (10%)
  - `total_activities_cost`: MWK 26,500 across 5 supportive activities.

---

## Testing & Quality Assurance
Run the complete automated test suite against the isolated test database:
```powershell
& "C:\xampp\php\php.exe" vendor/bin/phpunit
```
52 automated feature and unit tests prove:
- Single session collision lock & force logout.
- Forced password change on first login.
- Immutable append-only audit log triggers.
- Role permission matrix and access guards.
- Customer deduplication and phone/date parsing.
- 100% reconciliation of imported batches and allocations.
