-- Migration 0004: Add password policy, user sessions, and immutable audit log protection

ALTER TABLE users ADD COLUMN must_change_password TINYINT(1) NOT NULL DEFAULT 0 AFTER is_active;
ALTER TABLE users ADD COLUMN custom_permissions JSON NULL AFTER must_change_password;
ALTER TABLE audit_log ADD COLUMN user_agent VARCHAR(255) NULL AFTER ip;

CREATE TABLE IF NOT EXISTS user_sessions (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id INT UNSIGNED NOT NULL UNIQUE,
    session_id VARCHAR(128) NOT NULL,
    ip VARCHAR(45) NOT NULL,
    user_agent VARCHAR(255) NULL,
    logged_in_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_activity_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    KEY (last_activity_at)
) ENGINE=InnoDB;

DROP TRIGGER IF EXISTS trg_audit_log_prevent_update;
DROP TRIGGER IF EXISTS trg_audit_log_prevent_delete;

CREATE TRIGGER trg_audit_log_prevent_update
BEFORE UPDATE ON audit_log
FOR EACH ROW
BEGIN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Audit log is append-only and cannot be updated.';
END;

CREATE TRIGGER trg_audit_log_prevent_delete
BEFORE DELETE ON audit_log
FOR EACH ROW
BEGIN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Audit log is append-only and cannot be deleted.';
END;
