# PIMS Borrowing System Architecture

## Overview
High-level system architecture for the borrowing page `/PIMS/MAIN_USER/assets.php?office_id=1&status=borrowed` in the Property Inventory and Management System (PIMS).

## System Components

### 1. Frontend Layer (Presentation Layer)

```
┌─────────────────────────────────────────────────────────────┐
│                    BORROWING PAGE UI                         │
│                /MAIN_USER/assets.php                         │
├─────────────────────────────────────────────────────────────┤
│  ┌─────────────────┐  ┌─────────────────┐  ┌──────────────┐ │
│  │   Header       │  │   Filters       │  │   Actions    │ │
│  │                 │  │                 │  │              │ │
│  │ • Page Title    │  │ • Office Filter │  │ • Borrow     │ │
│  │ • Navigation    │  │ • Status Filter │  │ • Return     │ │
│  │ • User Info     │  │ • Refresh Btn   │  │ • View       │ │
│  └─────────────────┘  └─────────────────┘  └──────────────┘ │
│                                                             │
│  ┌─────────────────────────────────────────────────────────┐ │
│  │              BORROWED ITEMS TABLE                       │ │
│  │  ┌─────────┬─────────────┬─────────┬─────────────────┐  │ │
│  │  │ Prop No │ Description │ Office  │     Actions     │  │ │
│  │  ├─────────┼─────────────┼─────────┼─────────────────┤  │ │
│  │  │  P-001  │ Laptop      │ IT Dept │ [View][Return] │  │ │
│  │  │  P-002  │ Monitor     │ IT Dept │ [View][Return] │  │ │
│  │  └─────────┴─────────────┴─────────┴─────────────────┘  │ │
│  └─────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────┘
```

### 2. Business Logic Layer (Application Layer)

```
┌─────────────────────────────────────────────────────────────┐
│                 BUSINESS LOGIC LAYER                        │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  ┌─────────────────┐  ┌─────────────────┐  ┌──────────────┐ │
│  │  Asset Manager  │  │ Filter Manager  │  │ Auth Manager │ │
│  │                 │  │                 │  │              │ │
│  │ • Get Assets    │  │ • Apply Filters │  │ • Session    │ │
│  │ • Validate Data │  │ • Parse Params  │  │ • Permissions│ │
│  │ • Format Output │  │ • Build Query   │  │ • Role Check │ │
│  └─────────────────┘  └─────────────────┘  └──────────────┘ │
│                                                             │
│  ┌─────────────────┐  ┌─────────────────┐  ┌──────────────┐ │
│  │ Borrow Manager  │  │ Return Manager  │  │ UI Renderer  │ │
│  │                 │  │                 │  │              │ │
│  │ • Validate Item │  │ • Validate Item │  │ • Build HTML  │ │
│  │ • Check Status  │  │ • Check Status  │  │ • Apply CSS  │ │
│  │ • Process Logic │  │ • Process Logic │  │ • JS Scripts │ │
│  └─────────────────┘  └─────────────────┘  └──────────────┘ │
└─────────────────────────────────────────────────────────────┘
```

### 3. Data Access Layer (Persistence Layer)

```
┌─────────────────────────────────────────────────────────────┐
│                  DATA ACCESS LAYER                          │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  ┌─────────────────┐  ┌─────────────────┐  ┌──────────────┐ │
│  │  Asset DAO      │  │  Office DAO     │  │  User DAO    │ │
│  │                 │  │                 │  │              │ │
│  │ • CRUD Assets   │  │ • CRUD Offices  │  │ • User Data  │ │
│  │ • Filter Query  │  │ • Office List   │  │ • Permissions│ │
│  │ • Status Update │  │ • Location Data │  │ • Session    │ │
│  └─────────────────┘  └─────────────────┘  └──────────────┘ │
│                                                             │
│  ┌─────────────────┐  ┌─────────────────┐  ┌──────────────┐ │
│  │ History DAO      │  │  Logger DAO     │  │  Cache DAO   │ │
│  │                 │  │                 │  │              │ │
│  │ • Audit Trail   │  │ • System Logs   │  │ • Query Cache│ │
│  │ • Track Changes │  │ • Error Logs    │  │ • Performance│ │
│  │ • Timestamps    │  │ • Activity Logs │  │ • Optimization│ │
│  └─────────────────┘  └─────────────────┘  └──────────────┘ │
└─────────────────────────────────────────────────────────────┘
```

