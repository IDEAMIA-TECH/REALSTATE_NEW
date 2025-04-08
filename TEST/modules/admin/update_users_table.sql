-- Add first_name and last_name columns to users table
ALTER TABLE users
ADD COLUMN first_name VARCHAR(50) NOT NULL AFTER id,
ADD COLUMN last_name VARCHAR(50) NOT NULL AFTER first_name; 