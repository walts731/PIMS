-- Alter asset_items.image column to TEXT to support multiple images as JSON array
ALTER TABLE asset_items MODIFY COLUMN image TEXT DEFAULT NULL;
