# 🏗️ Property Management System with CSUSHPINSA Integration

## 📋 Table of Contents
- [Overview](#-overview)
- [User Roles](#-user-roles)
- [Client Management](#-client-management)
- [Property Management](#-property-management)
- [CSUSHPINSA Integration](#-csushpinsa-integration)
- [Reporting & Dashboard](#-reporting--dashboard)
- [Email Notifications](#-email-notifications)
- [Admin Dashboard](#-admin-dashboard)
- [Technical Stack](#-technical-stack)
- [Future Improvements](#-future-improvements)
- [Development Phases](#-development-phases)
- [Contact](#-contact)
- [Database Structure](#-database-structure)

## 🌟 Overview

A comprehensive web-based system for managing real estate clients and properties, featuring CSUSHPINSA index integration, automated reporting, and role-based access control.

### Key Features
- 📊 Real-time property valuation tracking
- 👥 Multi-role user management
- 📈 Automated reporting and analytics
- 📧 Email notification system
- 🔄 CSUSHPINSA index integration
- 📱 Responsive design

## 👥 User Roles

### 1. Administrator
- **Access Level:** Full system access
- **Capabilities:**
  - Client & property management (CRUD operations)
  - System configuration
  - User management
  - Report generation
  - Email notifications
  - Global dashboard access

### 2. Property Owner
- **Access Level:** Limited access
- **Capabilities:**
  - View assigned properties
  - Receive email notifications
  - Access restricted views

### 3. View Only
- **Access Level:** Read-only
- **Capabilities:**
  - View-only access to permitted data
  - No editing rights
  - No notifications

## 👤 Client Management

### Data Requirements
| Field | Required | Type | Description |
|-------|----------|------|-------------|
| Name | Yes | Text | Full name or company name |
| Email | Yes | Email | Primary contact email |
| Phone | Yes | Text | Contact number |
| Address | No | Text | Physical address |
| Status | Yes | Enum | Active/Archived |

### Features
- Client registration and management
- Soft delete functionality
- Email notifications
- Property association

## 🏡 Property Management

### Property Details
| Field | Required | Type | Description |
|-------|----------|------|-------------|
| Address | Yes | Text | Property location |
| Initial Valuation | Yes | Decimal | Starting value |
| Agreed Pct | Yes | Decimal | Agreement percentage |
| Total Fees | Yes | Decimal | Associated fees |
| Effective Date | Yes | Date | Start date |
| Term | Yes | Integer | Duration in months |
| Option Price | Yes | Decimal | Option cost |

### Features
- Property registration and tracking
- Automated valuation updates
- Client association
- Email notifications

## 🌐 CSUSHPINSA Integration

### API Configuration
- **Provider:** FRED (Federal Reserve Economic Data)
- **API Key:** `bf7de8e5b6c4328f21855e67a5bdd8f2`
- **Endpoint:** [CSUSHPINSA Index](https://fred.stlouisfed.org/series/CSUSHPINSA)

### Features
- Historical data retrieval
- Automated updates
- Data storage and management
- Integration with property valuations

## 📊 Reporting & Dashboard

### Report Types
1. **Property Valuation Reports**
   - Current value
   - Appreciation tracking
   - Share calculations
   - Terminal value projections

2. **Data Analysis**
   - CSV file import
   - CSUSHPINSA index comparison
   - Interactive visualizations
   - Export capabilities (PDF/Excel)

## 📧 Email Notifications

### Notification Events
- Client registration
- Property assignment
- System updates
- Report generation

### Email Content
- Entity details
- Creation timestamp
- Action buttons
- System links

## 🖥️ Admin Dashboard

### Dashboard Components
- Activity feed
- Property overview
- Client management
- System configuration
- Report generation
- User management

## 💻 Technical Stack

### Frontend
- html, CSS JAvascriot

### Backend
- PHP, JavaScript
- Database: MySQL

### Integration
- Email: PHPMailer
- API: GuzzleHttp


## 🚀 Future Improvements



## 📅 Development Timeline

| Phase | Description | Status | Timeline |
|-------|-------------|--------|----------|
| 1 | Database & User Roles | Pending | Q2 2024 |
| 2 | Client & Property CRUD | Pending | Q2 2024 |
| 3 | CSUSHPINSA Integration | Pending | Q3 2024 |
| 4 | Reporting Module | Pending | Q3 2024 |
| 5 | Admin Configuration | Pending | Q4 2024 |
| 6 | Email System | Pending | Q4 2024 |
| 7 | Testing & Deployment | Pending | Q1 2025 |



*Last Updated: April 2024*

## 💾 Database Structure

### Users Table
```sql
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    role ENUM('admin', 'property_owner', 'view_only') NOT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

### Clients Table
```sql
CREATE TABLE clients (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    address TEXT,
    status ENUM('active', 'archived') DEFAULT 'active',
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id)
);
```

### Properties Table
```sql
CREATE TABLE properties (
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
    FOREIGN KEY (client_id) REFERENCES clients(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
);
```

### Home Price Index Table
```sql
CREATE TABLE home_price_index (
    id INT PRIMARY KEY AUTO_INCREMENT,
    date DATE NOT NULL,
    value DECIMAL(15,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_date (date)
);
```

### Property Valuations Table
```sql
CREATE TABLE property_valuations (
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
    FOREIGN KEY (property_id) REFERENCES properties(id)
);
```

### Email Notifications Table
```sql
CREATE TABLE email_notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    recipient_id INT NOT NULL,
    subject VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    status ENUM('pending', 'sent', 'failed') DEFAULT 'pending',
    sent_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (recipient_id) REFERENCES users(id)
);
```

### Activity Log Table
```sql
CREATE TABLE activity_log (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    action VARCHAR(50) NOT NULL,
    entity_type VARCHAR(50) NOT NULL,
    entity_id INT NOT NULL,
    details TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);
```

### Database Relationships
1. **Users to Clients**
   - One-to-Many: One user can create many clients
   - Foreign Key: clients.created_by → users.id

2. **Clients to Properties**
   - One-to-Many: One client can have many properties
   - Foreign Key: properties.client_id → clients.id

3. **Users to Properties**
   - One-to-Many: One user can create many properties
   - Foreign Key: properties.created_by → users.id

4. **Properties to Valuations**
   - One-to-Many: One property can have many valuations
   - Foreign Key: property_valuations.property_id → properties.id

5. **Users to Notifications**
   - One-to-Many: One user can have many notifications
   - Foreign Key: email_notifications.recipient_id → users.id

6. **Users to Activity Log**
   - One-to-Many: One user can have many activity logs
   - Foreign Key: activity_log.user_id → users.id

### Indexes
```sql
-- Users table indexes
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_role ON users(role);
CREATE INDEX idx_users_status ON users(status);

-- Clients table indexes
CREATE INDEX idx_clients_name ON clients(name);
CREATE INDEX idx_clients_email ON clients(email);
CREATE INDEX idx_clients_status ON clients(status);

-- Properties table indexes
CREATE INDEX idx_properties_client ON properties(client_id);
CREATE INDEX idx_properties_status ON properties(status);
CREATE INDEX idx_properties_effective_date ON properties(effective_date);

-- Property Valuations indexes
CREATE INDEX idx_valuations_property ON property_valuations(property_id);
CREATE INDEX idx_valuations_date ON property_valuations(valuation_date);

-- Activity Log indexes
CREATE INDEX idx_activity_user ON activity_log(user_id);
CREATE INDEX idx_activity_entity ON activity_log(entity_type, entity_id);
CREATE INDEX idx_activity_date ON activity_log(created_at);
```

### Database Features
1. **Data Integrity**
   - Foreign key constraints
   - Unique constraints
   - NOT NULL constraints
   - ENUM type validations

2. **Performance**
   - Strategic indexes
   - Optimized table structure
   - Efficient data types

3. **Security**
   - Password hashing
   - Role-based access
   - Activity logging

4. **Maintenance**
   - Timestamp tracking
   - Soft delete capability
   - Status tracking