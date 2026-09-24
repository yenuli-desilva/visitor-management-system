# Enterprise Visitor Management System (VMS)

A professional, secure, and modern **Visitor Management System (VMS)** built from scratch using clean Vanilla HTML5, CSS3, JavaScript, PHP 8+, and MySQL.

Designed specifically for organizational premises and enterprise reception desks to track visitors, manage check-in/check-out flows, enforce role-based access control, and generate daily/monthly audit reports.

---

## 1. Project Overview

The Visitor Management System replaces manual paper guestbooks with a secure, real-time digital log. It features:
- **Corporate UI Design**: Deep navy primary styling (`#0f172a`), teal/blue accents, rounded cards, soft shadows, responsive slide-out drawer navigation, and pure Vanilla SVG interactive charts.
- **Role-Based Access Control (RBAC)**: Distinct permissions for System Administrators vs. Ordinary Staff Users.
- **REST-Style Backend**: Clean JSON API endpoints with consistent response structures.
- **Real-Time Check-In/Check-Out**: Instant status toggles with live timestamp tracking.
- **Audit & Compliance Reporting**: Daily and monthly visit reports with integrated print/PDF export layouts.

---

## 2. Folder Structure

```
c:\xamp\htdocs\VMS\
├── config/
│   └── database.php             # PDO database connection & error handling
├── includes/
│   ├── auth.php                 # Session management, authentication, & RBAC helpers
│   ├── helpers.php              # JSON response helpers, input sanitization, date formatters
│   ├── header.php               # Common layout header, responsive sidebar, topbar
│   └── footer.php               # Global toast container, modals, script loaders
├── database/
│   └── vms_database.sql         # SQL schema, foreign keys, indexes, and demo seed data
├── api/
│   ├── auth.php                 # Login, Register, Logout, Me endpoints
│   ├── dashboard.php            # Analytics KPI metrics, trend data, recent visits
│   ├── visitors.php             # Visitor CRUD, search, department/status filter, pagination
│   ├── visits.php               # Check-in and check-out actions
│   ├── users.php                # Admin-only user management (add, edit, block, delete)
│   ├── departments.php          # Active departments listing
│   └── reports.php              # Daily and monthly report datasets
├── assets/
│   ├── css/
│   │   ├── main.css             # Design tokens, typography, buttons, tables, badges, toast, modals
│   │   ├── auth.css             # Split-screen login & register styling
│   │   ├── dashboard.css        # Layout grid, sidebar drawer, stat cards, SVG charts
│   │   └── reports.css          # Reporting cards, department bars, print styles (@media print)
│   └── js/
│       ├── api.js               # Centralized fetch wrapper with JSON error handling
│       ├── ui.js                # Toast notifications, modals, password toggles, sidebar
│       ├── auth.js              # Authentication form handlers & password strength meter
│       ├── dashboard.js         # Stat counters, Vanilla SVG trend chart, recent activity
│       ├── visitors.js          # Live search, filters, visitor CRUD modals, check-in/out
│       ├── users.js             # User accounts table, add/edit modals, block/unblock
│       └── reports.js           # Daily/monthly reporting controller and print trigger
├── index.php                    # Entry router (redirects to login.php or dashboard.php)
├── login.php                    # Split-screen corporate login page
├── register.php                 # Staff self-registration page with password strength meter
├── dashboard.php                # Main dashboard with live KPIs and quick visitor registration
├── visitors.php                 # Full visitors directory and check-in/out management
├── users.php                    # Admin User Management (RBAC protected)
├── reports.php                  # Daily & monthly reports with print/PDF export
├── help.php                     # Operations guide and role permission matrix
├── logout.php                   # Secure session destruction & redirect
└── README.md                    # Project documentation
```

---

## 3. Technologies

### Frontend
- **HTML5**: Semantic tags, accessible forms, data attributes.
- **CSS3**: CSS Custom Properties (Tokens), Flexbox, CSS Grid, animations, media queries for mobile responsiveness, `@media print` print styles.
- **Vanilla JavaScript**: ES6+, Fetch API, async/await, pure DOM manipulation.
- **Zero Heavy Frameworks**: No React, Vue, Angular, or Tailwind. Lightning-fast load times.

