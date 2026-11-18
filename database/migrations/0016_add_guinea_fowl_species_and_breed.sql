-- Migration 0016: Add Guinea Fowl species and breed with 28-day incubation
-- Guinea fowl (Numida meleagris) incubation period is 28 days with transfer on Day 25

INSERT INTO species (id, code, name, incubation_days)
SELECT 5, 'GUINEA_FOWL', 'Guinea fowl', 28
WHERE NOT EXISTS (SELECT 1 FROM species WHERE code = 'GUINEA_FOWL');

INSERT INTO breeds (species_id, code, name, egg_tray_size, egg_tray_price)
SELECT s.id, 'GUINEA_FOWL', 'Guinea fowl', 30, NULL
FROM species s
WHERE s.code = 'GUINEA_FOWL'
  AND NOT EXISTS (SELECT 1 FROM breeds WHERE code = 'GUINEA_FOWL');