### 4. Database Layer

```
┌─────────────────────────────────────────────────────────────┐
│                     DATABASE LAYER                          │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  ┌─────────────────┐  ┌─────────────────┐  ┌──────────────┐ │
│  │  asset_items    │  │   assets        │  │   offices    │ │
│  │                 │  │                 │  │              │ │
│  │ • id (PK)       │  │ • id (PK)       │  │ • id (PK)    │ │
│  │ • property_no   │  │ • description   │  │ • office_name│ │
│  │ • description   │  │ • category_id   │  │ • location   │ │
│  │ • status        │  │ • unit_cost     │  │ • created_at │ │
│  │ • value         │  │ • quantity      │  │ • updated_at │ │
│  │ • office_id     │  │ • created_at    │  │              │ │
│  │ • asset_id      │  │ • updated_at    │  │              │ │
│  │ • employee_id   │  │                 │  │              │ │
│  │ • last_updated  │  │                 │  │              │ │
│  └─────────────────┘  └─────────────────┘  └──────────────┘ │
│                                                             │
│  ┌─────────────────┐  ┌─────────────────┐  ┌──────────────┐ │
│  │asset_item_history│ │   users         │  │system_logs   │ │
│  │                 │  │                 │  │              │ │
│  │ • id (PK)       │  │ • id (PK)       │  │ • id (PK)    │ │
│  │ • item_id       │  │ • username      │  │ • user_id    │ │
│  │ • action        │  │ • email         │  │ • action     │ │
│  │ • details       │  │ • role          │  │ • details    │ │
│  │ • user_id       │  │ • office_id     │  │ • timestamp  │ │
│  │ • created_at    │  │ • created_at    │  │ • ip_address │ │
│  └─────────────────┘  └─────────────────┘  └──────────────┘ │
└─────────────────────────────────────────────────────────────┘
```

## Data Flow Architecture

### 1. Page Load Flow

```
┌─────────────┐    ┌──────────────┐    ┌─────────────┐    ┌─────────────┐
│   User      │───▶│   Browser    │───▶│   Web Server │───▶│   PHP App   │
│  Request    │    │   Request    │    │   (Apache)   │    │  (assets.php)│
└─────────────┘    └──────────────┘    └─────────────┘    └─────────────┘
                                                                │
                                                                ▼
┌─────────────┐    ┌──────────────┐    ┌─────────────┐    ┌─────────────┐
│   HTML      │◀───│   Response   │◀───│   PHP Output │◀───│   Database  │
│  Response   │    │   (HTML)     │    │   (Render)   │    │   Query     │
└─────────────┘    └──────────────┘    └─────────────┘    └─────────────┘
```

### 2. Borrow/Return Action Flow

```
┌─────────────┐    ┌──────────────┐    ┌─────────────┐    ┌─────────────┐
│   User      │───▶│  JavaScript  │───▶│   AJAX       │───▶│  Backend    │
│  Click      │    │   Function   │    │  Request     │    │  Processor  │
└─────────────┘    └──────────────┘    └─────────────┘    └─────────────┘
                                                                │
                                                                ▼
┌─────────────┐    ┌──────────────┐    ┌─────────────┐    ┌─────────────┐
│   Page      │◀───│   JSON       │◀───│   Database   │◀───│   Status    │
│  Reload     │    │  Response    │    │   Update     │    │   Change    │
└─────────────┘    └──────────────┘    └─────────────┘    └─────────────┘
```

## Security Architecture

### 1. Authentication Layer
```
┌─────────────────────────────────────────────────────────────┐
│                    AUTHENTICATION                           │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  ┌─────────────────┐  ┌─────────────────┐  ┌──────────────┐ │
│  │  Session Check  │  │  Role Validation│  │ Permission   │ │
│  │                 │  │                 │  │  Check       │ │
│  │ • Session ID    │  │ • main_user     │  │ • Office     │ │
│  │ • Timeout       │  │ • admin         │  │ • Asset      │ │
│  │ • Validity      │  │ • system_admin  │  │ • Action     │ │
│  └─────────────────┘  └─────────────────┘  └──────────────┘ │
└─────────────────────────────────────────────────────────────┘
```