### Backend
- **PHP 8+**: Strict typing, PDO database connection, prepared SQL statements.
- **PHP Sessions**: Secure cookie parameters (`HttpOnly`, `SameSite=Lax`, 24h lifetime).
- **Password Security**: Cryptographic hashing via `password_hash()` and `password_verify()` with Bcrypt.
- **REST-Style JSON Endpoints**: Clean separation between frontend presentation and backend API.

### Database
- **MySQL / MariaDB**: Relational schema with primary keys, foreign keys (`ON DELETE CASCADE` / `ON DELETE RESTRICT`), and indexes on search columns.

---

## 4. Database Setup & Schema

The database name is `vms_db`. The complete SQL schema is located in `database/vms_database.sql`.

### Tables Overview
1. `Users`:
   - `UserID` (INT PK AUTO_INCREMENT)
   - `Username` (VARCHAR(50) UNIQUE)
   - `Password` (VARCHAR(255) - Bcrypt hash)
   - `FullName` (VARCHAR(100))
   - `Role` (ENUM('admin', 'user'))
   - `Status` (ENUM('active', 'blocked'))
   - `CreatedAt` (TIMESTAMP)

2. `Departments`:
   - `DepartmentID` (INT PK AUTO_INCREMENT)
   - `Name` (VARCHAR(100) UNIQUE)
   - `Status` (ENUM('active', 'inactive'))
   - `CreatedAt` (TIMESTAMP)

3. `Visitors`:
   - `VisitorID` (INT PK AUTO_INCREMENT)
   - `Name` (VARCHAR(100))
   - `NIC` (VARCHAR(30), indexed)
   - `Phone` (VARCHAR(25), indexed)
   - `Email` (VARCHAR(100) NULL)
   - `Purpose` (VARCHAR(255))
   - `Host` (VARCHAR(100))
   - `DepartmentID` (INT FK -> `Departments.DepartmentID`)
   - `CreatedAt` (TIMESTAMP)

4. `Visits`:
   - `VisitID` (INT PK AUTO_INCREMENT)
   - `VisitorID` (INT FK -> `Visitors.VisitorID` ON DELETE CASCADE)
   - `CheckIn` (DATETIME)
   - `CheckOut` (DATETIME NULL)
   - `VisitDate` (DATE, indexed)
   - `Status` (ENUM('checked_in', 'checked_out', 'scheduled'), indexed)
   - `CreatedAt` (TIMESTAMP)

---

## 5. XAMPP Setup

1. Open the **XAMPP Control Panel**.
2. Start the **Apache** service.
3. Start the **MySQL** service.
4. Ensure the project folder is placed inside `htdocs`:
   ```
   C:\xampp\htdocs\VMS   (or C:\xamp\htdocs\VMS)
   ```

---

## 6. How to Import the Database

### Option A: Using phpMyAdmin
1. Open your web browser and navigate to `http://localhost/phpmyadmin/`.
2. Click **Import** in the top navigation bar.
3. Click **Choose File** and select `database/vms_database.sql` from your `VMS` folder.
4. Click **Go** at the bottom of the page.

### Option B: Using MySQL Command Line
Run the following command in PowerShell / Command Prompt:
```powershell
mysql -u root -p < database/vms_database.sql
```
*(If prompted for a password on default XAMPP, simply press Enter).*

---

## 7. How to Configure database.php

The database configuration file is located at `config/database.php`.

```php
define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306');
define('DB_NAME', 'vms_db');
define('DB_USER', 'root');
define('DB_PASS', ''); // Set your MySQL password if you configured one
define('DB_CHARSET', 'utf8mb4');
```

---

## 8. How to Start the Application

1. Ensure Apache and MySQL are running in XAMPP.
2. Open your web browser and navigate to:
   ```
   http://localhost/VMS/
   ```
3. You will be greeted by the modern split-screen sign-in portal.

---

## 9. Demo Login Accounts

Pre-seeded demo accounts are ready for testing:

| Role | Username | Password | Status | Notes |
| :--- | :--- | :--- | :--- | :--- |
| **Administrator** | `admin` | `Admin@123` | `active` | Full access to all modules, user management, and deletions |
| **Staff User** | `user` | `User@123` | `active` | Front desk staff account with check-in, search, and reports |
| **Receptionist** | `sarah_reception` | `Reception@123` | `active` | Additional staff account |
| **Blocked Account** | `mark_security` | `Security@123` | `blocked` | Demonstrates blocked account prevention |

