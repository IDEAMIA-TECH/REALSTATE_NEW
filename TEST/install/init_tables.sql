-- Create database if not exists
CREATE DATABASE IF NOT EXISTS ideamiad_realestate CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE ideamiad_realestate;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    role ENUM('admin', 'property_owner', 'view_only') NOT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_users_email (email),
    INDEX idx_users_role (role),
    INDEX idx_users_status (status)
);

-- Clients table
CREATE TABLE IF NOT EXISTS clients (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    address TEXT,
    status ENUM('active', 'archived') DEFAULT 'active',
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_clients_name (name),
    INDEX idx_clients_email (email),
    INDEX idx_clients_status (status)
);

-- Properties table
CREATE TABLE IF NOT EXISTS properties (
    id INT PRIMARY KEY AUTO_INCREMENT,
    client_id INT NOT NULL,
    address TEXT NOT NULL,
    initial_valuation DECIMAL(15,2) NOT NULL,
    agreed_pct DECIMAL(5,2) NOT NULL,
    total_fees DECIMAL(15,2) NOT NULL,
    effective_date DATE NOT NULL,
    term INT NOT NULL,
    option_price DECIMAL(15,2) NOT NULL,
    status ENUM('active', 'archived') DEFAULT 'active',
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_properties_client (client_id),
    INDEX idx_properties_status (status),
    INDEX idx_properties_effective_date (effective_date)
);

-- Home Price Index table
CREATE TABLE IF NOT EXISTS home_price_index (
    id INT PRIMARY KEY AUTO_INCREMENT,
    date DATE NOT NULL,
    value DECIMAL(15,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_date (date)
);

-- Property Valuations table
CREATE TABLE IF NOT EXISTS property_valuations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    property_id INT NOT NULL,
    valuation_date DATE NOT NULL,
    current_value DECIMAL(15,2) NOT NULL,
    appreciation DECIMAL(15,2) NOT NULL,
    share_appreciation DECIMAL(15,2) NOT NULL,
    terminal_value DECIMAL(15,2) NOT NULL,
    projected_payoff DECIMAL(15,2) NOT NULL,
    option_valuation DECIMAL(15,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE,
    INDEX idx_valuations_property (property_id),
    INDEX idx_valuations_date (valuation_date)
);

-- Email Notifications table
CREATE TABLE IF NOT EXISTS email_notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    recipient_id INT NOT NULL,
    subject VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    status ENUM('pending', 'sent', 'failed') DEFAULT 'pending',
    sent_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (recipient_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Activity Log table
CREATE TABLE IF NOT EXISTS activity_log (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    action VARCHAR(50) NOT NULL,
    entity_type VARCHAR(50) NOT NULL,
    entity_id INT,
    details TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_activity_user (user_id),
    INDEX idx_activity_entity (entity_type, entity_id),
    INDEX idx_activity_date (created_at)
);

-- Create admin user
INSERT INTO users (username, password, email, role) 
VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@example.com', 'admin')
ON DUPLICATE KEY UPDATE id=id;

-- Create test property owner
INSERT INTO users (username, password, email, role) 
VALUES ('owner', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'owner@example.com', 'property_owner')
ON DUPLICATE KEY UPDATE id=id;

-- Create test view only user
INSERT INTO users (username, password, email, role) 
VALUES ('viewer', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'viewer@example.com', 'view_only')
ON DUPLICATE KEY UPDATE id=id; 