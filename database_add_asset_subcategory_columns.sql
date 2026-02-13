-- Add asset_subcategory_id column to assets and asset_items tables
-- This script adds foreign key references to the asset_sub_categories table

USE pims;

-- Add asset_subcategory_id column to assets table
ALTER TABLE assets 
ADD COLUMN asset_subcategory_id INT NULL 
AFTER asset_categories_id;

-- Add foreign key constraint for assets table
ALTER TABLE assets 
ADD CONSTRAINT fk_assets_asset_subcategory 
FOREIGN KEY (asset_subcategory_id) REFERENCES asset_sub_categories(id) 
ON DELETE SET NULL ON UPDATE CASCADE;

-- Add asset_subcategory_id column to asset_items table
ALTER TABLE asset_items 
ADD COLUMN asset_subcategory_id INT NULL 
AFTER asset_id;

-- Add foreign key constraint for asset_items table
ALTER TABLE asset_items 
ADD CONSTRAINT fk_asset_items_asset_subcategory 
FOREIGN KEY (asset_subcategory_id) REFERENCES asset_sub_categories(id) 
ON DELETE SET NULL ON UPDATE CASCADE;

-- Add index for better performance on assets table
ALTER TABLE assets 
ADD INDEX idx_asset_subcategory_id (asset_subcategory_id);

-- Add index for better performance on asset_items table
ALTER TABLE asset_items 
ADD INDEX idx_asset_subcategory_id (asset_subcategory_id);

-- Update existing records to set default subcategory values (optional)
-- This sets existing assets to have a default subcategory if available
-- You can modify these values based on your actual data

-- Example: Update IT assets to have 'Desktop Computers' subcategory (id=1)
UPDATE assets a 
SET asset_subcategory_id = (
    SELECT id FROM asset_sub_categories 
    WHERE sub_category_code = 'DC' AND asset_categories_id = a.asset_categories_id 
    LIMIT 1
) 
WHERE asset_categories_id = 1 AND asset_subcategory_id IS NULL;

-- Example: Update Furniture assets to have 'Office Chairs' subcategory (id=5)
UPDATE assets a 
SET asset_subcategory_id = (
    SELECT id FROM asset_sub_categories 
    WHERE sub_category_code = 'OC' AND asset_categories_id = a.asset_categories_id 
    LIMIT 1
) 
WHERE asset_categories_id = 2 AND asset_subcategory_id IS NULL;

-- Example: Update Vehicle assets to have 'Vehicles' subcategory (id=8)
UPDATE assets a 
SET asset_subcategory_id = (
    SELECT id FROM asset_sub_categories 
    WHERE sub_category_code = 'VH' AND asset_categories_id = a.asset_categories_id 
    LIMIT 1
) 
WHERE asset_categories_id = 3 AND asset_subcategory_id IS NULL;

-- Copy subcategory from assets to asset_items where applicable
UPDATE asset_items ai 
SET asset_subcategory_id = (
    SELECT asset_subcategory_id FROM assets 
    WHERE id = ai.asset_id
) 
WHERE asset_subcategory_id IS NULL AND asset_id IN (
    SELECT id FROM assets WHERE asset_subcategory_id IS NOT NULL
);

COMMIT;
