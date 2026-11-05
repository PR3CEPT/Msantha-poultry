# Msantha Poultry Management System — Analysis & Blueprint

Prepared from the four Excel workbooks (2022 – Dec 2026). Every statement about the data below was read from the files, not assumed.
Companion files: `02_ANTIGRAVITY_PROMPTS.md` (build prompts), `03_DATA_QUALITY_LOG.md` (problems found in the Excel data), `schema.sql` (database), `data_pack/` (42 CSVs, one per sheet).

---

## 1. What the Excel files tell us the business actually does

| Workbook | Sheets | What it records | Becomes system module |
|---|---|---|---|
| Poultry Incubation Services Analysis Table | 2 | Hatching service for customers: **837 egg batches** (2023→2026), about **222 differently-typed customer names**, eggs in, fertile/infertile, hatched, hatch %, cost, paid, balance. 2026 sheet splits each receipt into electricity / water / labour / telephone / profit, plus a "supportive activities" list. | Incubation & Customers |
| DYNAMICS OF TURKEYS, DUCKS, CHICKENS 2026 | 28 | **28 flock batches** (1 turkey, 3 local chicken, 8 Mikolongwe, 8 Sasso, 3 quail, 3 Pekin duck, 2 Khaki Campbell). One row per day: day #, week, month, stock, deaths, deaths-to-date, sold, balance, **age-based price** (price rises as birds grow), remarks (vaccinations such as Gumboro, NCD). Current egg-tray prices are in each sheet. | Flocks, Mortality, Sales, Price ladder, Vaccination |
| POULTRY FARMING TRANSACTIONS | 5 | **Daily ledger** 2022, 2023, 2024-25(→2026): balances by breed, egg production & eggs incubated, feed bought (kg + MK), drugs, sales (birds, fertilised eggs, stale eggs, quantity + value), cash available/used/banked/withdrawn/bank balance, deaths, births, remarks. Plus a daily **quail egg production** sheet. | Daily Ledger, Eggs, Feed, Sales, Cash/Bank |
| CASH BOOK SOLUTIONS 2023 | 6 | Cash book (receipts / expenditure, month-end totals) for **6 business lines**: poultry, bee farming, and 4 car-hire vehicles (MZ 9350, NA 7153, MZ 8170, MJ 4324). | Finance (multi-business cash book) |

Total in the data pack: **180,870 non-empty cells across 42 sheets**, reconciled to the Excel files with zero differences.

### Business rules discovered (to hard-code as configurable settings)
1. **Incubation charge = eggs × rate per egg.** The rate has risen: ≈ MK120 (2023) → 150–170 (2024) → 190 (2025) → 200–250 (2026). Store rate history; never overwrite old rates.
2. **Hatch % = hatched ÷ eggs incubated.** Across all customer batches roughly 45% (≈17,300 hatched of ≈38,200 incubated) — the system should show this per customer, per breed, per month.
3. **Balance = cost − amount paid.** Excel has negative balances (overpayment) and unexplained notes in the balance column; the system keeps payments as separate rows so a balance can never drift.
4. **Hatch timing:** chicken eggs move to the hatching tray 18 days after setting and hatch on day 21 (seen as 11.08 → 29.08 → 01.09.2023). Turkey/duck 28 days, quail ≈ 18 days. Drives SMS reminders.
5. **Receipt allocation (2026):** each incubation receipt is split 25% electricity, 15% water, 30% labour, 20% telephone, 10% profit (e.g. MK12,000 → 3,000 / 1,800 / 3,600 / 2,400 / 1,200). Configurable.
6. **Age-based price ladder:** price per bird steps up with weeks of age (e.g. turkey poult MK12,000 at day 1 → 35,000 at week 5). Store as a ladder per breed with effective dates.
7. **Egg-tray prices (30 eggs):** Mikolongwe 22,500 → 27,000; Sasso 25,000 → 30,000; quail 10,000 (earlier K275/egg, 8,250/tray); duck 35,000 → 40,000.
8. **"Msantha" as a customer = the farm's own eggs** (200 of 837 batches). Mark as internal so it never creates a debt or an SMS.
9. **Mortality**: deaths and deaths-to-date per batch, per day — feed a mortality % KPI and alerts.

