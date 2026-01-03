# Pilar Inventory Management System (PIMS)

A comprehensive inventory management system with role-based access control.

## Setup Instructions

### 1. Database Setup
1. Import the database structure by running the `database_setup.sql` script in your MySQL database:
   ```sql
   mysql -u root -p < database_setup.sql
   ```

### 2. Default Login Credentials
- **Username:** `system_admin`
- **Password:** `admin123`

### 3. User Roles
- **system_admin:** Full system access and user management
- **admin:** Administrative access to inventory
- **office_admin:** Office-level inventory management
- **user:** Basic inventory access

### 4. Directory Structure
```
PIMS/
├── index.php                 # Login page
├── config.php               # Database configuration
├── database_setup.sql       # Database setup script
├── logout.php              # Logout script
├── SYSTEM_ADMIN/           # System admin dashboard
├── ADMIN/                  # Admin dashboard (to be created)
├── OFFICE_ADMIN/           # Office admin dashboard (to be created)
└── USER/                   # User dashboard (to be created)
```

### 5. Features
- Secure login with password hashing
- Role-based authentication and authorization
- Session management
- Bootstrap 5 responsive design
- MySQL database backend
- Prepared statements for security

### 6. Security Features
- Password hashing using PHP's `password_hash()`
- SQL injection prevention with prepared statements
- Session-based authentication
- Role-based access control
- Input sanitization with `htmlspecialchars()`

## Getting Started
1. Ensure you have XAMPP/WAMP/MAMP installed with MySQL and Apache
2. Place the PIMS folder in your web directory (htdocs)
3. Import the database setup script
4. Navigate to `http://localhost/PIMS` in your browser
5. Login with the default system admin credentials

## Next Steps
- Create additional dashboard pages for each role
- Implement user management functionality
- Add inventory management features
- Create reporting modules
