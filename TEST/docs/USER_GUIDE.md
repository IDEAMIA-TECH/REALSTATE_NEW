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

*(Content to be added)*

## 5. Logging Out

1.  Click on your username in the top-right corner of the header.
2.  Select **Logout** from the dropdown menu.
3.  You will be logged out and redirected to the login page. 