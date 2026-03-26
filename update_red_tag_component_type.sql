-- Update red_tags table to include peripheral component type
ALTER TABLE `red_tags` 
MODIFY COLUMN `component_type` enum('main_asset','monitor','ups','peripheral') DEFAULT 'main_asset';
