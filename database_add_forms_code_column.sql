-- Add numeric code column to forms table
-- This adds a separate column for digit codes alongside the existing form_code

ALTER TABLE forms ADD COLUMN code VARCHAR(20) DEFAULT NULL AFTER form_code;

-- Add index for the new code column
ALTER TABLE forms ADD INDEX idx_code (code);

-- Update existing forms with numeric codes
UPDATE forms SET code = '01' WHERE form_code = 'PAR';
UPDATE forms SET code = '02' WHERE form_code = 'ICS';
UPDATE forms SET code = '03' WHERE form_code = 'RIS';
UPDATE forms SET code = '04' WHERE form_code = 'JO';
UPDATE forms SET code = '05' WHERE form_code = 'PO';

-- Make the code column unique after updating existing records
ALTER TABLE forms ADD CONSTRAINT uc_forms_code UNIQUE (code);
