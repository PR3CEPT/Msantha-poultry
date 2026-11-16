-- Migration 0011: Fix negative bird stock, clean legacy phantom deaths, update v_flock_status

-- 1. Delete legacy phantom death template rows for Flock 9 (MIKOLONGWE 8 (2))
DELETE FROM flock_events 
WHERE flock_id = 9 
  AND event_type = 'death' 
  AND id BETWEEN 38 AND 68;

-- 2. Delete legacy phantom death template rows for Flock 10 (MIKOLONGWE 8 (1))
DELETE FROM flock_events 
WHERE flock_id = 10 
  AND event_type = 'death' 
  AND id BETWEEN 75 AND 170 
  AND id != 164;

-- 3. Mark completed/depleted batches as closed
UPDATE flocks SET status = 'closed', closed_date = COALESCE(closed_date, CURRENT_DATE())
WHERE id IN (9, 10, 16, 18, 1);

-- 4. Recreate v_flock_status with GREATEST(0, ...) guarantee
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
    GREATEST(0, CAST(f.start_count + COALESCE(e.addition_count, 0) - COALESCE(e.deaths_count, 0) - COALESCE(e.sold_count, 0) - COALESCE(e.cull_count, 0) - COALESCE(e.consumed_count, 0) AS SIGNED)) AS current_stock,
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
