-- Migration 0003: Core SQL Views for Flocks, Incubation, Balances, and Cash Summaries

-- 1. Flock Status View (stock, mortality, age, events)
CREATE OR REPLACE VIEW v_flock_status AS
SELECT
    f.id AS flock_id,
    f.batch_code,
    f.breed_id,
    b.name AS breed_name,
    s.code AS species_code,
    s.name AS species_name,
    f.purpose,
    f.start_date,
    f.start_count,
    f.start_count AS starting_count,
    f.status,
    f.closed_date,
    f.legacy_sheet,
    COALESCE(e.deaths_count, 0) AS deaths_todate,
    COALESCE(e.sold_count, 0) AS sold_count,
    COALESCE(e.sold_count, 0) AS sold_todate,
    COALESCE(e.cull_count, 0) AS cull_count,
    COALESCE(e.consumed_count, 0) AS consumed_count,
    COALESCE(e.addition_count, 0) AS addition_count,
    CAST(f.start_count + COALESCE(e.addition_count, 0) - COALESCE(e.deaths_count, 0) - COALESCE(e.sold_count, 0) - COALESCE(e.cull_count, 0) - COALESCE(e.consumed_count, 0) AS SIGNED) AS current_stock,
    DATEDIFF(COALESCE(f.closed_date, CURRENT_DATE()), f.start_date) AS age_days,
    FLOOR(DATEDIFF(COALESCE(f.closed_date, CURRENT_DATE()), f.start_date) / 7) AS age_weeks,
    CASE
        WHEN f.start_count > 0 THEN ROUND((COALESCE(e.deaths_count, 0) / f.start_count) * 100, 2)
        ELSE 0.00
    END AS mortality_rate_percent
FROM flocks f
JOIN breeds b ON b.id = f.breed_id
JOIN species s ON s.id = b.species_id
LEFT JOIN (
    SELECT
        flock_id,
        SUM(CASE WHEN event_type = 'death' THEN quantity ELSE 0 END) AS deaths_count,
        SUM(CASE WHEN event_type = 'sale' THEN quantity ELSE 0 END) AS sold_count,
        SUM(CASE WHEN event_type = 'cull' THEN quantity ELSE 0 END) AS cull_count,
        SUM(CASE WHEN event_type = 'consumed' THEN quantity ELSE 0 END) AS consumed_count,
        SUM(CASE WHEN event_type = 'addition' THEN quantity ELSE 0 END) AS addition_count
    FROM flock_events
    GROUP BY flock_id
) e ON e.flock_id = f.id;

-- 2. Incubation Jobs View (hatch %, balance, days to hatch)
CREATE OR REPLACE VIEW v_incubation_jobs AS
SELECT
    j.id,
    j.customer_id,
    c.name AS customer_name,
    c.phone AS customer_phone,
    c.is_internal AS customer_is_internal,
    j.breed_id,
    b.name AS breed_name,
    j.incubator_id,
    inc.name AS incubator_name,
    j.date_received,
    j.eggs_received,
    j.eggs_incubated,
    j.date_incubated,
    j.date_transfer,
    j.expected_hatch_date,
    j.date_hatched,
    j.infertile,
    j.fertile,
    j.hatched,
    j.rate_per_egg,
    j.cost,
    j.amount_paid AS recorded_paid,
    COALESCE(p.paid_sum, j.amount_paid, 0) AS total_paid,
    CASE
        WHEN c.is_internal = 1 OR LOWER(c.name) LIKE '%msantha%' THEN 0
        ELSE CAST(j.cost - COALESCE(p.paid_sum, j.amount_paid, 0) AS SIGNED)
    END AS balance,
    CASE
        WHEN j.eggs_incubated > 0 AND j.hatched IS NOT NULL
        THEN ROUND((j.hatched / j.eggs_incubated) * 100, 2)
        ELSE NULL
    END AS hatch_percent,
    CASE
        WHEN j.expected_hatch_date IS NOT NULL
        THEN DATEDIFF(j.expected_hatch_date, CURRENT_DATE())
        ELSE NULL
    END AS days_to_hatch,
    j.status,
    j.remarks,
    j.legacy_ref,
    j.created_by,
    j.created_at
FROM incubation_jobs j
JOIN customers c ON c.id = j.customer_id
LEFT JOIN breeds b ON b.id = j.breed_id
LEFT JOIN incubators inc ON inc.id = j.incubator_id
LEFT JOIN (
    SELECT job_id, SUM(amount) AS paid_sum
    FROM incubation_payments
    GROUP BY job_id
) p ON p.job_id = j.id;