---

## 2. Problems in the Excel data → how the system fixes them

| Problem in Excel | Evidence | System improvement |
|---|---|---|
| One sheet per batch, copied from a template | 28 near-identical sheets | One `flocks` table + `flock_events`; add a batch in 10 seconds. |
| Daily balances typed/carried by formula | stock = previous − deaths − sold | Balance is **computed from events**, never typed. |
| Template pre-filled to 31-Dec-2026 | all batch sheets run to 31 Dec 2026 although today is 3 Nov 2026 | Import marks rows after the cut-off as `is_projected`; no fake history. |
| Year/date errors | 2023 dates inside 2025/26 sheets, 1980 date in QUAILS 22, `24.10.20204`, `22.05.2026+` (full list in the data-quality log) | Real DATE columns, validation, `date_suspect` flag kept for review. |
| Customers retyped each time | 222 spellings, phone typos (883119711 / 88119711), two numbers in one cell | `customers` master with E.164 phone, merge tool, `legacy_names` kept. |
| Text dates in 4 formats (`1.12.2023`, `05.12.2023`…) | cash book, incubation | Parsed once at import; stored as DATE. |
| Money and counts in the same wide sheet (up to 95 columns) | ledger 2024-25 | Normalised tables: sales, feed, cash, bank, egg production. Original wide row kept verbatim in `legacy_ledger_daily`. |
| No customer communication | customers phone/ask balance by hand | SMS at every stage; PayChangu pay-link. |
| Cash book has 6 businesses in 6 tabs, hidden helper columns | hidden cols E–I on poultry book | One `cash_entries` table with `business_unit`; owner dashboard per business. |
| No audit trail | anyone can edit a cell | Roles, audit log, soft locks on closed months. |

---

## 3. Target system

### 3.1 Stack (chosen for cheap Malawi hosting, works on a phone)
- **PHP 8.2+**, no heavy framework: small MVC (front controller, router, PDO, Twig-free plain templates) — runs on any cPanel host.
- **MariaDB/MySQL 8** (`schema.sql`), InnoDB, utf8mb4. Money = integer MWK.
- **Frontend:** HTML5, Bootstrap 5.3, vanilla JavaScript (ES modules), Chart.js. **Mobile-first**, one-hand forms, big buttons, numeric keypad inputs.
- **PWA:** manifest + service worker; installable on Android; **offline queue** so farm workers can record deaths/eggs without network and sync later (IndexedDB queue → `/api/sync`).
- **Cron:** one line per minute runs the SMS queue and daily reminders.
- Composer packages kept minimal: `vlucas/phpdotenv`, `phpunit/phpunit` (dev). Use PHP cURL for gateways.

### 3.2 Folder layout (fixed — every prompt must use it)
```
/public            index.php, assets/, manifest.webmanifest, sw.js, uploads/ (logo)
/app
  /Core            Router, Request, Response, DB, Auth, Csrf, Validator, View, Logger
  /Controllers     one per module
  /Models          thin PDO models
  /Services        SmsService, PayChanguService, ImportService, ReportService, PricingService
  /Views           layouts + module views (mobile-first)
/config            app.php, database.php (read from .env)
/database          schema.sql, seeds/, migrations/
/storage           logs/, imports/ (CSV data pack), cache/
/tests             PHPUnit
/cron              sms_queue.php, daily_reminders.php
```

