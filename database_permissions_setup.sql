-- PIMS Role Permissions Setup Script
-- Create permissions and role permissions tables

USE pims;

-- Disable foreign key checks temporarily
SET FOREIGN_KEY_CHECKS = 0;

-- Drop tables if they exist
DROP TABLE IF EXISTS role_permissions;
DROP TABLE IF EXISTS permissions;

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;

-- Create permissions table
CREATE TABLE permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    module VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create role_permissions table (junction table)
CREATE TABLE role_permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role ENUM('system_admin', 'admin', 'office_admin', 'user') NOT NULL,
    permission_id INT NOT NULL,
    can_create BOOLEAN DEFAULT FALSE,
    can_read BOOLEAN DEFAULT TRUE,
    can_update BOOLEAN DEFAULT FALSE,
    can_delete BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE,
    UNIQUE KEY unique_role_permission (role, permission_id)
);

-- Insert default permissions
INSERT INTO permissions (name, description, module) VALUES
-- User Management permissions
('users.create', 'Create new users', 'users'),
('users.read', 'View users list', 'users'),
('users.update', 'Edit user information', 'users'),
('users.delete', 'Delete users', 'users'),
('users.activate', 'Activate/deactivate users', 'users'),

-- Inventory Management permissions
('inventory.create', 'Add new products', 'inventory'),
('inventory.read', 'View products list', 'inventory'),
('inventory.update', 'Edit product information', 'inventory'),
('inventory.delete', 'Delete products', 'inventory'),
('inventory.transaction.in', 'Add stock (IN transactions)', 'inventory'),
('inventory.transaction.out', 'Remove stock (OUT transactions)', 'inventory'),

-- Category Management permissions
('categories.create', 'Create new categories', 'categories'),
('categories.read', 'View categories list', 'categories'),
('categories.update', 'Edit category information', 'categories'),
('categories.delete', 'Delete categories', 'categories'),

-- Reports permissions
('reports.view', 'View system reports', 'reports'),
('reports.export', 'Export reports', 'reports'),

-- System permissions
('system.settings', 'Access system settings', 'system'),
('system.logs', 'View system logs', 'system'),
('system.backup', 'Create system backup', 'system'),
('system.audit', 'Access security audit', 'system');

-- Insert default role permissions

-- System Admin - Full access to everything
INSERT INTO role_permissions (role, permission_id, can_create, can_read, can_update, can_delete)
SELECT 'system_admin', id, TRUE, TRUE, TRUE, TRUE FROM permissions;

-- Admin - Can manage users, inventory, categories, and view reports
INSERT INTO role_permissions (role, permission_id, can_create, can_read, can_update, can_delete)
SELECT 'admin', id, 
    CASE 
        WHEN name IN ('system.settings', 'system.logs', 'system.backup', 'system.audit') THEN FALSE
        ELSE TRUE
    END,
    TRUE,
    CASE 
        WHEN name IN ('system.settings', 'system.logs', 'system.backup', 'system.audit') THEN FALSE
        ELSE TRUE
    END,
    CASE 
        WHEN name IN ('users.delete', 'system.settings', 'system.logs', 'system.backup', 'system.audit') THEN FALSE
        ELSE TRUE
    END
FROM permissions;

-- Office Admin - Can manage inventory and categories, view limited reports
INSERT INTO role_permissions (role, permission_id, can_create, can_read, can_update, can_delete)
SELECT 'office_admin', id,
    CASE 
        WHEN name IN ('inventory.create', 'inventory.update', 'categories.create', 'categories.update', 
                     'inventory.transaction.in', 'inventory.transaction.out') THEN TRUE
        ELSE FALSE
    END,
    CASE 
        WHEN name IN ('users.read', 'inventory.read', 'categories.read', 'reports.view') THEN TRUE
        ELSE FALSE
    END,
    CASE 
        WHEN name IN ('inventory.update', 'categories.update') THEN TRUE
        ELSE FALSE
    END,
    FALSE
FROM permissions;

-- Regular User - Can read inventory and categories, do transactions
INSERT INTO role_permissions (role, permission_id, can_create, can_read, can_update, can_delete)
SELECT 'user', id,
    CASE 
        WHEN name IN ('inventory.transaction.in', 'inventory.transaction.out') THEN TRUE
        ELSE FALSE
    END,
    CASE 
        WHEN name IN ('inventory.read', 'categories.read') THEN TRUE
        ELSE FALSE
    END,
    FALSE,
    FALSE
FROM permissions;
