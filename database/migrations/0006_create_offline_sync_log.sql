-- Migration 0006: Create offline_sync_log for PWA and Mobile offline queue
CREATE TABLE IF NOT EXISTS offline_sync_log (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  client_uuid VARCHAR(64) NOT NULL UNIQUE,
  record_type ENUM('death','egg','sale') NOT NULL,
  entity_id BIGINT UNSIGNED NULL,
  user_id INT UNSIGNED NULL,
  status ENUM('success','failed','duplicate') NOT NULL DEFAULT 'success',
  payload_json JSON NULL,
  error_message TEXT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  synced_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY (record_type),
  KEY (user_id),
  KEY (synced_at)
) ENGINE=InnoDB;

