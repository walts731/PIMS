-- RIS (Requisition and Issue Slip) Database Setup Script
-- USE pims;

-- Disable foreign key checks temporarily
SET FOREIGN_KEY_CHECKS = 0;

-- Drop existing RIS tables if they exist
DROP TABLE IF EXISTS ris_items;
DROP TABLE IF EXISTS ris_forms;

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;

-- Create RIS Forms table
CREATE TABLE ris_forms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ris_no VARCHAR(50) UNIQUE NOT NULL,
    sai_no VARCHAR(50) UNIQUE NOT NULL,
    code VARCHAR(50) UNIQUE NOT NULL,
    division VARCHAR(100) NOT NULL,
    office VARCHAR(100) NOT NULL,
    responsibility_center VARCHAR(100) NOT NULL,
    date DATE NOT NULL,
    date_2 DATE NOT NULL,
    purpose TEXT NOT NULL,
    requested_by VARCHAR(100) NOT NULL,
    requested_by_position VARCHAR(100) NOT NULL,
    requested_date DATE NOT NULL,
    approved_by VARCHAR(100) NOT NULL,
    approved_by_position VARCHAR(100) NOT NULL,
    approved_date DATE NOT NULL,
    issued_by VARCHAR(100) NOT NULL,
    issued_by_position VARCHAR(100) NOT NULL,
    issued_date DATE NOT NULL,
    received_by VARCHAR(100) NOT NULL,
    received_by_position VARCHAR(100) NOT NULL,
    received_date DATE NOT NULL,
    status ENUM('draft', 'submitted', 'approved', 'issued', 'received', 'cancelled') DEFAULT 'draft',
    total_amount DECIMAL(12,2) DEFAULT 0.00,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id),
    INDEX idx_ris_no (ris_no),
    INDEX idx_sai_no (sai_no),
    INDEX idx_code (code),
    INDEX idx_status (status),
    INDEX idx_date (date)
);

-- Create RIS Items table
CREATE TABLE ris_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ris_form_id INT NOT NULL,
    stock_no INT NOT NULL,
    unit VARCHAR(50) NOT NULL,
    description TEXT NOT NULL,
    quantity DECIMAL(10,2) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    total_amount DECIMAL(12,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (ris_form_id) REFERENCES ris_forms(id) ON DELETE CASCADE,
    INDEX idx_ris_form_id (ris_form_id),
    INDEX idx_stock_no (stock_no)
);

-- Create trigger to update total_amount when items change
DELIMITER //
CREATE TRIGGER ris_items_after_insert 
AFTER INSERT ON ris_items
FOR EACH ROW
BEGIN
    UPDATE ris_forms 
    SET total_amount = (
        SELECT COALESCE(SUM(total_amount), 0) 
        FROM ris_items 
        WHERE ris_form_id = NEW.ris_form_id
    ),
    updated_at = CURRENT_TIMESTAMP
    WHERE id = NEW.ris_form_id;
END//
DELIMITER ;

DELIMITER //
CREATE TRIGGER ris_items_after_update 
AFTER UPDATE ON ris_items
FOR EACH ROW
BEGIN
    UPDATE ris_forms 
    SET total_amount = (
        SELECT COALESCE(SUM(total_amount), 0) 
        FROM ris_items 
        WHERE ris_form_id = NEW.ris_form_id
    ),
    updated_at = CURRENT_TIMESTAMP
    WHERE id = NEW.ris_form_id;
END//
DELIMITER ;

DELIMITER //
CREATE TRIGGER ris_items_after_delete 
AFTER DELETE ON ris_items
FOR EACH ROW
BEGIN
    UPDATE ris_forms 
    SET total_amount = (
        SELECT COALESCE(SUM(total_amount), 0) 
        FROM ris_items 
        WHERE ris_form_id = OLD.ris_form_id
    ),
    updated_at = CURRENT_TIMESTAMP
    WHERE id = OLD.ris_form_id;
END//
DELIMITER ;

-- Insert sample RIS forms (optional)
-- INSERT INTO ris_forms (
--     ris_no, sai_no, code, division, office, responsibility_center, 
--     date, date_2, purpose, requested_by, requested_by_position, 
--     requested_date, approved_by, approved_by_position, approved_date,
--     issued_by, issued_by_position, issued_date, received_by, 
--     received_by_position, received_date, created_by
-- ) VALUES (
--     'RIS-2024-001', 'SAI-2024-001', 'CODE-2024-001', 
--     'General Services', 'Main Office', 'Supply Office',
--     CURDATE(), CURDATE(), 'Office supplies for monthly operations',
--     'Juan Dela Cruz', 'Office Manager', CURDATE(),
--     'Maria Santos', 'Department Head', CURDATE(),
--     'Pedro Reyes', 'Supply Officer', CURDATE(),
--     'Ana Lopez', 'Clerk', CURDATE(), 1
-- );

-- Insert sample RIS items (optional)
-- INSERT INTO ris_items (ris_form_id, stock_no, unit, description, quantity, price, total_amount)
-- VALUES (1, 1, 'pcs', 'Bond Paper A4', 10, 150.00, 1500.00),
--        (1, 2, 'boxes', 'Paper Clips', 5, 25.00, 125.00),
--        (1, 3, 'bottles', 'Inkjet Printer Ink', 2, 850.00, 1700.00);

-- Create view for RIS summary
CREATE VIEW ris_summary AS
SELECT 
    rf.id,
    rf.ris_no,
    rf.sai_no,
    rf.code,
    rf.division,
    rf.office,
    rf.responsibility_center,
    rf.date,
    rf.purpose,
    rf.status,
    rf.total_amount,
    COUNT(ri.id) as item_count,
    rf.created_by,
    u.first_name,
    u.last_name,
    rf.created_at,
    rf.updated_at
FROM ris_forms rf
LEFT JOIN ris_items ri ON rf.id = ri.ris_form_id
LEFT JOIN users u ON rf.created_by = u.id
GROUP BY rf.id;
