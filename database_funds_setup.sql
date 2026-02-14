-- Funds Management Database Setup for PIMS
-- This script creates the necessary tables for managing funds/fund clusters

-- Create funds table
CREATE TABLE IF NOT EXISTS funds (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fund_code VARCHAR(50) UNIQUE NOT NULL,
    fund_name VARCHAR(255) NOT NULL,
    fund_cluster VARCHAR(100) NOT NULL,
    description TEXT,
    department VARCHAR(255),
    budget_year INT NOT NULL,
    initial_amount DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    current_balance DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    status ENUM('active', 'inactive', 'closed') DEFAULT 'active',
    start_date DATE,
    end_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT,
    updated_by INT,
    INDEX idx_fund_code (fund_code),
    INDEX idx_fund_cluster (fund_cluster),
    INDEX idx_budget_year (budget_year),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create fund_transactions table for tracking all fund movements
CREATE TABLE IF NOT EXISTS fund_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fund_id INT NOT NULL,
    transaction_type ENUM('allocation', 'expenditure', 'transfer', 'adjustment', 'reversal') NOT NULL,
    transaction_no VARCHAR(50) UNIQUE NOT NULL,
    reference_no VARCHAR(100),
    description TEXT NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    balance_before DECIMAL(15,2) NOT NULL,
    balance_after DECIMAL(15,2) NOT NULL,
    related_form_type VARCHAR(50),
    related_form_id INT,
    office_id INT,
    transaction_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_by INT,
    FOREIGN KEY (fund_id) REFERENCES funds(id) ON DELETE RESTRICT,
    INDEX idx_fund_id (fund_id),
    INDEX idx_transaction_type (transaction_type),
    INDEX idx_transaction_date (transaction_date),
    INDEX idx_related_form (related_form_type, related_form_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create fund_allocations table for tracking office allocations
CREATE TABLE IF NOT EXISTS fund_allocations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fund_id INT NOT NULL,
    office_id INT NOT NULL,
    allocated_amount DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    utilized_amount DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    remaining_balance DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    allocation_date DATE NOT NULL,
    status ENUM('active', 'inactive', 'closed') DEFAULT 'active',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT,
    updated_by INT,
    FOREIGN KEY (fund_id) REFERENCES funds(id) ON DELETE RESTRICT,
    INDEX idx_fund_office (fund_id, office_id),
    INDEX idx_allocation_date (allocation_date),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create view for fund summary
CREATE OR REPLACE VIEW fund_summary AS
SELECT 
    f.id,
    f.fund_code,
    f.fund_name,
    f.fund_cluster,
    f.description,
    f.department,
    f.budget_year,
    f.initial_amount,
    f.current_balance,
    f.status,
    f.start_date,
    f.end_date,
    COUNT(ft.id) as transaction_count,
    COALESCE(SUM(CASE WHEN ft.transaction_type = 'expenditure' THEN ft.amount ELSE 0 END), 0) as total_expenditures,
    COALESCE(SUM(CASE WHEN ft.transaction_type = 'allocation' THEN ft.amount ELSE 0 END), 0) as total_allocations,
    f.created_at,
    f.updated_at
FROM funds f
LEFT JOIN fund_transactions ft ON f.id = ft.fund_id
GROUP BY f.id;

-- Create view for fund allocation summary
CREATE OR REPLACE VIEW fund_allocation_summary AS
SELECT 
    fa.id,
    fa.fund_id,
    fa.office_id,
    o.office_name,
    f.fund_code,
    f.fund_name,
    f.fund_cluster,
    fa.allocated_amount,
    fa.utilized_amount,
    fa.remaining_balance,
    fa.allocation_date,
    fa.status,
    ROUND((fa.utilized_amount / fa.allocated_amount) * 100, 2) as utilization_percentage,
    fa.created_at,
    fa.updated_at
FROM fund_allocations fa
JOIN funds f ON fa.fund_id = f.id
JOIN offices o ON fa.office_id = o.id;

-- Insert default fund data
INSERT IGNORE INTO funds (fund_code, fund_name, fund_cluster, description, department, budget_year, initial_amount, current_balance, start_date, end_date) VALUES
('GEN-2025', 'General Fund 2025', 'General Fund', 'General fund for municipal operations', 'General Administration', 2025, 5000000.00, 5000000.00, '2025-01-01', '2025-12-31'),
('SEF-2025', 'Special Education Fund 2025', 'SEF', 'Special education fund for school operations', 'Education', 2025, 2000000.00, 2000000.00, '2025-01-01', '2025-12-31'),
('LGGF-2025', 'Local Government Development Fund 2025', 'LGGF', 'Local government development fund', 'Development', 2025, 3000000.00, 3000000.00, '2025-01-01', '2025-12-31'),
('TRUST-2025', 'Trust Fund 2025', 'Trust Fund', 'Trust fund for specific purposes', 'Finance', 2025, 1500000.00, 1500000.00, '2025-01-01', '2025-12-31'),
('INFRA-2025', 'Infrastructure Fund 2025', 'Infrastructure', 'Infrastructure development fund', 'Engineering', 2025, 8000000.00, 8000000.00, '2025-01-01', '2025-12-31');

-- Create trigger to update fund balance when transaction is added
CREATE TRIGGER update_fund_balance_after_transaction
AFTER INSERT ON fund_transactions
FOR EACH ROW
BEGIN
    UPDATE funds 
    SET current_balance = NEW.balance_after,
        updated_at = CURRENT_TIMESTAMP
    WHERE id = NEW.fund_id;
END;

-- Create trigger to update allocation utilized amount
CREATE TRIGGER update_allocation_utilization
AFTER INSERT ON fund_transactions
FOR EACH ROW
BEGIN
    IF NEW.related_form_type IN ('PAR', 'ICS', 'ITR', 'RIS') AND NEW.office_id IS NOT NULL THEN
        UPDATE fund_allocations 
        SET utilized_amount = utilized_amount + NEW.amount,
            remaining_balance = allocated_amount - (utilized_amount + NEW.amount),
            updated_at = CURRENT_TIMESTAMP
        WHERE fund_id = NEW.fund_id AND office_id = NEW.office_id AND status = 'active';
    END IF;
END;