-- 3. Customer Balances View (incubation + sales balances)
CREATE OR REPLACE VIEW v_customer_balances AS
SELECT
    c.id AS customer_id,
    c.name,
    c.phone,
    c.location,
    c.is_internal,
    c.sms_opt_in,
    COALESCE(j.incubation_cost, 0) AS incubation_cost,
    COALESCE(j.incubation_paid, 0) AS incubation_paid,
    CAST(COALESCE(j.incubation_cost, 0) - COALESCE(j.incubation_paid, 0) AS SIGNED) AS incubation_balance,
    COALESCE(s.sales_total, 0) AS sales_total,
    COALESCE(s.sales_paid, 0) AS sales_paid,
    CAST(COALESCE(s.sales_total, 0) - COALESCE(s.sales_paid, 0) AS SIGNED) AS sales_balance,
    CAST((COALESCE(j.incubation_cost, 0) - COALESCE(j.incubation_paid, 0)) + (COALESCE(s.sales_total, 0) - COALESCE(s.sales_paid, 0)) AS SIGNED) AS total_balance,
    CASE
        WHEN ((COALESCE(j.incubation_cost, 0) - COALESCE(j.incubation_paid, 0)) + (COALESCE(s.sales_total, 0) - COALESCE(s.sales_paid, 0))) > 0 THEN 1
        ELSE 0
    END AS has_debt,
    CASE
        WHEN ((COALESCE(j.incubation_cost, 0) - COALESCE(j.incubation_paid, 0)) + (COALESCE(s.sales_total, 0) - COALESCE(s.sales_paid, 0))) < 0 THEN 1
        ELSE 0
    END AS is_overpaid
FROM customers c
LEFT JOIN (
    SELECT
        customer_id,
        SUM(cost) AS incubation_cost,
        SUM(amount_paid) AS incubation_paid
    FROM incubation_jobs
    GROUP BY customer_id
) j ON j.customer_id = c.id
LEFT JOIN (
    SELECT
        customer_id,
        SUM(total) AS sales_total,
        SUM(paid) AS sales_paid
    FROM sales
    WHERE customer_id IS NOT NULL
    GROUP BY customer_id
) s ON s.customer_id = c.id;

-- 4. Cash Month Summary View (multi-business cashbook summaries)
CREATE OR REPLACE VIEW v_cash_month_summary AS
SELECT
    bu.id AS business_unit_id,
    bu.code AS business_unit_code,
    bu.name AS business_unit_name,
    bu.kind AS business_unit_kind,
    DATE_FORMAT(ce.entry_date, '%Y-%m') AS `year_month`,
    YEAR(ce.entry_date) AS entry_year,
    MONTH(ce.entry_date) AS entry_month,
    COALESCE(SUM(CASE WHEN ce.entry_kind = 'transaction' THEN ce.receipt ELSE 0 END), 0) AS total_receipts,
    COALESCE(SUM(CASE WHEN ce.entry_kind = 'transaction' THEN ce.expenditure ELSE 0 END), 0) AS total_expenditure,
    CAST(COALESCE(SUM(CASE WHEN ce.entry_kind = 'transaction' THEN ce.receipt ELSE 0 END), 0) - COALESCE(SUM(CASE WHEN ce.entry_kind = 'transaction' THEN ce.expenditure ELSE 0 END), 0) AS SIGNED) AS net_cash,
    COUNT(CASE WHEN ce.entry_kind = 'transaction' THEN 1 END) AS transaction_count,
    COALESCE(SUM(CASE WHEN ce.entry_kind = 'month_total' THEN ce.receipt ELSE 0 END), 0) AS excel_month_total_receipts,
    COALESCE(SUM(CASE WHEN ce.entry_kind = 'month_total' THEN ce.expenditure ELSE 0 END), 0) AS excel_month_total_expenditure
FROM business_units bu
LEFT JOIN cash_entries ce ON ce.business_unit_id = bu.id AND ce.entry_date IS NOT NULL
GROUP BY
    bu.id,
    bu.code,
    bu.name,
    bu.kind,
    DATE_FORMAT(ce.entry_date, '%Y-%m'),
    YEAR(ce.entry_date),
    MONTH(ce.entry_date);
