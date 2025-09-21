# HR Management System

A comprehensive Human Resource Management System built with PHP 8+ and Tailwind CSS.

## Features

- **Dashboard with Sidebar Navigation**

  - Clean, modern interface using Tailwind CSS
  - Responsive design that works on all devices
  - Smooth transitions and hover effects

- **Employees Section**

  - Complete employee listing with JOIN queries
  - Employee statistics (Total, Active, Inactive, Offices)
  - Search functionality (by name, email, office)
  - Filter by status (Active/Inactive)
  - Displays: Full name, Email, Office, Designation, Role, Leave count, Status, Join date

- **Leave Table Section**
  - Comprehensive leave requests with JOIN queries
  - Leave statistics (Total, Pending, Approved, Rejected, This Month)
  - Advanced filtering (Status, Date ranges)
  - Search functionality (by employee, leave type, reason)
  - Displays: Employee info, Leave type, Duration, Dates, Reason, Status, Filed date

## Requirements

- PHP 8.0 or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx)
- Modern web browser

## Installation

1.  **Database Setup**

    - Import the provided `db_hr (2).sql` file into your MySQL database
    - The database name should be `db_hr`

2.  **File Structure**

    ```
    hr2/
    ├── index.php (Entry point - redirects to login/dashboard)
    ├── login.php (Login page with authentication)
    ├── dashboard.php (Main dashboard with session management)
    ├── logout.php (Logout handler)
    ├── setup.php (One-time database setup for passwords)
    ├── example-page.php (Example page showing component usage)
    ├── components/
    │   ├── sidebar.php (Reusable sidebar component)
    │   └── header.php (Reusable header component)
    ├── classes/
    │   └── User.php (User authentication and authorization class)
    ├── config/
    │   └── database.php (Database configuration)
    └── sections/
        ├── employees.php (Employees section with access control)
        └── leaves.php (Leave table section with access control)
    ```

3.  **Configuration**

    - Update database credentials in `config/database.php` if needed
    - Default settings:
      - Host: localhost
      - Database: db_hr
      - Username: root
      - Password: (empty)

4.  **Password Setup**

    - Run `http://localhost/hr2/setup.php` once after importing the database
    - This will set up proper password hashing for all accounts

5.  **Access**
    - Navigate to `http://localhost/hr2/` in your browser
    - You will be redirected to the login page
    - Use the demo credentials below to login

## Authentication & Security

- **Session-based authentication** with secure session configuration
- **Password hashing** using PHP's `password_hash()` function
- **Secure login system** with proper validation
- **Session protection** with httpOnly, secure, and SameSite policies
- **Automatic redirects** between login and dashboard
- **Role-based access control** with User class architecture

## Access Control & User Privileges

The system implements comprehensive access control based on user designation and roles:

### **User Roles & Designation-Based Restrictions**

#### **Designation ID = 2 (Administrators/HR)**

- **Full Access**: Can view all employees and leaves from all offices
- **Global View**: See system-wide data and statistics
- **No Restrictions**: Access to all sections and functionality

#### **Designation ID = 6 (Restricted Users)**

- **Complete Restriction**: Blocked from accessing employees and leaves sections
- **Dashboard Access**: Denied access to main dashboard
- **Auto Redirect**: Automatically redirected to login with access denied message
- **Error Message**: Displays "Access denied: Insufficient privileges"

#### **Other Designations (Regular Employees)**

- **Office-Filtered Access**: Can only view employees and leaves from their own office
- **Limited Scope**: Data filtered by `office_id` to match user's office
- **Section Access**: Can access both employees and leaves sections with restrictions

### **Data Filtering Logic**

#### **Employees Section**

```php
// Administrators (designation_id = 2)
SELECT * FROM tbl_users ORDER BY first_name, last_name

// Regular Users (other designations)
SELECT * FROM tbl_users WHERE office_id = [user_office_id] ORDER BY first_name, last_name

// Restricted Users (designation_id = 6)
Access Denied - Cannot view section
```

#### **Leaves Section**

