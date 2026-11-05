-- Migration 0007: Add Data Review & Quality Flags
ALTER TABLE incubation_jobs 
  ADD COLUMN IF NOT EXISTS date_suspect TINYINT(1) NOT NULL DEFAULT 0 AFTER status,
  ADD COLUMN IF NOT EXISTS raw_date VARCHAR(50) NULL AFTER date_suspect;

ALTER TABLE egg_production_daily
  ADD COLUMN IF NOT EXISTS date_suspect TINYINT(1) NOT NULL DEFAULT 0 AFTER kept,
  ADD COLUMN IF NOT EXISTS raw_date VARCHAR(50) NULL AFTER date_suspect;

ALTER TABLE cash_entries
  ADD COLUMN IF NOT EXISTS date_suspect TINYINT(1) NOT NULL DEFAULT 0 AFTER date_missing,
  ADD COLUMN IF NOT EXISTS raw_date VARCHAR(50) NULL AFTER date_suspect;
