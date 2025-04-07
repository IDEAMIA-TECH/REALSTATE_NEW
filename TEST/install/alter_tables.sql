-- Add initial_index column to properties table
ALTER TABLE properties
ADD COLUMN initial_index DECIMAL(15,2) DEFAULT 0 AFTER created_by;

-- Add closing index and date columns to properties table
ALTER TABLE properties
ADD COLUMN closing_index DECIMAL(15,2) DEFAULT NULL AFTER initial_index,
ADD COLUMN closing_date DATE DEFAULT NULL AFTER closing_index; 