# Antigravity Prompt Pack — Msantha Poultry Management System

**How to use (important, avoids the "input token count exceeds 1048576" error)**
1. Create a new project. Put these in the workspace: `01_SYSTEM_ANALYSIS_AND_BLUEPRINT.md`, `03_DATA_QUALITY_LOG.md`, `schema.sql`, and the folder `data_pack/` (42 small CSVs, ≈300k tokens in total — never attach them all in one message; let the agent read them **from disk**, one at a time).
2. Paste **PROMPT 0 (Master Rules)** at the start of every new chat/session, then paste one phase prompt at a time (1 → 14). Do not skip phases or reorder them.
3. Each phase ends with a **Definition of Done**. Ask the agent to print the checklist and prove each item before moving on.
4. Attach the **logo** to the workspace as `public/assets/img/logo.png` (and generate favicon / PWA icons 192, 512 from it).

---
## PROMPT 0 — MASTER RULES (paste first, every session)
```
You are the lead engineer building the "Msantha Poultry Management System" for a Malawian poultry farm (Msantha). Follow these rules in EVERY task, without exception.

PRODUCT
- Web app in PHP 8.2 + MySQL/MariaDB + HTML5 + vanilla JavaScript (ES modules) + Bootstrap 5.3 + Chart.js. Mobile-first PWA that also works offline for daily entry. Currency: Malawi Kwacha (MWK), integers only. Timezone Africa/Blantyre. Dates shown dd-Mon-yyyy, stored as DATE.
- Read the specification files first: 01_SYSTEM_ANALYSIS_AND_BLUEPRINT.md, 03_DATA_QUALITY_LOG.md, schema.sql. They are the source of truth. If something is not in them, ask me — do not invent business rules.
- The real data is in data_pack/*.csv (every row carries `excel_row`). Never edit those files. Read them from disk one file at a time; never paste them whole into the chat.

ARCHITECTURE (fixed — do not change)
- Folders exactly as in section 3.2 of the blueprint (/public /app/{Core,Controllers,Models,Services,Views} /config /database /storage /tests /cron).
- Front controller public/index.php, router in app/Core, PDO prepared statements only, no raw SQL string concatenation, no global state, PSR-12 style, `declare(strict_types=1);` in every PHP file, namespaces `App\...`, one class per file, class = file name.
- Config only from .env (phpdotenv). Never hard-code keys, passwords, URLs.
- Naming: tables/columns snake_case (as in schema.sql), classes PascalCase, methods camelCase, routes kebab-case (/incubation-jobs), JS files kebab-case.

UNIFORM UI RULES (every screen)
- Same layout (app/Views/layouts/main.php): top bar with logo + page title, bottom navigation on mobile (Home, Flocks, Eggs, Incubation, More), sidebar on desktop ≥ 992px.
- Same components: list page = search + filters + cards on mobile / table on desktop; form page = single column on mobile, labels above inputs, big touch targets (≥ 44px), numeric inputs use inputmode="numeric", date inputs native; one primary button (green), destructive actions red with confirm dialog.
- Same states on every page: loading spinner, empty state with a hint, error alert, success toast. Same validation messages (server-side always; client-side as a convenience).
- Brand colours taken from the logo; define them once as CSS variables in public/assets/css/app.css. Dark text on light cards, minimum contrast 4.5:1.

UNIFORM CODE RULES
- Every POST: CSRF token + role check (Auth::requireRole) + Validator + audit_log entry. Every list endpoint: pagination (default 25) and sort whitelist.
- Money in integer MWK. Percentages and balances are COMPUTED, never stored (hatch % = hatched/eggs_incubated, balance = cost − paid, stock = start + additions − deaths − sales − culls).
- Phone numbers normalised to E.164 (+265…) by one helper (App\Core\Phone). Dates parsed by one helper (App\Core\DateParser).
- Errors logged to storage/logs, never shown to users in production. All user-facing text in app/lang/en.php (Chichewa file prepared, empty).
- Every feature ships with PHPUnit tests for its business rules and a short section in README.md.

DATA RULES
- NEVER lose a record. Import is idempotent (re-running does not duplicate: use legacy_ref / UNIQUE keys). Every skipped or flagged row is written to import_log with the reason. After each import print: rows_in_csv, rows_imported, rows_skipped, and money totals; they must reconcile.
- Keep original values; flag suspicious data (date_suspect, date_missing, is_projected) instead of "fixing" it silently.
- Import cut-off date = today's date at import time. Rows after the cut-off that are only carry-forward/zero formulas are projections (is_projected = 1).

WORKFLOW (every task)
1. Restate the task in 3 lines. 2. List the files you will create/change. 3. Implement. 4. Run the tests/linters and show the output. 5. Print the Definition-of-Done checklist with ✔/✘ and evidence. 6. Stop and wait for the next prompt.
Do not start the next phase on your own. Do not refactor earlier phases unless a test fails.
```