### 3.3 Modules (screens)
1. **Dashboard** — live stock by breed, deaths this week, eggs this week, incubating batches with hatch countdown, cash today / this month, unpaid incubation balances, alerts.
2. **Flocks** — list, add batch, daily quick-entry (deaths / sold / added / vaccinated), batch profile with chart of stock & mortality, close batch.
3. **Eggs** — daily collection per breed/flock (broken, consumed, sold, kept, incubated), tray calculator.
4. **Incubation service** — receive eggs, set incubator, auto-dates (transfer, hatch), record fertile/infertile/hatched, hatch %, payments, balance, customer SMS, printable receipt/hatch certificate.
5. **Customers** — master list, merge duplicates, history, balance, SMS opt-in.
6. **Sales** — live birds, fertilised eggs, table eggs, stale eggs; credit sales; receipts (PDF/print).
7. **Feed & drugs** — purchases (kg, MK), consumption estimate per flock, cost per bird / per egg.
8. **Finance** — cash book for all 6 business lines, month-end close, bank deposits/withdrawals, incubation receipt allocation (25/15/30/20/10), supportive activities, profit & loss per business.
9. **Reports** — monthly P&L, mortality %, hatch % by breed/customer, feed conversion, egg productivity, aged debtors; export CSV/Excel/PDF.
10. **SMS centre** — templates, queue, delivery log, bulk message to selected customers.
11. **Payments** — PayChangu transactions, reconciliation.
12. **Admin** — users/roles, settings (rates, tray prices, allocation %), audit log, data import with reconciliation report, backups.

### 3.3b Roles
Owner (all) · Manager (all except users/settings) · Worker (daily entry only — mobile) · Accountant (finance, read others) · Viewer (read-only).

### 3.4 Improvements beyond the spreadsheets (recommended)
- **Event-sourced stock** (no typed balances) + automatic *deaths-to-date*, weeks/months of age.
- **Vaccination calendar** per species (Gumboro, Lasota, Hitchner, NCD…) with SMS/in-app reminders — the Excel remarks show these are done manually.
- **Hatch countdown & transfer-day alerts** (day 18 → tray, day 21 → hatch).
- **Mortality alert** when daily deaths exceed a threshold (default 2% of flock).
- **Feed cost per bird/egg** and **profit per batch**.
- **Debtors ageing** with automatic reminders.
- **Duplicate-customer detection** by phone number.
- **Month locking**: closed months can't be edited without the owner.
- **Backups**: nightly DB dump + weekly download link for owner.
- **Multi-language labels** ready (English + Chichewa) via a language file.
- **Print/PDF** receipts with logo.

---

## 4. SMS notifications

- Provider is **pluggable** (`SmsProviderInterface`): ship with **Africa's Talking** and a generic **HTTP-gateway** adapter; the owner picks the provider in Settings. *Confirm which provider and Sender ID you will register in Malawi (Airtel/TNM) before go-live — this is a business decision, not a code one.*
- Every message goes through **`sms_outbox`** (queue) → cron sends → status updated → retries (max 3) → failures visible in the SMS centre.
- `dedupe_key` (e.g. `HATCH_READY:job-123`) guarantees a customer never gets the same alert twice.
- Phones normalised to E.164 (`099…` → `+26599…`, `088…` → `+26588…`).

| Trigger | Template | To |
|---|---|---|
| Eggs received | JOB_RECEIVED | customer |
| Day 18 (transfer) | TRANSFER_DAY | customer |
| Hatch completed | HATCH_READY | customer |
| Payment recorded / PayChangu success | PAYMENT_RECEIPT | customer |
| Balance unpaid (7, 14, 30 days) | BALANCE_REMINDER (with pay link) | customer |
| Vaccine due | VACCINE_DUE | manager/worker |
| High mortality / low stock | LOW_STOCK | owner |
| Daily summary (optional) | — | owner |

---

## 5. PayChangu integration (checked against developer.paychangu.com)

