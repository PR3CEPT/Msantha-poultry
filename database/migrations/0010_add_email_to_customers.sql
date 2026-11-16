-- Migration 0010: Add email and email_opt_in to customers table for executive and client notification delivery

ALTER TABLE customers ADD COLUMN email VARCHAR(150) NULL AFTER phone_alt;
ALTER TABLE customers ADD COLUMN email_opt_in TINYINT(1) DEFAULT 1 AFTER sms_opt_in;
