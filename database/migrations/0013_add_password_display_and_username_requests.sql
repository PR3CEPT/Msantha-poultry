-- Migration 0013: Add reversible password display and username change requests

-- 1. Add password_display column to users table
ALTER TABLE users ADD COLUMN password_display TEXT NULL AFTER password_hash;

-- 2. Create username_change_requests table
CREATE TABLE IF NOT EXISTS username_change_requests (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id INT UNSIGNED NOT NULL,
    current_username VARCHAR(60) NOT NULL,
    requested_username VARCHAR(60) NOT NULL,
    reason TEXT NULL,
    status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    reviewed_by INT UNSIGNED NULL,
    reviewed_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_user_id (user_id),
    KEY idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
