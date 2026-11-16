-- Migration 0012: Ensure internal owner accounts (is_internal = 1) have zero outstanding balance and zero debt flags
CREATE OR REPLACE VIEW v_customer_balances AS
SELECT 
    c.id AS customer_id,
    c.name AS name,
    c.phone AS phone,
    c.location AS location,
    c.is_internal AS is_internal,
    c.sms_opt_in AS sms_opt_in,
    COALESCE(j.incubation_cost, 0) AS incubation_cost,
    COALESCE(j.incubation_paid, 0) AS incubation_paid,
    CASE 
        WHEN c.is_internal = 1 THEN 0 
        ELSE CAST(COALESCE(j.incubation_cost, 0) - COALESCE(j.incubation_paid, 0) AS SIGNED) 
    END AS incubation_balance,
    COALESCE(s.sales_total, 0) AS sales_total,
    COALESCE(s.sales_paid, 0) AS sales_paid,
    CASE 
        WHEN c.is_internal = 1 THEN 0 
        ELSE CAST(COALESCE(s.sales_total, 0) - COALESCE(s.sales_paid, 0) AS SIGNED) 
    END AS sales_balance,
    CASE 
        WHEN c.is_internal = 1 THEN 0 
        ELSE CAST(COALESCE(j.incubation_cost, 0) - COALESCE(j.incubation_paid, 0) + (COALESCE(s.sales_total, 0) - COALESCE(s.sales_paid, 0)) AS SIGNED) 
    END AS total_balance,
    CASE 
        WHEN c.is_internal = 1 THEN 0 
        WHEN COALESCE(j.incubation_cost, 0) - COALESCE(j.incubation_paid, 0) + (COALESCE(s.sales_total, 0) - COALESCE(s.sales_paid, 0)) > 0 THEN 1 
        ELSE 0 
    END AS has_debt,
    CASE 
        WHEN c.is_internal = 1 THEN 0 
        WHEN COALESCE(j.incubation_cost, 0) - COALESCE(j.incubation_paid, 0) + (COALESCE(s.sales_total, 0) - COALESCE(s.sales_paid, 0)) < 0 THEN 1 
        ELSE 0 
    END AS is_overpaid
FROM customers c
LEFT JOIN (
    SELECT customer_id, SUM(cost) AS incubation_cost, SUM(amount_paid) AS incubation_paid
    FROM incubation_jobs
    GROUP BY customer_id
) j ON j.customer_id = c.id
LEFT JOIN (
    SELECT customer_id, SUM(total) AS sales_total, SUM(paid) AS sales_paid
    FROM sales
    WHERE customer_id IS NOT NULL
    GROUP BY customer_id
) s ON s.customer_id = c.id;