---
## PROMPT 1 — Scaffold & environment
```
PHASE 1 – Project scaffold.
Create the folder structure from the blueprint (section 3.2). Implement: front controller, Router (GET/POST, route params, middleware), Request/Response, View renderer with layout, DB (PDO singleton, utf8mb4, exceptions), Config/.env loader, Csrf, Logger, Validator, Phone and DateParser helpers (with unit tests for: 0999899620 → +265999899620, 0888… → +265888…, '1.12.2023' and '05.12.2023' → 2023-12-01 / 2023-12-05, '24.10.20204' → null+flag, '22.05.2026+' → 2026-05-22 + flag).
Add .env.example (APP_URL, DB_*, PAYCHANGU_*, SMS_*), .htaccess for cPanel (route everything to public/index.php, block /app /config /storage), composer.json (phpdotenv, phpunit), README with install steps for cPanel.
DoD: `composer test` passes; GET / shows a styled placeholder with the logo; /health returns JSON {ok:true,db:true}.
```

## PROMPT 2 — Database & migrations
```
PHASE 2 – Database.
Use database/schema.sql exactly as the base. Create a migration runner (php cli: `php cron/migrate.php`) that applies database/migrations/*.sql in order and records them in a `migrations` table. Make schema.sql the first migration. Add seeders for roles, species, breeds, business_units, feed_types, sms_templates, incubation_allocation_rules and ONE owner user whose password is set via CLI prompt (never in code). Create SQL VIEWS: v_flock_status (current stock, deaths todate, age in days/weeks, sold), v_incubation_jobs (hatch_percent, balance, days_to_hatch), v_customer_balances, v_cash_month_summary.
Add any missing index you find needed for the screens in the blueprint, but do NOT rename or drop columns from schema.sql; propose changes first.
DoD: fresh DB created from zero with one command; all views return rows on empty data without errors; tests prove balance = cost − paid and hatch % math (including 0 eggs → null, not division error).
```

## PROMPT 3 — Auth, roles, audit
```
PHASE 3 – Authentication & roles.
Login (username or phone + password, Argon2id), logout, change password, forgot-password by SMS code (6 digits, 10 min, 3 attempts), session hardening (HttpOnly, Secure, SameSite=Lax, regenerate on login), login throttling (5 fails → 15 min). Roles: owner, manager, worker, accountant, viewer with a permission matrix in config/permissions.php (module × action) enforced by Auth::requireRole/can. Admin → Users CRUD (owner only). Audit log written on every create/update/delete and visible in Admin → Audit with filters.
DoD: tests for each role's allowed/denied screens; a worker cannot open Finance or Admin; CSRF failure returns 419; audit rows exist for every write.
```

## PROMPT 4 — Data import engine (reads data_pack)
```
PHASE 4 – Import engine.
Build ImportService + Admin → Data import screen + CLI `php cron/import.php <file|all> [--dry-run]`. Read CSVs from storage/imports/ (copy data_pack there). Implement importers ONE PER SOURCE following blueprint section 6 and data-quality log:
 4a incubation_01 → customers (normalised by name+phone, legacy_names JSON, is_internal for "Msantha"), incubation_jobs (837 dated rows; skip "… END"/"YEAR" label rows but log them), incubation_payments (amount_paid), status derived from dates.
 4b incubation_02 → payments + allocations (cols H:Q) and supportive_activities (S:V).
 4c flock_01…28 → flocks (batch_code from sheet name, breed from name, start date/count from first day row), flock_daily_legacy (EVERY row), flock_events (deaths/sold/remarks), price_ladder, tray price from the J4/J-note.
 4d ledger_2022…2026 → legacy_ledger_daily (EVERY row, values_json), plus egg_production_daily, feed_purchases, drug_purchases, sales/sale_items, bank_movements from the right columns (use the column letters in the CSV headers).
 4e quail_egg_production → egg_production_daily.
 4f cashbook_01…06 → cash_entries (one business unit per file; month-end rows → month_total; missing dates kept with date_missing=1).
Each importer: idempotent, transactional per file, writes import_log, prints reconciliation (rows_in_csv = imported + skipped, with each skipped row + reason). Do 4a first, show the report, then continue one by one.
DoD: after importing ALL, the totals match this table (assert them in a test): non-empty cells per file = MANIFEST.csv `non_empty_cells`; row counts per file = MANIFEST.csv `data_rows`; cash-book month totals equal the "… END" rows; ledger legacy rows = ledger CSV rows. Running import twice creates no duplicates.
```