> [!TIP]
> On the login page, you can click the **Quick Demo Logins** chips to instantly populate the credentials.

---

## 10. Role & Permissions Matrix

| Feature | Administrator (`admin`) | Ordinary User (`user`) |
| :--- | :---: | :---: |
| **Dashboard Metrics & Trends** | ✅ Full Access | ✅ Full Access |
| **Register New Visitor** | ✅ Yes | ✅ Yes |
| **Visitor Live Search & Filter** | ✅ Yes | ✅ Yes |
| **Check In Visitor** | ✅ Yes | ✅ Yes |
| **Check Out Visitor** | ✅ Yes | ✅ Yes |
| **Edit Visitor Information** | ✅ Yes | ✅ Yes |
| **Delete Visitor Records** | ✅ Yes | ❌ Disabled & Hidden |
| **User Accounts Management** | ✅ Full Access | ❌ Forbidden (403) |
| **Add / Edit / Block / Delete Users** | ✅ Yes | ❌ Forbidden (403) |
| **Daily & Monthly Audit Reports** | ✅ Yes | ✅ Yes |
| **Print / Export PDF Reports** | ✅ Yes | ✅ Yes |

---

## 11. REST API Endpoint Structure

All API endpoints return standardized JSON structures:

**Success Response:**
```json
{
  "success": true,
  "message": "Action completed successfully",
  "data": { ... }
}
```

**Error Response:**
```json
{
  "success": false,
  "message": "Error description message"
}
```

### Endpoints
- `POST /api/auth.php?action=login`: Authenticate user session
- `POST /api/auth.php?action=register`: Self-registration for new staff users
- `POST /api/auth.php?action=logout`: Destroy session
- `GET /api/auth.php?action=me`: Get current logged-in user profile
- `GET /api/dashboard.php`: Fetch KPI counts, 7-day trend array, and recent visits
- `GET /api/visitors.php`: Search, filter by department/status, and paginate visitors
- `GET /api/visitors.php?id={id}`: Get single visitor with visit history
- `POST /api/visitors.php`: Register new visitor (with optional instant check-in)
- `PUT /api/visitors.php`: Update visitor profile
- `DELETE /api/visitors.php`: Permanently delete visitor (Admin only)
- `POST /api/visits.php?action=checkin`: Check in visitor
- `POST /api/visits.php?action=checkout`: Check out visitor
- `GET /api/departments.php`: Fetch active departments list
- `GET /api/users.php`: List system users (Admin only)
- `POST /api/users.php`: Add new user (Admin only)
- `PUT /api/users.php`: Edit user or toggle block/unblock (Admin only)
- `DELETE /api/users.php`: Delete user (Admin only, self-deletion prevented)
- `GET /api/reports.php?type=daily&date=YYYY-MM-DD`: Daily report metrics & table
- `GET /api/reports.php?type=monthly&month=YYYY-MM`: Monthly report metrics, daily breakdown, and department volume

---

## 12. Security Features

1. **Prepared SQL Statements**: 100% of database queries use PDO prepared statements with parameter binding, eliminating SQL Injection vulnerabilities.
2. **Cryptographic Password Hashing**: Passwords are saved as Bcrypt hashes generated with `password_hash()` and verified with `password_verify()`.
3. **Session Security**: Session cookies enforce `httponly: true` and `samesite: Lax`. Session IDs are regenerated upon login via `session_regenerate_id(true)` to prevent session fixation.
4. **Output Escaping**: All user-supplied data rendered in HTML is sanitized using `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')`.
5. **Authorization Enforcement**: Every administrative endpoint and page strictly verifies `isAdmin()`. Unauthorized API requests return `403 Forbidden` JSON, and unauthorized page requests redirect safely.
6. **Self-Deletion Guard**: Administrators cannot delete or block their own currently logged-in account.
7. **Credential Protection**: Database passwords and host details are encapsulated in `config/database.php` and never exposed to the frontend or browser.

---

## 13. Mobile Responsiveness

The application includes a responsive design accommodating:
- **Mobile Phones (< 768px)**: Stacked single-column layouts, slide-out hamburger navigation drawer with backdrop blur, full-width touch buttons, and responsive modal dialogs.
- **Tablets (768px - 992px)**: Responsive data grids and sidebars.
- **Laptops & Desktops (> 992px)**: Permanent deep-navy sidebar, multi-column KPI grids, and side-by-side analytics charts.
