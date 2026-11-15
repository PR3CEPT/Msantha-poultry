CREATE TABLE IF NOT EXISTS user_notification_status (
    user_id INT NOT NULL,
    notification_key VARCHAR(120) NOT NULL,
    is_seen TINYINT NOT NULL DEFAULT 0,
    is_deleted TINYINT NOT NULL DEFAULT 0,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, notification_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

