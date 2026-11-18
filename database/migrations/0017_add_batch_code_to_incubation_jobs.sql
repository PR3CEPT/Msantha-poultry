-- Migration 0017: Add batch_code to incubation_jobs table and update v_incubation_jobs view
-- Enables customer-specific initial + sequence batch numbering (e.g. IM-01, GP-02)

ALTER TABLE incubation_jobs ADD COLUMN batch_code VARCHAR(30) NULL AFTER customer_id;

CREATE OR REPLACE VIEW v_incubation_jobs AS
SELECT
    j.id,
    j.customer_id,
    c.name AS customer_name,
    c.phone AS customer_phone,
    c.is_internal AS customer_is_internal,
    j.batch_code,
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
