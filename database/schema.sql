-- Msantha Poultry Management System - MySQL 8 / MariaDB 10.5+ schema (InnoDB, utf8mb4)
-- Money is stored as integer Malawi Kwacha (MWK) - never floats.
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ---------- Security & users ----------
CREATE TABLE roles (
  id TINYINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  code VARCHAR(30) NOT NULL UNIQUE,           -- owner, manager, worker, accountant, viewer
  name VARCHAR(60) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE users (
  id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  role_id TINYINT UNSIGNED NOT NULL,
  full_name VARCHAR(120) NOT NULL,
  phone VARCHAR(20) NULL,                     -- E.164, e.g. +265999899620
  email VARCHAR(150) NULL UNIQUE,
  username VARCHAR(60) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,        -- password_hash(PASSWORD_ARGON2ID)
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  last_login_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (role_id) REFERENCES roles(id)
) ENGINE=InnoDB;

CREATE TABLE audit_log (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  user_id INT UNSIGNED NULL,
  action VARCHAR(40) NOT NULL,                -- create/update/delete/login/import
  table_name VARCHAR(60) NULL,
  record_id BIGINT UNSIGNED NULL,
  old_values JSON NULL,
  new_values JSON NULL,
  ip VARCHAR(45) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY (table_name, record_id), KEY (user_id)
) ENGINE=InnoDB;

CREATE TABLE settings (
  skey VARCHAR(60) PRIMARY KEY,
  svalue TEXT NOT NULL,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------- Customers ----------
CREATE TABLE customers (
  id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(150) NOT NULL,
  location VARCHAR(120) NULL,                 -- e.g. Nsanama, Liwonde, Ulongwe
  phone VARCHAR(20) NULL,
  phone_alt VARCHAR(20) NULL,
  email VARCHAR(150) NULL,
  is_internal TINYINT(1) NOT NULL DEFAULT 0,  -- 1 = the farm's own eggs ("Msantha")
  sms_opt_in TINYINT(1) NOT NULL DEFAULT 1,
  email_opt_in TINYINT(1) NOT NULL DEFAULT 1,
  notes VARCHAR(255) NULL,
  legacy_names JSON NULL,                     -- every spelling found in Excel
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY (name), KEY (phone)
) ENGINE=InnoDB;

-- ---------- Species / breeds / flocks ----------
CREATE TABLE species (
  id TINYINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  code VARCHAR(20) NOT NULL UNIQUE,           -- CHICKEN, TURKEY, DUCK, QUAIL
  name VARCHAR(40) NOT NULL,
  incubation_days TINYINT UNSIGNED NOT NULL   -- chicken 21, turkey 28, duck 28, quail 17-18
) ENGINE=InnoDB;

CREATE TABLE breeds (
  id SMALLINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  species_id TINYINT UNSIGNED NOT NULL,
  code VARCHAR(30) NOT NULL UNIQUE,           -- MIKOLONGWE, SASSO, LOCAL, KUROILER, PEKIN, KHAKI_CAMPBELL, QUAIL, TURKEY
  name VARCHAR(60) NOT NULL,
  egg_tray_size TINYINT UNSIGNED NOT NULL DEFAULT 30,
  egg_tray_price INT UNSIGNED NULL,           -- current price per tray (MWK)
  FOREIGN KEY (species_id) REFERENCES species(id)
) ENGINE=InnoDB;

CREATE TABLE flocks (                         -- one row per Excel batch sheet
  id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  breed_id SMALLINT UNSIGNED NOT NULL,
  batch_code VARCHAR(60) NOT NULL UNIQUE,     -- e.g. SASSO-2(15)
  legacy_sheet VARCHAR(80) NULL,              -- exact Excel sheet name
  purpose ENUM('layers','broilers','breeders','mixed','unknown') NOT NULL DEFAULT 'unknown',
  start_date DATE NOT NULL,
  start_count INT UNSIGNED NOT NULL,
  source ENUM('hatched_own','purchased','incubation','other') NOT NULL DEFAULT 'other',
  status ENUM('active','closed') NOT NULL DEFAULT 'active',
  closed_date DATE NULL,
  notes VARCHAR(255) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (breed_id) REFERENCES breeds(id),
  KEY (status)
) ENGINE=InnoDB;

CREATE TABLE flock_events (                   -- authoritative: deaths, sales, additions, vaccinations, notes
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  flock_id INT UNSIGNED NOT NULL,
  event_date DATE NOT NULL,
  event_type ENUM('death','sale','addition','cull','consumed','transfer','vaccination','treatment','weighing','note') NOT NULL,
  quantity INT NOT NULL DEFAULT 0,
  unit_price INT UNSIGNED NULL,
  amount INT UNSIGNED NULL,
  customer_id INT UNSIGNED NULL,
  remarks VARCHAR(255) NULL,
  legacy_ref VARCHAR(80) NULL,                -- sheet!row
  created_by INT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (flock_id) REFERENCES flocks(id),
  FOREIGN KEY (customer_id) REFERENCES customers(id),
  KEY (flock_id, event_date), KEY (event_type)
) ENGINE=InnoDB;

CREATE TABLE flock_daily_legacy (             -- every daily row from the Excel batch sheets, verbatim
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  flock_id INT UNSIGNED NOT NULL,
  excel_row INT UNSIGNED NOT NULL,
  row_kind ENUM('day','monthly_total') NOT NULL DEFAULT 'day',
  record_date DATE NULL,
  date_suspect TINYINT(1) NOT NULL DEFAULT 0, -- 1 = year/date out of sequence in Excel
  is_projected TINYINT(1) NOT NULL DEFAULT 0, -- 1 = date after import cut-off (pre-filled template row)
  day_no INT NULL, week_no INT NULL, month_no INT NULL,
  opening_count INT NULL, deaths INT NULL, deaths_todate INT NULL, sold INT NULL, balance INT NULL,
  price INT NULL, remarks VARCHAR(255) NULL,
  UNIQUE KEY (flock_id, excel_row)
) ENGINE=InnoDB;

CREATE TABLE price_ladder (                   -- age-based selling price (from "Estimated PRICE"/"NEW PRICE EFFECTED")
  id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  breed_id SMALLINT UNSIGNED NOT NULL,
  from_week SMALLINT UNSIGNED NOT NULL,
  price INT UNSIGNED NOT NULL,
  effective_from DATE NOT NULL,
  FOREIGN KEY (breed_id) REFERENCES breeds(id),
  KEY (breed_id, effective_from)
) ENGINE=InnoDB;

-- ---------- Eggs & incubation ----------
CREATE TABLE egg_production_daily (           -- flock egg collection (quail sheet + ledger EGG PRODUCTION)
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  breed_id SMALLINT UNSIGNED NOT NULL,
  flock_id INT UNSIGNED NULL,
  prod_date DATE NOT NULL,
  birds_count INT NULL,
  collected INT NOT NULL DEFAULT 0,
  broken INT NOT NULL DEFAULT 0,
  consumed INT NOT NULL DEFAULT 0,
  sold INT NOT NULL DEFAULT 0,
  incubated INT NOT NULL DEFAULT 0,
  kept INT NOT NULL DEFAULT 0,
  remarks VARCHAR(255) NULL,
  legacy_ref VARCHAR(80) NULL,
  UNIQUE KEY (breed_id, flock_id, prod_date),
  FOREIGN KEY (breed_id) REFERENCES breeds(id)
) ENGINE=InnoDB;

CREATE TABLE incubators (
  id TINYINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(60) NOT NULL, capacity INT UNSIGNED NOT NULL DEFAULT 0, is_active TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;

CREATE TABLE incubation_jobs (                -- one row per customer egg batch (Excel "Poultry incubation analysis")
  id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  customer_id INT UNSIGNED NOT NULL,
  breed_id SMALLINT UNSIGNED NULL,
  incubator_id TINYINT UNSIGNED NULL,
  date_received DATE NULL,
  eggs_received INT UNSIGNED NOT NULL DEFAULT 0,
  eggs_incubated INT UNSIGNED NOT NULL DEFAULT 0,
  date_incubated DATE NULL,
  date_transfer DATE NULL,                    -- to hatching tray (day 18 for chicken)
  expected_hatch_date DATE NULL,
  date_hatched DATE NULL,
  infertile INT UNSIGNED NULL,
  fertile INT UNSIGNED NULL,
  hatched INT UNSIGNED NULL,
  rate_per_egg INT UNSIGNED NOT NULL DEFAULT 0,
  cost INT UNSIGNED NOT NULL DEFAULT 0,       -- eggs_incubated * rate_per_egg (or discounted)
  amount_paid INT UNSIGNED NOT NULL DEFAULT 0,
  status ENUM('received','incubating','hatching','completed','collected','cancelled') NOT NULL DEFAULT 'received',
  remarks VARCHAR(255) NULL,
  legacy_ref VARCHAR(80) NULL,
  created_by INT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (customer_id) REFERENCES customers(id),
  KEY (date_received), KEY (status), KEY (expected_hatch_date)
  -- hatch_percent = hatched / eggs_incubated * 100 ; balance = cost - amount_paid  (computed in queries/views, not stored)
) ENGINE=InnoDB;

CREATE TABLE incubation_payments (
  id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  job_id INT UNSIGNED NOT NULL,
  paid_on DATE NOT NULL,
  amount INT UNSIGNED NOT NULL,
  method ENUM('cash','airtel_money','tnm_mpamba','bank','paychangu','other') NOT NULL DEFAULT 'cash',
  payment_id INT UNSIGNED NULL,               -- link to online payments row
  received_by INT UNSIGNED NULL,
  FOREIGN KEY (job_id) REFERENCES incubation_jobs(id),
  KEY (job_id)
) ENGINE=InnoDB;

CREATE TABLE incubation_allocation_rules (    -- 2026 split of each receipt: 25/15/30/20/10 %
  id TINYINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  category ENUM('electricity','water','labour','telephone','profit') NOT NULL UNIQUE,
  percent DECIMAL(5,2) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE incubation_allocations (         -- cash receipts split per payment
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  payment_id INT UNSIGNED NOT NULL,
  category ENUM('electricity','water','labour','telephone','profit') NOT NULL,
  amount INT UNSIGNED NOT NULL,
  FOREIGN KEY (payment_id) REFERENCES incubation_payments(id),
  KEY (payment_id)
) ENGINE=InnoDB;

CREATE TABLE supportive_activities (          -- Excel S:V "supportive activities"
  id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  activity_date DATE NOT NULL, activity VARCHAR(150) NOT NULL, amount INT UNSIGNED NOT NULL DEFAULT 0,
  legacy_ref VARCHAR(80) NULL
) ENGINE=InnoDB;

-- ---------- Stock: feed, drugs ----------
CREATE TABLE feed_types (
  id TINYINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(60) NOT NULL UNIQUE              -- Layers mash, Pullet grower, Pullet starter, Chick feed, Kumuzi mash ...
) ENGINE=InnoDB;

CREATE TABLE feed_purchases (
  id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  purchase_date DATE NOT NULL, feed_type_id TINYINT UNSIGNED NOT NULL,
  quantity_kg DECIMAL(10,2) NOT NULL, cost INT UNSIGNED NOT NULL,
  supplier VARCHAR(100) NULL, legacy_ref VARCHAR(80) NULL,
  FOREIGN KEY (feed_type_id) REFERENCES feed_types(id), KEY (purchase_date)
) ENGINE=InnoDB;

CREATE TABLE drug_purchases (
  id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  purchase_date DATE NOT NULL, item VARCHAR(100) NOT NULL, doses INT NULL,
  cost INT UNSIGNED NOT NULL, legacy_ref VARCHAR(80) NULL, KEY (purchase_date)
) ENGINE=InnoDB;

CREATE TABLE vaccination_schedule (           -- reminder templates (Gumboro, Lasota, Hitchner, NCD ...)
  id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  species_id TINYINT UNSIGNED NOT NULL, vaccine VARCHAR(60) NOT NULL, at_day SMALLINT UNSIGNED NOT NULL,
  repeat_every_days SMALLINT UNSIGNED NULL, FOREIGN KEY (species_id) REFERENCES species(id)
) ENGINE=InnoDB;

-- ---------- Sales ----------
CREATE TABLE sales (
  id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  sale_date DATE NOT NULL, customer_id INT UNSIGNED NULL,
  total INT UNSIGNED NOT NULL DEFAULT 0, paid INT UNSIGNED NOT NULL DEFAULT 0,
  payment_method ENUM('cash','airtel_money','tnm_mpamba','bank','paychangu','credit') NOT NULL DEFAULT 'cash',
  legacy_ref VARCHAR(80) NULL, created_by INT UNSIGNED NULL,
  FOREIGN KEY (customer_id) REFERENCES customers(id), KEY (sale_date)
) ENGINE=InnoDB;

CREATE TABLE sale_items (
  id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  sale_id INT UNSIGNED NOT NULL,
  item_type ENUM('live_birds','fertilised_eggs','table_eggs','stale_eggs','chicks','manure','other') NOT NULL,
  breed_id SMALLINT UNSIGNED NULL, flock_id INT UNSIGNED NULL,
  quantity INT NOT NULL, unit_price INT UNSIGNED NOT NULL, amount INT UNSIGNED NOT NULL,
  FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------- Cash book (all business lines) ----------
CREATE TABLE business_units (
  id TINYINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  code VARCHAR(30) NOT NULL UNIQUE,           -- POULTRY, BEE, CAR_MZ9350, CAR_NA7153, CAR_MZ8170, CAR_MJ4324
  name VARCHAR(80) NOT NULL, kind ENUM('poultry','bee','car_hire','other') NOT NULL, is_active TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;

CREATE TABLE expense_categories (
  id SMALLINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(80) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE cash_entries (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  business_unit_id TINYINT UNSIGNED NOT NULL,
  entry_date DATE NULL,                       -- NULL when the Excel row had no date (kept, flagged)
  date_missing TINYINT(1) NOT NULL DEFAULT 0,
  description VARCHAR(255) NOT NULL,
  receipt INT UNSIGNED NOT NULL DEFAULT 0,    -- RECEIPTS (DEBTS)
  expenditure INT UNSIGNED NOT NULL DEFAULT 0,-- EXPENDITURE (CREDIT)
  category_id SMALLINT UNSIGNED NULL,
  entry_kind ENUM('transaction','month_total','note') NOT NULL DEFAULT 'transaction', -- Excel "JANUARY END" rows
  legacy_ref VARCHAR(80) NULL, created_by INT UNSIGNED NULL,
  FOREIGN KEY (business_unit_id) REFERENCES business_units(id),
  KEY (business_unit_id, entry_date)
) ENGINE=InnoDB;

CREATE TABLE bank_movements (                 -- Excel: Amount banked / Bank withdraws / Bank Balance
  id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  move_date DATE NOT NULL, kind ENUM('deposit','withdrawal') NOT NULL, amount INT UNSIGNED NOT NULL,
  balance_after INT NULL, remarks VARCHAR(255) NULL, legacy_ref VARCHAR(80) NULL, KEY (move_date)
) ENGINE=InnoDB;

-- ---------- Ledger snapshot import (verbatim, for traceability) ----------
CREATE TABLE legacy_ledger_daily (            -- 2022-2026 daily ledgers: one row per Excel row, wide values in JSON
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  source_sheet VARCHAR(60) NOT NULL, excel_row INT UNSIGNED NOT NULL,
  ledger_date DATE NULL, month_label VARCHAR(20) NULL, day_no INT NULL,
  values_json JSON NOT NULL,                  -- {"C: POULTRY BALANCES | MIKOLONGWE": 625, ...}
  remarks VARCHAR(500) NULL,
  UNIQUE KEY (source_sheet, excel_row)
) ENGINE=InnoDB;

CREATE TABLE import_log (
  id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  source_file VARCHAR(150) NOT NULL, rows_in_csv INT NOT NULL, rows_imported INT NOT NULL,
  rows_skipped INT NOT NULL, skipped_detail JSON NULL, run_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------- Online payments (PayChangu) ----------
CREATE TABLE payments (
  id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  tx_ref VARCHAR(64) NOT NULL UNIQUE,         -- our unique reference sent to PayChangu
  purpose ENUM('incubation_job','sale','deposit','other') NOT NULL,
  purpose_id INT UNSIGNED NULL,
  customer_id INT UNSIGNED NULL,
  amount INT UNSIGNED NOT NULL, currency CHAR(3) NOT NULL DEFAULT 'MWK',
  status ENUM('pending','success','failed','cancelled') NOT NULL DEFAULT 'pending',
  checkout_url VARCHAR(500) NULL,
  paychangu_charge_id VARCHAR(80) NULL, channel VARCHAR(30) NULL, mobile_number VARCHAR(20) NULL,
  raw_verify JSON NULL, raw_webhook JSON NULL,
  verified_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (customer_id) REFERENCES customers(id), KEY (status)
) ENGINE=InnoDB;

CREATE TABLE webhook_events (                 -- idempotency: every webhook hit is stored once
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  provider VARCHAR(20) NOT NULL, event_key VARCHAR(120) NOT NULL, payload JSON NOT NULL,
  signature_valid TINYINT(1) NOT NULL, processed TINYINT(1) NOT NULL DEFAULT 0,
  received_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, UNIQUE KEY (provider, event_key)
) ENGINE=InnoDB;

-- ---------- SMS ----------
CREATE TABLE sms_templates (
  id SMALLINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  code VARCHAR(40) NOT NULL UNIQUE, body VARCHAR(480) NOT NULL, is_active TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;

CREATE TABLE sms_outbox (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  to_phone VARCHAR(20) NOT NULL, customer_id INT UNSIGNED NULL, user_id INT UNSIGNED NULL,
  template_code VARCHAR(40) NULL, body VARCHAR(480) NOT NULL,
  status ENUM('queued','sent','delivered','failed') NOT NULL DEFAULT 'queued',
  attempts TINYINT UNSIGNED NOT NULL DEFAULT 0, provider_msg_id VARCHAR(80) NULL, error VARCHAR(255) NULL,
  related_table VARCHAR(40) NULL, related_id INT UNSIGNED NULL,
  dedupe_key VARCHAR(120) NULL UNIQUE,        -- stops duplicate reminders
  send_after DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  sent_at DATETIME NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY (status, send_after)
) ENGINE=InnoDB;

SET FOREIGN_KEY_CHECKS = 1;

-- ---------- Seed ----------
INSERT INTO roles (code,name) VALUES ('owner','Owner'),('manager','Manager'),('worker','Farm worker'),('accountant','Accountant'),('viewer','Viewer');
INSERT INTO species (code,name,incubation_days) VALUES ('CHICKEN','Chicken',21),('TURKEY','Turkey',28),('DUCK','Duck',28),('QUAIL','Quail',18);
INSERT INTO breeds (species_id,code,name,egg_tray_size,egg_tray_price) VALUES
 (1,'MIKOLONGWE','Mikolongwe',30,27000),(1,'SASSO','Sasso',30,30000),(1,'LOCAL','Local chicken',30,27000),(1,'KUROILER','Kuroiler',30,NULL),
 (2,'TURKEY','Turkey',30,NULL),(3,'PEKIN','Pekin duck',30,40000),(3,'KHAKI_CAMPBELL','Khaki Campbell duck',30,40000),(4,'QUAIL','Quail',30,10000);
INSERT INTO incubation_allocation_rules (category,percent) VALUES ('electricity',25),('water',15),('labour',30),('telephone',20),('profit',10);
INSERT INTO business_units (code,name,kind) VALUES ('POULTRY','Poultry','poultry'),('BEE','Bee farming','bee'),('CAR_MZ9350','Car hire MZ 9350','car_hire'),('CAR_NA7153','Car hire NA 7153','car_hire'),('CAR_MZ8170','Car hire MZ 8170','car_hire'),('CAR_MJ4324','Car hire MJ 4324','car_hire');
INSERT INTO feed_types (name) VALUES ('Layers mash'),('Pullet grower'),('Pullet starter'),('Chick feed'),('Growers mash');
INSERT INTO sms_templates (code,body) VALUES
 ('JOB_RECEIVED','Msantha Poultry: we received {eggs} eggs on {date}. Expected hatch: {hatch_date}. Cost MK{cost}.'),
 ('TRANSFER_DAY','Msantha Poultry: your {eggs} eggs moved to the hatching tray today. Hatch expected {hatch_date}.'),
 ('HATCH_READY','Msantha Poultry: {hatched} chicks hatched from your {eggs} eggs ({percent}%). Please collect. Balance MK{balance}.'),
 ('PAYMENT_RECEIPT','Msantha Poultry: received MK{amount} on {date}. Balance MK{balance}. Thank you.'),
 ('BALANCE_REMINDER','Msantha Poultry: you owe MK{balance} for incubation of {date}. Pay via {pay_link}'),
 ('VACCINE_DUE','Farm alert: {vaccine} due today for {batch}.'),
 ('LOW_STOCK','Farm alert: {batch} stock is {balance}. Deaths today {deaths}.');
