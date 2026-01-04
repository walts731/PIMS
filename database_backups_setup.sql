-- Create backups table
CREATE TABLE IF NOT EXISTS backups (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    type ENUM('full', 'database', 'files') NOT NULL,
    include_files BOOLEAN DEFAULT FALSE,
    include_database BOOLEAN DEFAULT FALSE,
    file_path VARCHAR(255) NOT NULL,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id)
);
