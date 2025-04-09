# Micro Home Equity Investment - User Guide

## 1. Introduction

Welcome to the Micro Home Equity Investment platform. This application is designed to help manage clients, properties, and their associated financial data, particularly focusing on property valuations and investment tracking.

### User Roles

There are two primary user roles in this system:

*   **Administrator:** Has full control over the system, including managing users, clients, properties, reports, and system settings.
*   **Client/Property Owner:** Can log in to view their dashboard, see details about their properties, track valuation history, and manage their profile.

## 2. Getting Started

### Accessing the Application

Open your web browser and navigate to the base URL provided for the application (e.g., `http://yourdomain.com/realestate/TEST/`).

### Logging In

1.  Navigate to the login page (usually the default page or accessible via a 'Login' link).
2.  Enter your assigned **Username** and **Password**.
3.  Click the **Sign In** button.
4.  Upon successful login, you will be redirected to your specific dashboard based on your role.

### Understanding the Interface

*   **Header:** Located at the top, contains the application logo, name, navigation links (Dashboard, Management for Admins), and user profile options (Profile, Settings, Logout).
*   **Main Content Area:** Displays the primary information for the current page (e.g., dashboard widgets, tables of data, forms).
*   **Footer:** Located at the bottom, displays copyright information and the application version.

## 3. User Dashboard (Clients/Property Owners)

After logging in, clients/property owners are directed to their dashboard, which provides an overview of their properties and related information.

### Dashboard Overview (`dashboard.php`)

*   **Statistics:** Displays key metrics such as:
    *   Total Properties
    *   Total Property Value
    *   Average Appreciation
    *   Total Valuations
*   **Recent Activity:** Shows recent actions related to your properties.
*   **Recent Properties:** Lists your most recently added or updated properties with a summary.

### Viewing Your Properties (`my_properties.php`)

Navigate to the 'My Properties' section (link might be in the header or dashboard).

*   **View Toggle:** You can switch between viewing your properties as **Cards** or in a **Table** format using the toggle buttons at the top.
*   **Card View:** Displays each property in a separate card, showing key details like address, value, and status. Includes a 'View Details' button.
*   **Table View:** Presents properties in a structured table with columns for Address, Initial Value, Agreed Percentage, Effective Date, Term, Status, and Actions (like 'View Details').
*   **Viewing Details:** Clicking 'View Details' (in either view) will likely take you to a page or modal showing more comprehensive information about the specific property, including its valuation history.
*   **Valuation History:** When viewing property details, you can access the valuation history, typically displayed in a table or chart format, showing valuation dates, index values, appreciation, and any associated notes.

### Managing Your Profile

1.  Click on your username in the top-right corner of the header.
2.  Select **Profile** from the dropdown menu.
3.  On the Profile page (`profile.php` - likely shared with admins):
    *   Your email address is displayed (usually non-editable).
    *   **Change Password:** You can update your password by entering your Current Password, the New Password, and confirming the New Password. Leave these fields blank if you don't want to change your password.
4.  Click **Update Profile** to save changes.

## 4. Admin Dashboard

Administrators have access to a comprehensive dashboard and management tools to oversee the entire application.

### Admin Dashboard Overview (`dashboard.php`)

*   **Welcome Section:** Greets the administrator.
*   **Quick Actions:** Provides buttons for quickly navigating to key management sections:
    *   Manage Users
    *   Manage Clients
    *   Manage Properties
    *   View Reports
*   **Statistics:** Displays key system-wide metrics:
    *   Active Clients
    *   Active Properties
    *   Active Users
    *   Total Valuations
*   **Recent Activity:** Shows a log of recent actions performed by users across the system.
*   **Recent Properties:** Lists the most recently added or updated properties in the system.

### Managing Users (`users.php`)

*   **View Users:** Displays a table of all registered users with their username, email, role, and status.
*   **Add User:** Allows adding a new user by providing username, email, password, and role.
*   **Edit User:** Enables modification of an existing user's details (username, email, role, status). Password changes might be restricted or require a separate process.
*   **Delete User:** Removes a user from the system (may be a soft delete or permanent).

### Managing Clients (`clients.php`)

*   **View Clients:** Shows a list of all clients with details like name, email, phone, address, and status.
*   **Add Client:** Allows creating a new client record, potentially linking them to a new user account with a 'property_owner' role. Includes fields for contact information and address.
    *   **Welcome Email:** Upon creation, the new client receives a welcome email with their login credentials (username and generated password).
    *   **Admin Notification:** All administrators receive an email notification about the new client registration.
*   **Edit Client:** Allows updating client information.
*   **View Client Details:** Shows comprehensive information about a specific client, potentially including linked properties.

### Managing Properties (`properties.php`)

*   **View Properties:** Displays a table of all properties, often filterable or searchable, showing address, client, initial valuation, status, etc.
*   **Add Property:** Allows adding a new property, linking it to a client, and entering details like address, initial valuation, agreed percentage, term, and status.
*   **Edit Property:** Enables modification of property details.
*   **View Property Details:** Shows comprehensive information for a specific property, including:
    *   **Valuation History:** Track changes in property value over time. Admins can likely add new valuations (`update_valuation.php`).
    *   **Documents:** Manage documents associated with the property (uploading via `upload_document.php`, deleting via `delete_document.php`).

### Generating Reports (`reports.php`)

*   Provides options to generate various reports based on system data (e.g., client reports, property reports, valuation summaries).
*   Allows filtering reports by date ranges or other criteria.
*   Offers options to export reports in different formats (e.g., CSV, PDF - likely using `ReportExporter.php`).

### System Settings (`settings.php`)

*   Allows administrators to configure various application settings, such as:
    *   Application Name (`app_name`)
    *   Base URL (`base_url`)
    *   Email configuration
    *   Other system parameters.

### Viewing Activity Logs (`activity_log.php` / `logs.php`)

*   Displays a detailed log of actions performed within the application for auditing and monitoring purposes.
*   May offer filtering or searching capabilities.

### Performing Backups (`backup.php`)

*   Provides functionality to create backups of the application's database or files.
*   May include options for scheduling or downloading backup files.

### Managing Your Admin Profile (`profile.php`)

*   Accessed via the user dropdown in the header.
*   Allows administrators to update their own email address and change their password, similar to regular users.

## 5. Logging Out

1.  Click on your username in the top-right corner of the header.
2.  Select **Logout** from the dropdown menu.
3.  You will be logged out and redirected to the login page. 