```php
// Administrators (designation_id = 2)
SELECT * FROM tbl_leaves l JOIN tbl_users u ON l.user_id = u.user_id ORDER BY l.filed_at DESC

// Regular Users (other designations)
SELECT * FROM tbl_leaves l JOIN tbl_users u ON l.user_id = u.user_id
WHERE u.office_id = [user_office_id] ORDER BY l.filed_at DESC

// Restricted Users (designation_id = 6)
Access Denied - Cannot view section
```

### **Security Implementation**

#### **Multi-Layer Authentication**

1. **Session Validation**: Secure session with proper cookie settings
2. **User Object Validation**: User class validates session and loads user data
3. **Role-Based Filtering**: Data queries filtered based on user permissions
4. **Access Control**: Section-level restrictions for unauthorized roles

#### **User Class Architecture**

- **Centralized Authentication**: All authentication logic in User class
- **Secure Sessions**: Automatic session security configuration
- **Permission Methods**: Built-in methods for checking user capabilities
- **Query Building**: Dynamic query building based on user permissions

### **Access Control Features**

#### **Unauthenticated Users**

- Redirected to login page
- Cannot access any protected sections
- Session automatically configured when accessing User class

#### **Session Security**

- **Cookie Lifetime**: 1 day (86400 seconds)
- **HTTP Only**: Prevents JavaScript access to cookies
- **Secure Flag**: Requires HTTPS for cookie transmission
- **SameSite Policy**: Strict policy prevents CSRF attacks
- **Session Fixation Protection**: Prevents session hijacking

## Database Structure

The system uses the following tables with proper relationships:

- `tbl_users` - Employee information
- `tbl_offices` - Office/Department information
- `tbl_designations` - Job positions
- `tbl_roles` - User roles (IT Admin, Employee)
- `tbl_leaves` - Leave requests
- `tbl_leave_type` - Types of leaves (Sick, Privilege, Emergency)

## Key Features

### Employee Management

- Complete employee profiles with office and designation
- Role-based categorization
- Activity status tracking
- Leave count monitoring
- **Role-based data filtering** (office-level restrictions for regular users)
- **Access control** (blocked for designation_id = 6)

### Leave Management

- Comprehensive leave request tracking
- Status management (Pending, Approved, Rejected)
- Duration calculation
- Filing date tracking
- **Role-based data filtering** (office-level restrictions for regular users)
- **Access control** (blocked for designation_id = 6)

### User Interface

- Modern, responsive design
- Real-time search and filtering
- Statistics cards for quick overview
- Intuitive navigation
- Professional styling with Tailwind CSS
- **Access denied messages** for unauthorized users

### Access Control System

- **Multi-tier user privileges** based on designation
- **Office-based data filtering** for regular employees
- **Complete section blocking** for restricted users
- **Secure session management** with industry standards
- **Centralized authentication** through User class architecture

## Technical Implementation

- **Backend**: PHP 8+ with PDO for secure database operations
- **Frontend**: HTML5, Tailwind CSS, JavaScript (Vanilla)
- **Database**: MySQL with proper indexing and foreign keys
- **Security**: Prepared statements, HTML escaping, secure sessions
- **Performance**: Optimized queries with JOINs, minimal HTTP requests
- **Architecture**: Object-oriented User class for authentication and authorization
- **Access Control**: Role-based permissions with data filtering
- **Session Management**: Centralized secure session handling

### User Class Features

- **Secure Session Configuration**: Automatic setup of secure session parameters
- **Authentication Methods**: `fromSession()` for validating logged-in users
- **Permission Checking**: Built-in methods like `canViewAllEmployees()`, `canAccessLeaves()`
- **Query Building**: Dynamic SQL generation based on user permissions
- **Data Access Control**: Automatic filtering of data based on user office/role
- **Centralized Logic**: Single source of truth for all user-related operations

## Default Login Credentials (from database)

- Admin: `admin@admin.com` / Password: `admin123` (hashed in DB)
- HR: `hr@hr.com` / Password: `admin123` (hashed in DB)
- CA: `ca@ca.com` / Password: `admin123` (hashed in DB)

_Note: All passwords are hashed using PHP's `password_hash()` function_

## Browser Compatibility

- Chrome 70+
- Firefox 65+
- Safari 12+
- Edge 79+

## License

This project is developed for educational and professional use.
