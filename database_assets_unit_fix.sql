-- Fix missing unit field in assets table
-- This script adds the unit field that should have been included in the original assets table

USE pims;

-- Add unit field to assets table
ALTER TABLE assets ADD COLUMN unit VARCHAR(50) NOT NULL DEFAULT 'pcs' AFTER quantity;

-- Update existing assets to have a default unit
UPDATE assets SET unit = 'pcs' WHERE unit IS NULL OR unit = '';

-- Add status field to assets table (for overall asset status)
ALTER TABLE assets ADD COLUMN status ENUM('active', 'inactive', 'disposed') DEFAULT 'active' AFTER office_id;

-- Update existing assets to have active status
UPDATE assets SET status = 'active' WHERE status IS NULL;

-- Display the updated table structure
DESCRIBE assets;