**Initiate** — `POST https://api.paychangu.com/payment`, headers `Accept: application/json`, `Authorization: Bearer {SECRET_KEY}`, JSON body: `amount`, `currency` (`MWK`), `tx_ref` (our unique reference), `callback_url`, `return_url`, `first_name`, `last_name`, `email` (optional), `customization{title,description}`, `meta`. Response: `data.checkout_url` → redirect/open it (or send it in an SMS link).
**Verify (mandatory before giving value)** — `GET https://api.paychangu.com/verify-payment/{tx_ref}` with the same bearer key. Accept only if: `tx_ref` matches, `status` = `success`, `currency` = `MWK`, `amount` ≥ the expected amount.
**Webhook** — PayChangu POSTs JSON with header `Signature` = HMAC-SHA256 of the **raw body** using the webhook secret. Verify with `hash_equals(hash_hmac('sha256', $rawBody, $secret), $_SERVER['HTTP_SIGNATURE'])`; reply **HTTP 200** quickly (any other code → retried 3 times, 30 min apart). Store each event once (`webhook_events`), then **re-verify via the verify endpoint** before marking paid.
**Rules the code must follow:** keys only in `.env`; never trust the browser redirect alone; idempotent processing; on success create `incubation_payments`/`sales` payment rows + allocation + SMS receipt; log raw verify/webhook JSON in `payments`.
Callback/webhook URLs must be public HTTPS (use the real domain, not localhost).

---

## 6. Data migration (nothing may be lost)

| Source CSV(s) in data_pack | Goes to | Rule |
|---|---|---|
| `incubation_01_…` | `customers`, `incubation_jobs`, `incubation_payments` | one job per dated row; "… END" rows are month totals → skipped from jobs but kept in `import_log`/legacy note; derive customer by normalised name + phone; "Msantha" ⇒ `is_internal`. |
| `incubation_02_…` | `incubation_payments`, `incubation_allocations`, `supportive_activities` | columns H:Q are the allocation of each receipt; S:V is the activities table. |
| `flock_01…28` | `flocks`, `flock_events`, `flock_daily_legacy`, `price_ladder` | every daily row → `flock_daily_legacy` verbatim (flag `date_suspect`, `is_projected`); events derived where deaths/sold > 0 or remarks exist; first row gives start count/date. |
| `ledger_2022/23/24/25/26` | `legacy_ledger_daily` (full row JSON) + normalised `egg_production_daily`, `feed_purchases`, `drug_purchases`, `sales/sale_items`, `bank_movements` | month label carried forward; blank Day on month's first row is filled from sequence; rows after last real activity not imported as events. |
| `quail_egg_production…` | `egg_production_daily` | |
| `cashbook_01…06` | `cash_entries` | one business unit per file; "MARCH MONTH END" rows → `entry_kind=month_total`; rows without date kept (`date_missing=1`); hidden helper columns kept in `import_log` detail. |

**Reconciliation (must pass before go-live):** for every CSV the importer prints `rows_in_csv = rows_imported + rows_skipped` and lists every skipped row with a reason; money totals per month in the system must equal the Excel month totals (cash book "… END" rows and ledger totals).

---

## 7. Security & quality checklist
Argon2id passwords · CSRF tokens on every POST · prepared statements only · output escaping · session cookies `HttpOnly; Secure; SameSite=Lax` · login throttling · role checks in every controller · upload validation (logo) · `.env` outside web root · HTTPS only · audit log · PHPUnit tests for pricing, balance, import reconciliation, PayChangu signature, SMS dedupe.

## 8. Items I need from you (not in the files)
1. **Logo file** — it was not attached to the chat; please re-send (PNG/SVG, ideally square 512×512).
2. SMS provider choice + registered Sender ID, and the PayChangu **secret key, webhook secret** (test keys first).
3. Domain/hosting details (cPanel? PHP version?).
4. Who are the users and their roles?
5. Confirm: should the "Msantha" customer always be treated as own-use eggs?
6. Confirm the current incubation rate per egg (2026 sheets show 200 and 250) and whether bulk/discount rates exist (some rows imply e.g. MK138/egg).
