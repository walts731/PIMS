-- SQL Script to check and populate fuel_transactions table
-- Run this in phpMyAdmin or MySQL command line

-- 1. Check if table exists
SHOW TABLES LIKE 'fuel_transactions';

-- 2. Check table structure
DESCRIBE fuel_transactions;

-- 3. Check current data
SELECT * FROM fuel_transactions ORDER BY transaction_date DESC LIMIT 5;

-- 4. Check transaction types
SELECT transaction_type, COUNT(*) as count 
FROM fuel_transactions 
GROUP BY transaction_type;

-- 5. Check specifically for OUT transactions
SELECT * FROM fuel_transactions 
WHERE transaction_type = 'OUT' 
ORDER BY transaction_date DESC 
LIMIT 5;

-- 6. If no data exists, insert sample records
-- Uncomment the following lines if you want to add sample data

-- Sample Fuel OUT transactions
INSERT INTO fuel_transactions (
    transaction_type, 
    fuel_type, 
    quantity, 
    transaction_date, 
    source, 
    employee_id, 
    recipient_name, 
    purpose, 
    user_id, 
    created_at, 
    updated_at
) VALUES 
(
    'OUT', 
    'diesel', 
    25.50, 
    '2026-03-16 10:30:00', 
    'Main Tank', 
    1001, 
    'John Doe', 
    'Vehicle refueling for delivery truck', 
    1, 
    NOW(), 
    NOW()
),
(
    'OUT', 
    'gasoline', 
    15.75, 
    '2026-03-16 09:15:00', 
    'Generator Tank', 
    1002, 
    'Jane Smith', 
    'Generator fuel for emergency power', 
    1, 
    NOW(), 
    NOW()
),
(
    'OUT', 
    'premium', 
    30.00, 
    '2026-03-16 08:45:00', 
    'Main Tank', 
    1003, 
    'Mike Johnson', 
    'Equipment fueling for maintenance', 
    1, 
    NOW(), 
    NOW()
);

-- Sample Fuel IN transactions (for balance)
INSERT INTO fuel_transactions (
    transaction_type, 
    fuel_type, 
    quantity, 
    transaction_date, 
    source, 
    employee_id, 
    recipient_name, 
    purpose, 
    user_id, 
    created_at, 
    updated_at
) VALUES 
(
    'IN', 
    'diesel', 
    1000.00, 
    '2026-03-16 07:00:00', 
    'Fuel Supplier Co', 
    NULL, 
    'Main Office', 
    'Monthly fuel delivery', 
    1, 
    NOW(), 
    NOW()
),
(
    'IN', 
    'gasoline', 
    500.00, 
    '2026-03-16 06:30:00', 
    'Gas Station', 
    NULL, 
    'Main Office', 
    'Weekly fuel delivery', 
    1, 
    NOW(), 
    NOW()
);

-- 7. Verify data was inserted
SELECT * FROM fuel_transactions 
WHERE transaction_type = 'OUT' 
ORDER BY transaction_date DESC 
LIMIT 5;

-- 8. Check total counts
SELECT 
    transaction_type,
    COUNT(*) as total_count,
    SUM(quantity) as total_quantity
FROM fuel_transactions 
GROUP BY transaction_type;
