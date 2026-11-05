-- Migration 0002: Add complementary performance indexes for blueprint screens
-- Note: Base schema.sql columns and tables are preserved intact without modification.

ALTER TABLE flocks ADD INDEX idx_flocks_start_date (start_date);
ALTER TABLE incubation_jobs ADD INDEX idx_incubation_jobs_date_incubated (date_incubated);
ALTER TABLE incubation_jobs ADD INDEX idx_incubation_jobs_transfer (date_transfer);
ALTER TABLE incubation_payments ADD INDEX idx_incubation_payments_paid_on (paid_on);
ALTER TABLE cash_entries ADD INDEX idx_cash_entries_kind (entry_kind);
ALTER TABLE sales ADD INDEX idx_sales_payment_method (payment_method);

