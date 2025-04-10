-- Add initial_index column to properties table
ALTER TABLE properties
ADD COLUMN initial_index DECIMAL(15,2) DEFAULT 0 AFTER created_by,
ADD COLUMN initial_index_date DATE DEFAULT NULL AFTER initial_index;

-- Add closing index and date columns to properties table
ALTER TABLE properties
ADD COLUMN closing_index DECIMAL(15,2) DEFAULT NULL AFTER initial_index_date,
ADD COLUMN closing_date DATE DEFAULT NULL AFTER closing_index,
ADD COLUMN closing_index_date DATE DEFAULT NULL AFTER closing_date;

-- Split address into separate fields
ALTER TABLE properties
ADD COLUMN street_address VARCHAR(255) AFTER client_id,
ADD COLUMN city VARCHAR(100) AFTER street_address,
ADD COLUMN state VARCHAR(50) AFTER city,
ADD COLUMN zip_code VARCHAR(20) AFTER state,
ADD COLUMN country VARCHAR(100) DEFAULT 'United States' AFTER zip_code;

-- Update existing addresses
UPDATE properties SET 
    street_address = SUBSTRING_INDEX(address, ',', 1),
    city = TRIM(SUBSTRING_INDEX(SUBSTRING_INDEX(address, ',', 2), ',', -1)),
    state = TRIM(SUBSTRING_INDEX(SUBSTRING_INDEX(address, ',', 3), ',', -1)),
    zip_code = TRIM(SUBSTRING_INDEX(address, ',', -1))
WHERE address IS NOT NULL;

-- Drop the old address column
ALTER TABLE properties DROP COLUMN address;

-- Create cancellation fees table
CREATE TABLE cancellation_fees (
    id INT AUTO_INCREMENT PRIMARY KEY,
    state VARCHAR(50) NOT NULL,
    region VARCHAR(100),
    fee_percentage DECIMAL(5,2) NOT NULL,
    minimum_fee DECIMAL(10,2) NOT NULL,
    maximum_fee DECIMAL(10,2),
    effective_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT,
    FOREIGN KEY (created_by) REFERENCES users(id),
    UNIQUE KEY unique_state_region_date (state, region, effective_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add cancellation fee reference to properties table
ALTER TABLE properties
ADD COLUMN cancellation_fee_id INT AFTER status,
ADD FOREIGN KEY (cancellation_fee_id) REFERENCES cancellation_fees(id);

-- Modify cancellation_fees table
ALTER TABLE cancellation_fees
DROP COLUMN minimum_fee,
DROP COLUMN maximum_fee,
ADD COLUMN fee_type ENUM('percentage', 'fixed') NOT NULL DEFAULT 'percentage',
ADD COLUMN fee_percentage DECIMAL(5,2) NULL,
ADD COLUMN fixed_fee DECIMAL(10,2) NULL,
ADD CONSTRAINT chk_fee_type CHECK (
    (fee_type = 'percentage' AND fee_percentage IS NOT NULL AND fixed_fee IS NULL) OR
    (fee_type = 'fixed' AND fixed_fee IS NOT NULL AND fee_percentage IS NULL)
);

-- Update existing records to use the new structure
UPDATE cancellation_fees 
SET fee_type = 'percentage',
    fee_percentage = minimum_fee,
    fixed_fee = NULL
WHERE minimum_fee IS NOT NULL;

-- Remove NOT NULL constraint from fee_percentage and fixed_fee
ALTER TABLE cancellation_fees
MODIFY COLUMN fee_percentage DECIMAL(5,2) NULL,
MODIFY COLUMN fixed_fee DECIMAL(10,2) NULL; 