## PROMPT 5 — Customers & Incubation module
```
PHASE 5 – Customers and Incubation service.
Customers: list/search (name, phone), add/edit, merge duplicates (move all jobs/payments/SMS to the kept customer, keep legacy_names), history tab, balance, SMS opt-in, internal flag.
Incubation: "Receive eggs" form (customer lookup-or-create, breed, eggs received, eggs incubated, rate per egg default from settings, auto cost). Auto dates by species: chicken transfer day 18 / hatch day 21, turkey & duck 28, quail 18 (config in species table). Steps: received → incubating → hatching → completed → collected. Record infertile/fertile/hatched, hatch % (computed), payments (cash/Airtel/TNM/bank/PayChangu) with automatic allocation 25/15/30/20/10 into incubation_allocations, balance (computed), printable receipt and hatch certificate with logo. Dashboard widgets: "Transfer today", "Hatching soon (3 days)", "Unpaid balances".
DoD: imported 837 jobs display correctly; tests: cost = eggs×rate, allocation sums exactly to the payment (rounding remainder goes to profit), hatch date math for each species, overpayment shows negative balance and is flagged.
```

## PROMPT 6 — Flocks, mortality, vaccination
```
PHASE 6 – Flocks.
Flock list (cards on mobile), add batch (breed, start date, start count, source, purpose), QUICK DAILY ENTRY screen (one screen per flock or all flocks in a list: deaths, sold (qty, price, customer), added, culled, consumed, vaccination, note). Stock = computed from events (v_flock_status). Age in days/weeks/months computed. Price ladder: PricingService returns the price for a breed at an age on a date; manager edits ladder. Mortality % and alerts (threshold setting, default 2%/day). Vaccination schedule per species with reminders (due list + SMS to manager). Batch profile: chart of stock and cumulative deaths, events timeline, close batch.
DoD: for 5 imported flocks (one per species) the computed daily balance equals the Excel "Balance in stock" column for every non-projected day (write a test that compares against flock_daily_legacy and prints any difference).
```

## PROMPT 7 — Eggs, feed, drugs, sales
```
PHASE 7 – Eggs, feed, drugs, sales.
Egg production daily (collected, broken, consumed, sold, incubated, kept, cumulative) per breed/flock; tray calculator (30 eggs; breed tray price). Feed purchases (type, kg, cost) and drug purchases; feed cost per bird/egg report. Sales: live birds, fertilised eggs, table eggs, stale eggs; customer optional; cash/credit/mobile money; receipt PDF/print; sale_items → totals; credit sales create a debtor. All forms use the uniform UI rules. 
DoD: tests for totals and cumulative eggs; imported ledger sales tie to legacy_ledger_daily totals per month (print any month that differs).
```

## PROMPT 8 — Finance: multi-business cash book
```
PHASE 8 – Finance.
Cash book screen per business unit (POULTRY, BEE, CAR_MZ9350, CAR_NA7153, CAR_MZ8170, CAR_MJ4324): add receipt/expenditure, category, running balance, month summary, month-end close (locks the month; reopen = owner only). Bank deposits/withdrawals and balance. Incubation receipts auto-appear in the POULTRY book with their allocation (electricity/water/labour/telephone/profit). Profit & loss per business per month, owner dashboard card per business. Export CSV/PDF.
DoD: month totals in the system equal the Excel "… END" rows for every month where they exist (print a reconciliation table); locked month rejects edits.
```

## PROMPT 9 — SMS notifications
```
PHASE 9 – SMS.
Implement SmsProviderInterface with adapters: AfricasTalkingProvider and GenericHttpProvider (configurable URL/headers/body in settings); Settings → SMS (provider, sender ID, test button). SmsService::queue(template, phone, vars, dedupe_key) writes sms_outbox; cron/sms_queue.php (every minute) sends queued messages with retry (3 attempts, backoff) and updates status; delivery-report webhook endpoint (if provider supports). Templates editable in Admin (placeholders {eggs} {date} {hatch_date} {cost} {balance} {amount} {pay_link} {vaccine} {batch}). Triggers from blueprint section 4 via cron/daily_reminders.php (transfer day, hatch ready, balance reminders at 7/14/30 days, vaccine due, mortality alert) and on events (job received, payment received). Never SMS internal customers or opted-out customers. SMS centre: log, filter, resend failed, bulk message to selected customers with cost estimate (segments of 160 chars).
DoD: tests with a fake provider: dedupe prevents duplicates, opt-out respected, failure retries 3 times then 'failed', phone normalisation applied, no secret in logs.
```

