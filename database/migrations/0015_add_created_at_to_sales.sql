-- Migration 0015: Add created_at timestamp to sales table
-- Fixes: Unknown column 'created_at' in 'field list' during sales creation

ALTER TABLE sales ADD COLUMN created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP;
