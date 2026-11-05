-- Migration 0005: Create month_locks table for multi-business cash book month-end close
CREATE TABLE IF NOT EXISTS month_locks (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    business_unit_id TINYINT UNSIGNED NOT NULL,
    `year_month` CHAR(7) NOT NULL, -- Format: 'YYYY-MM'
    locked_by INT UNSIGNED NULL,
    locked_at DATETIME NOT NULL,
    notes VARCHAR(255) NULL,
    UNIQUE KEY uq_unit_month (business_unit_id, `year_month`),
    FOREIGN KEY (business_unit_id) REFERENCES business_units(id) ON DELETE CASCADE,
    FOREIGN KEY (locked_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;
