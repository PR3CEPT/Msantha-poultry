-- Migration 0014: Create contact inquiries table for public website inquiries
CREATE TABLE IF NOT EXISTS contact_inquiries (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    phone VARCHAR(30) NOT NULL,
    subject VARCHAR(150) NOT NULL,
    message TEXT NOT NULL,
    status ENUM('new', 'attended') NOT NULL DEFAULT 'new',
    attended_by INT UNSIGNED NULL,
    attended_at DATETIME NULL,
    resolution_notes TEXT NULL,
    ip VARCHAR(45) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_status (status),
    KEY idx_created_at (created_at),
    FOREIGN KEY (attended_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