### 2. Data Validation Layer
```
┌─────────────────────────────────────────────────────────────┐
│                    DATA VALIDATION                          │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  ┌─────────────────┐  ┌─────────────────┐  ┌──────────────┐ │
│  │  Input Filter   │  │  Output Escape  │  │  SQL Injection│ │
│  │                 │  │                 │  │  Prevention   │ │
│  │ • Sanitize GET  │  │ • htmlspecialchars│ │ • Prepared   │ │
│  │ • Validate POST │  │ • Filter Output │  │ • Statements  │ │
│  │ • Type Check    │  │ • Safe Display  │  │ • Bind Params │ │
│  └─────────────────┘  └─────────────────┘  └──────────────┘ │
└─────────────────────────────────────────────────────────────┘
```

## Performance Architecture

### 1. Caching Strategy
```
┌─────────────────────────────────────────────────────────────┐
│                      CACHING LAYER                          │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  ┌─────────────────┐  ┌─────────────────┐  ┌──────────────┐ │
│  │  Query Cache    │  │  Output Cache   │  │  Browser     │ │
│  │                 │  │                 │  │  Cache       │ │
│  │ • Office List   │  │ • Rendered HTML │  │ • CSS/JS     │ │
│  │ • Categories    │  │ • Filter Results│  │ • Images     │ │
│  │ • User Data     │  │ • Status Counts │  │ • Session    │ │
│  └─────────────────┘  └─────────────────┘  └──────────────┘ │
└─────────────────────────────────────────────────────────────┘
```

### 2. Database Optimization
```
┌─────────────────────────────────────────────────────────────┐
│                 DATABASE OPTIMIZATION                       │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  ┌─────────────────┐  ┌─────────────────┐  ┌──────────────┐ │
│  │   Indexing      │  │  Query Optimize │  │ Connection   │ │
│  │                 │  │                 │  │  Pooling     │ │
│  │ • status index  │  │ • JOIN Order    │  │ • Persistent │ │
│  │ • office_id idx │  │ • WHERE Clause  │  │ • Reuse      │ │
│  │ • asset_id idx  │  │ • LIMIT Clause  │  │ • Timeout    │ │
│  └─────────────────┘  └─────────────────┘  └──────────────┘ │
└─────────────────────────────────────────────────────────────┘
```

## API Architecture

### 1. RESTful Endpoints
```
┌─────────────────────────────────────────────────────────────┐
│                      API ENDPOINTS                          │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  GET    /assets.php                    - List assets        │
│  GET    /assets.php?status=borrowed   - Filter borrowed    │
│  GET    /assets.php?office_id=1       - Filter by office   │
│  POST   /process_borrow.php            - Borrow item        │
│  POST   /process_borrow.php            - Return item        │
│  GET    /view_asset_item.php?id=123   - View item details  │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

### 2. Request/Response Format
```
┌─────────────────────────────────────────────────────────────┐
│                    REQUEST/RESPONSE                         │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  REQUEST:                                                   │
│  ───────────────────────────────────────────────────────   │
│  POST /process_borrow.php                                   │
│  Content-Type: application/x-www-form-urlencoded            │
│  {                                                         │
│    "action": "borrow",                                     │
│    "item_id": "123",                                       │
│    "user_id": "456"                                        │
│  }                                                         │
│                                                             │
│  RESPONSE:                                                  │
│  ───────────────────────────────────────────────────────   │
│  Content-Type: application/json                             │
│  {                                                         │
│    "success": true,                                        │
│    "message": "Item borrowed successfully!"                 │
│  }                                                         │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

## Technology Stack

### Frontend Technologies
- **HTML5** - Semantic markup
- **CSS3** - Bootstrap 5 framework
- **JavaScript** - ES6+ modern JavaScript
- **Bootstrap Icons** - Icon library
- **AJAX** - Asynchronous requests