## PROMPT 10 — PayChangu payments
```
PHASE 10 – PayChangu.
Implement PayChanguService exactly as in blueprint section 5 (verified against developer.paychangu.com):
- initiate: POST https://api.paychangu.com/payment with Authorization: Bearer PAYCHANGU_SECRET_KEY, JSON {amount, currency:'MWK', tx_ref (unique, e.g. MSP-YYYYMMDD-<random>), callback_url, return_url, first_name, last_name, email?, customization{title,description}, meta{purpose,purpose_id}}; store a `payments` row (pending) and return data.checkout_url.
- verify: GET https://api.paychangu.com/verify-payment/{tx_ref}; accept ONLY if tx_ref matches, status == 'success', currency == 'MWK', amount >= expected.
- webhook: POST /webhooks/paychangu — read RAW body, compute hash_hmac('sha256', $raw, PAYCHANGU_WEBHOOK_SECRET), compare with the `Signature` header using hash_equals; store the event once in webhook_events (idempotent), respond HTTP 200 immediately, then re-verify with the verify endpoint before marking paid.
- callback_url/return_url pages: never trust the redirect; show "checking payment…" and poll verify.
- On success (once only): mark payments.success, create incubation_payments / sales payment, run the 25/15/30/20/10 allocation, queue PAYMENT_RECEIPT SMS, write audit_log.
- UI: "Pay online" button on a job/sale, "Send pay link by SMS" button, Admin → Payments with status filter and "Re-verify" button. Use PayChangu TEST keys by default (APP_ENV=test) and a clear LIVE switch for the owner.
DoD: tests with mocked HTTP: valid signature accepted, tampered body rejected, duplicate webhook processed once, amount-too-low rejected, currency mismatch rejected, success creates exactly one payment row and one SMS.
```

## PROMPT 11 — Dashboard & reports
```
PHASE 11 – Dashboard and reports.
Dashboard cards: live stock by breed, deaths this week, eggs this week, incubating batches + countdown, cash today/month per business, unpaid balances total, alerts. Reports (filter by date range, breed, flock, customer; export CSV/PDF): monthly P&L, mortality % per flock, hatch % by breed/customer/month, egg productivity, feed cost per bird/egg, aged debtors, sales by item, incubation income by allocation category.
DoD: every report has a test with known numbers from the imported data; charts readable at 360px width.
```

## PROMPT 12 — Mobile, PWA, offline
```
PHASE 12 – Mobile & offline.
Add manifest.webmanifest (name, short_name, theme colour from logo, icons 192/512 generated from the logo), service worker (cache shell + static assets, network-first for pages, never cache POST/auth), offline "daily entry" queue in IndexedDB for deaths, eggs and sales with a visible "pending sync (n)" badge and conflict-safe /api/sync (client UUID per record → idempotent). Add install prompt hint. Test at 360×640, 390×844 and 768×1024. Lighthouse PWA and accessibility ≥ 90.
DoD: screenshot proof of each main screen at 360px; airplane-mode entry syncs correctly once online with no duplicates.
```

## PROMPT 13 — Security, QA, backups
```
PHASE 13 – Hardening.
Run a security checklist: SQL injection (all queries prepared), XSS (escape all output), CSRF everywhere, session fixation, brute force, file upload (logo only: type+size), secure headers (CSP, X-Frame-Options, Referrer-Policy), .env not web-accessible, directory listing off, role bypass tests. Add nightly mysqldump cron with 14-day retention and an owner-only "Download backup" button. Add a full-system end-to-end test script: import → create flock → daily entry → receive eggs → pay by PayChangu (mock) → SMS queued → report. Fix every finding.
DoD: written security report with each item ✔/✘ and evidence; all tests green.
```

## PROMPT 14 — Deployment & hand-over
```
PHASE 14 – Deployment.
Write DEPLOY.md for cPanel shared hosting (PHP 8.2, create DB, upload, .env, run migrate + import, cron lines, SSL, set PayChangu callback/webhook URLs, SMS sender ID), a USER_GUIDE.md with screenshots for Owner/Manager/Worker, and a final "Data reconciliation report" PDF listing every source file, rows in, rows imported, rows skipped (with reasons) and total MWK per month vs Excel.
DoD: a fresh install on a blank host following DEPLOY.md only works; the reconciliation report shows 0 unexplained differences.
```

---
## UNIFORMITY CHECK (paste after every phase)
```
Run the uniformity audit on everything built so far and print a table (✔/✘ + file:line):
1 strict_types + namespace + PSR-12 in every PHP file  2 prepared statements only  3 CSRF + role check + audit on every POST  4 pagination + sort whitelist on every list  5 layout, buttons, empty/loading/error states identical across screens  6 money = int MWK, no stored percentages/balances  7 phones via Phone helper, dates via DateParser  8 all strings in lang file  9 tests exist for each business rule  10 README section updated.
Fix every ✘ before continuing.
```

## WHEN THE AGENT HITS A TOKEN LIMIT
- Never attach more than ~2 CSV files in one message; tell the agent: "Read data_pack/<file>.csv from disk with a script, summarise column meaning, and write the importer — do not print the file."
- If a single file is still too large, ask it to read it in chunks of 200 rows (`head`/`tail` or a Python loop) and keep a running notes file `docs/import-notes.md`.
