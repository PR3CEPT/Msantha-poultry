# Msantha Poultry Management System — Production Deployment Guide (cPanel / Linux)

This guide provides step-by-step instructions for deploying the **Msantha Poultry Management System** to a shared cPanel hosting environment or dedicated Linux VPS running PHP 8.0 or PHP 8.2 with MariaDB/MySQL.

---

## 1. Prerequisites & Server Requirements

Ensure your cPanel hosting account or server meets the following specifications:
* **PHP Version**: PHP 8.0.30 or PHP 8.2 (tested and certified)
* **Required PHP Extensions**:
  * `pdo_mysql` (Database connectivity)
  * `sodium` / `argon2id` (Password hashing)
  * `curl` (PayChangu and SMS API communications)
  * `json` (API payloads and legacy ledger storage)
  * `mbstring` (UTF-8 text normalisation)
  * `fileinfo` (MIME-type validation for logo uploads)
* **Database**: MySQL 5.7+ or MariaDB 10.3+ with `utf8mb4` encoding support
* **Web Server**: Apache with `mod_rewrite` enabled
* **Memory Limit**: At least `128M` (256M recommended for large CSV imports)
* **Timezone**: Default configured to `Africa/Blantyre` (CAT / UTC+2)

---

## 2. Database Creation & User Setup

1. Log into your **cPanel Dashboard**.
2. Navigate to **MySQL Databases**.
3. Create a new database named `youruser_msantha_poultry`.
4. Create a new database user (e.g. `youruser_msantha_user`) with a strong password.
5. Add the user to the database and grant **ALL PRIVILEGES**.
6. Note the database name, username, and password for your `.env` file.

---

## 3. Upload Application Files & Directory Layout

1. Compress your project workspace into a zip archive (excluding `node_modules`, `.git`, or temporary cache).
2. Using **cPanel File Manager** or SFTP:
   * Recommended structure: place all application files in `/home/username/msantha_app/` (outside the public document root).
   * Put only the contents of `/public/` inside `/home/username/public_html/`.
   * Update `/home/username/public_html/index.php` to require `__DIR__ . '/../msantha_app/vendor/autoload.php'`.
3. If deploying directly to `public_html/`:
   * Ensure the `.htaccess` in the project root correctly restricts direct web access to `/app/`, `/config/`, `/database/`, `/storage/`, and `/.env`.
   * Direct all traffic to `public/index.php`.

---

## 4. Directory Permissions

Ensure the web server (e.g. `nobody`, `www-data`, or your cPanel user) has read and write permissions to the storage directories:
```bash
chmod -R 755 storage/
chmod -R 775 storage/cache/
chmod -R 775 storage/logs/
chmod -R 775 storage/backups/
chmod -R 775 storage/imports/
```

---

## 5. Environment Configuration (`.env`)

Copy `.env.example` to `.env` in the application root directory and configure:

```ini
APP_NAME="Msantha Poultry Management System"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://poultry.msantha.mw
APP_TIMEZONE=Africa/Blantyre
APP_LOCALE=en

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=youruser_msantha_poultry
DB_USERNAME=youruser_msantha_user
DB_PASSWORD="YourStrongDatabasePassword"
DB_CHARSET=utf8mb4

# PayChangu Gateway
# Use live keys for production payments
PAYCHANGU_SECRET_KEY=sec-live-xxxxxxxxxxxxxxxxxxxx
PAYCHANGU_WEBHOOK_SECRET=whsec-live-xxxxxxxxxxxxxxx
PAYCHANGU_RETURN_URL=https://poultry.msantha.mw/payments/return
PAYCHANGU_CALLBACK_URL=https://poultry.msantha.mw/webhooks/paychangu

# SMS Gateway (Africa's Talking / Generic HTTP)
SMS_PROVIDER=africas_talking
SMS_SENDER_ID=MSANTHA
SMS_API_KEY=your_live_africas_talking_api_key
SMS_USERNAME=your_africas_talking_username
```

---

## 6. Run Migrations & Historical Data Import

Access your server via SSH (or cPanel Terminal) and run:

### A. Run Database Migrations
```bash
php cron/migrate.php
```
*Applies all migrations (0001 through 0006) including views, indexes, month locks, password policies, and offline sync logs.*

### B. Import Historical Data Pack (42 Excel Source Files)
```bash
php cron/import.php all
```
*Imports customers, 837 incubation batches, flock sheets, daily ledgers, egg production, cash books, and bank movements with 100% reconciliation.*

---

## 7. SSL / HTTPS Security

1. In cPanel, navigate to **SSL/TLS Status**.
2. Run **AutoSSL** to generate a Let's Encrypt or cPanel Comodo certificate for your domain.
3. Verify that HTTPS redirection is active in `.htaccess`.

---

## 8. Webhook Configuration in PayChangu

1. Log into your **PayChangu Merchant Portal** ([https://paychangu.com](https://paychangu.com)).
2. Navigate to **Developers &rarr; Webhooks**.
3. Set the **Webhook URL** to:
   ```
   https://poultry.msantha.mw/webhooks/paychangu
   ```
4. Copy the Webhook Secret and set it as `PAYCHANGU_WEBHOOK_SECRET` in your `.env`.

---

## 9. Automated Scheduled Tasks (Cron Jobs)

Set up the following cron entries in **cPanel &rarr; Cron Jobs**:

| Schedule | Frequency | Command | Description |
| :--- | :--- | :--- | :--- |
| `* * * * *` | Every Minute | `php /home/username/msantha_app/cron/sms_queue.php >/dev/null 2>&1` | Processes queued SMS dispatches, retries, and delivery statuses |
| `0 6 * * *` | Daily at 06:00 | `php /home/username/msantha_app/cron/daily_reminders.php >/dev/null 2>&1` | Sends egg transfer, hatch collection, and debtor reminders |
| `0 2 * * *` | Daily at 02:00 | `php /home/username/msantha_app/cron/backup.php >/dev/null 2>&1` | Creates database backup snapshot and purges files older than 14 days |

---

## 10. Initial Logins & Family Accounts Setup

The system includes pre-configured family roles:
* `ik.chipeta` — Owner (Full authority across all businesses)
* `isaac.chipeta` — Manager (Operations, customer merge, role assignments)
* `precious.chipeta` — Worker (Daily egg entry, flock mortality, offline sync)
* `faith.chipeta` — Accountant (Financial receipts, cash book, month-end lock)
* `esther.chipeta` — Viewer (Read-only farm reporting)

> [!IMPORTANT]
> All users must sign in with their temporary credentials and will be prompted to choose their personal 10+ character password before accessing the system.

