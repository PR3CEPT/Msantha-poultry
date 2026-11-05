# Msantha Poultry Management System — User & Operations Manual

Welcome to the **Msantha Poultry Management System**. This user guide outlines the primary workflows and screens for all farm roles.

---

## 1. System Navigation & Mobile PWA App

### Desktop Navigation (Screens &ge; 992px)
* **Top Bar**: System logo, farm name, live offline indicator, pending sync badge, and your profile dropdown.
* **Left Sidebar**: Quick navigation to Home, Flocks, Eggs, Incubation, Sales, Feed & Drugs, Finance, SMS, Reports, and Admin Tools.

### Mobile Navigation (Smartphones & Tablets)
* **Bottom Navigation Bar**: One-touch access to **Home**, **Flocks**, **Eggs**, **Incubation**, and **More**.
* **PWA Offline Mode**: You can install Msantha Farm on Android/iOS via your browser's "Add to Home Screen" or the top install banner. When working inside pens with poor cell coverage, the application continues to accept daily entries (eggs, deaths, sales) and automatically synchronizes when connection is restored.

---

## 2. Role-by-Role Operations Manual

### Role 1: Farm Worker (Precious Chipeta)
*Primary responsibilities: Daily flock mortality, egg collection, and quick entries.*

#### A. Quick Daily Entry
1. Tap **Flocks** on the bottom navigation bar and select **Quick Daily Entry**.
2. Select the flock (e.g. `SASSO-2(15)` or `MIKOLONGWE-1`).
3. Enter:
   * **Deaths today**: Enter number of bird deaths observed during morning inspection.
   * **Birds sold / culled / consumed**: Enter numbers if any.
   * **Vaccination / Treatment**: Select or describe medication administered.
   * **Remarks / Notes**: Free-text notes on pen health or feeder observations.
4. Tap **Save Daily Entry**.
5. *Offline Support*: If the farm network is down, the screen saves your entry directly into the phone's offline queue (`IndexedDB`). A badge appears in the topbar: `Pending Sync (1)`. As soon as you return to Wi-Fi/cellular coverage, it uploads automatically.

#### B. Daily Egg Collection
1. Tap **Eggs** on the bottom navigation.
2. Select the breed (e.g. *Mikolongwe*, *Sasso*, *Local Chicken*, *Quail*).
3. Enter eggs collected:
   * **Collected**: Total count of eggs gathered.
   * **Broken / Damaged**: Count of cracked or damaged eggs.
   * **Tray Breakdown**: The system automatically computes full trays (30 eggs/tray) and loose eggs.
4. Tap **Record Collection**.

---

### Role 2: Farm Manager (Isaac Precious Chipeta)
*Primary responsibilities: Incubation batch management, flock pricing ladders, customer management, and SMS alerts.*

#### A. Receiving Customer Eggs for Incubation
1. Navigate to **Incubation &rarr; Receive Eggs** (`/incubation/receive`).
2. Search for the customer by phone or name (or tap **Add New Customer**).
3. Select species (Chicken: 21 days, Turkey: 28 days, Duck: 28 days, Quail: 18 days).
4. Enter `Eggs Received` and `Rate per Egg` (defaults from farm settings, e.g. MWK 100).
5. The system automatically computes total incubation cost and schedules:
   * **Transfer Date** (Day 18 for chickens)
   * **Expected Hatch Date** (Day 21 for chickens)
6. Tap **Register Batch**.
7. An automated `JOB_RECEIVED` SMS confirmation is queued for the customer.

#### B. Recording Hatching & Releasing Chicks
1. Open the incubation job when hatching completes.
2. Enter results: `Infertile`, `Fertile`, and `Hatched`.
3. The system computes the **Hatch %** (`hatched / eggs_incubated * 100`).
4. Generate and print:
   * **Official Hatch Certificate** (with farm logo and inspection stamps).
   * **Customer Payment Receipt**.

#### C. Dynamic Price Ladders
1. Navigate to **Flocks &rarr; Price Ladder** (`/flocks/price-ladder`).
2. Set prices per breed at given ages (weeks 1 to 24+).
3. The system uses this ladder across all point-of-sale screens to price live birds accurately.

---

### Role 3: Farm Accountant (Faith Chipeta)
*Primary responsibilities: Multi-business cash books, statutory allocations, sales receipts, and month-end closing.*

#### A. Multi-Business Unit Cash Books
1. Navigate to **Finance** (`/finance`).
2. Select the business unit tab:
   * **POULTRY** (Farm operations & incubation)
   * **BEE** (Honey and apiary products)
   * **CAR_MZ9350**, **CAR_NA7153**, **CAR_MZ8170**, **CAR_MJ4324** (Car hire vehicles)
3. Enter new receipts (credits) or expenditures (debits).
4. View running balance and monthly summaries.

#### B. Statutory Incubation 25/15/30/20/10 Split
Every incubation cash payment is split into statutory expense accounts:
* **25%** Electricity
* **15%** Water
* **30%** Labour
* **20%** Telephone
* **10% + remainder** Farm Profit
This allocation appears in the POULTRY cash book and is audited in **Reports &rarr; Incubation Allocation Income**.

#### C. Month-End Close & Lock
1. At the end of each calendar month, reconcile the cash book against physical bank statements and physical cash on hand.
2. Click **Lock Month**.
3. Once locked, records for that month cannot be edited or altered by any staff member (unlocking requires Owner override).

---

### Role 4: Farm Owner (Mr. I.K Chipeta)
*Primary responsibilities: Farm oversight, profit & loss analysis, user account control, and emergency backups.*

#### A. Operational Dashboard & P&L
* Real-time metrics: Live birds in stock by breed, weekly mortality rates, active incubating batches with countdown timers, and cash balances across all six business units.
* Access **Reports &rarr; Monthly P&L** to review consolidated farm profitability.

#### B. User Accounts Management
1. Navigate to **User Accounts** (`/admin/users`).
2. Create, edit, or deactivate staff credentials.
3. Enforce single active sessions and password security policies.

#### C. Database Snapshots & Backups
1. Navigate to **Database Backups** (`/admin/backups`).
2. View nightly automated backups.
3. Click **Create Backup Now** to take an instant snapshot.
4. Click **Download** to save an encrypted SQL dump directly to your personal computer.

---

## 3. PayChangu Online Gateway & Mobile Money Payments

Customers can pay their incubation deposits or egg purchases via Airtel Money, TNM Mpamba, or Bank Card:
1. On any Incubation Job or Sale screen, click **Pay Online** or **Send Pay Link by SMS**.
2. The customer receives a secure PayChangu checkout link (`https://checkout.paychangu.com/...`).
3. Upon payment approval:
   * The webhook validates the cryptographic signature (`HMAC-SHA256`).
   * The job balance is marked paid.
   * Statutory 25/15/30/20/10 allocations are distributed automatically.
   * A `PAYMENT_RECEIPT` SMS is queued to the customer's phone.