### Backend Technologies
- **PHP 8.x** - Server-side scripting
- **MySQL** - Database management
- **Apache/Nginx** - Web server
- **Session Management** - User authentication

### Development Tools
- **Git** - Version control
- **VS Code** - IDE
- **Chrome DevTools** - Debugging
- **phpMyAdmin** - Database management

## Deployment Architecture

### 1. Server Environment
```
┌─────────────────────────────────────────────────────────────┐
│                    PRODUCTION SERVER                        │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  ┌─────────────────┐  ┌─────────────────┐  ┌──────────────┐ │
│  │   Web Server    │  │   PHP Engine    │  │   Database   │ │
│  │                 │  │                 │  │              │ │
│  │ • Apache 2.4    │  │ • PHP 8.x       │  │ • MySQL 8.0  │ │
│  │ • HTTPS/SSL     │  │ • OPCache       │  │ • InnoDB     │ │
│  │ • Mod Rewrite   │  │ • Sessions      │  │ • Backups    │ │
│  └─────────────────┘  └─────────────────┘  └──────────────┘ │
└─────────────────────────────────────────────────────────────┘
```

### 2. File Structure
```
/PIMS/
├── MAIN_USER/
│   ├── assets.php                 # Main borrowing page
│   ├── process_borrow.php         # Borrow/Return processor
│   ├── view_asset_item.php        # Item details view
│   ├── includes/
│   │   ├── sidebar.php
│   │   ├── topbar.php
│   │   └── logout-modal.php
│   └── assets/
│       └── css/
├── ADMIN/
├── USER/
├── uploads/
│   ├── qr_codes/
│   └── asset_images/
├── includes/
│   ├── config.php
│   ├── system_functions.php
│   └── logger.php
└── config.php
```

## Monitoring & Logging

### 1. Application Monitoring
```
┌─────────────────────────────────────────────────────────────┐
│                    MONITORING LAYER                         │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  ┌─────────────────┐  ┌─────────────────┐  ┌──────────────┐ │
│  │  Error Logging  │  │  Performance    │  │  User Activity│ │
│  │                 │  │                 │  │              │ │
│  │ • PHP Errors    │  │ • Response Time │  │ • Login/Logout│ │
│  │ • Database Err  │  │ • Query Time    │  │ • Borrow/Return│ │
│  │ • System Logs    │  │ • Memory Usage  │  │ • Page Views  │ │
│  └─────────────────┘  └─────────────────┘  └──────────────┘ │
└─────────────────────────────────────────────────────────────┘
```

### 2. Audit Trail
```
┌─────────────────────────────────────────────────────────────┐
│                      AUDIT TRAIL                             │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  ┌─────────────────┐  ┌─────────────────┐  ┌──────────────┐ │
│  │  Asset History  │  │  User Actions   │  │  System Logs │ │
│  │                 │  │                 │  │              │ │
│  │ • Borrow/Return │  │ • Page Access   │  │ • Errors     │ │
│  │ • Status Change │  │ • Filter Use    │  │ • Security   │ │
│  │ • Timestamps    │  │ • Button Clicks │  │ • Performance│ │
│  └─────────────────┘  └─────────────────┘  └──────────────┘ │
└─────────────────────────────────────────────────────────────┘
```

## Scalability Considerations

### 1. Horizontal Scaling
- Load balancer for multiple web servers
- Database read replicas for query distribution
- CDN for static assets
- Session storage in Redis

### 2. Vertical Scaling
- Increased server resources (CPU, RAM)
- Database optimization and indexing
- Caching layers implementation
- Code optimization

## Security Best Practices

### 1. Input Validation
- Sanitize all user inputs
- Validate data types and ranges
- Use prepared statements
- Implement CSRF protection

### 2. Access Control
- Role-based permissions
- Office-based access restrictions
- Session timeout management
- Secure password handling

### 3. Data Protection
- HTTPS encryption
- Sensitive data masking
- Regular security audits
- Backup encryption

This architecture provides a robust, scalable, and secure foundation for the borrowing system in PIMS, ensuring optimal performance and user experience while maintaining data integrity and security.
