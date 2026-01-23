-- Add property_number column to asset_items table
-- This migration adds the property_number column to support property number tracking

USE pims;

-- Add property_number column to asset_items table
ALTER TABLE asset_items 
ADD COLUMN property_number VARCHAR(100) DEFAULT NULL AFTER description;

-- Add index for better performance
CREATE INDEX idx_asset_items_property_number ON asset_items(property_number);

-- Add unique constraint to prevent duplicate property numbers (optional)
-- ALTER TABLE asset_items ADD UNIQUE KEY idx_unique_property_number (property_number);
