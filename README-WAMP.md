# MUKIL TECH Billing, POS, Inventory & PC Repair System
## WAMP Server Installation

This version is prepared for **WAMP Server + Apache + PHP + MySQL/MariaDB**.
Node.js, Vite, React and the AI Studio files are not required.

### 1. Copy the website
Extract this folder into:

    C:\wamp64\www\mukiltech

Your main file should be:

    C:\wamp64\www\mukiltech\index.php

### 2. Start WAMP
Open WampServer and make sure the WAMP icon is **green**.
Start/restart:
- Apache
- MySQL

### 3. Create the database
Open:

    http://localhost/phpmyadmin/

Create/import the database by selecting the **Import** tab and importing:

    database.sql

The SQL file creates the database named:

    mukiltech

### 4. Database settings
The default WAMP configuration is:

    Host: localhost
    Port: 3306
    Database: mukiltech
    Username: root
    Password: (blank)

These defaults are in:

    config/database.php

If your WAMP MySQL root account has a password, edit `DB_PASS` in that file.

### 5. Open the system

    http://localhost/mukiltech/

The application also works when the folder is renamed, because internal URLs are calculated automatically.

### 6. Default users
The database contains these accounts:

    admin / admin123
    cashier / cashier123
    technician / tech123

### Important WAMP notes
- Use a normal browser URL beginning with `http://localhost/...`.
- Do not run `npm install`, `npm run dev`, Vite, or Node.js for this version.
- The PHP session cookie is configured to work on normal WAMP HTTP localhost and automatically becomes secure when HTTPS is enabled.
- `baseUrl()` has been corrected so links work from all application folders, including Settings, Users, Payments and Reports.

### If MySQL connection fails
Check that:
1. WAMP is green.
2. MySQL is running.
3. `mukiltech` exists in phpMyAdmin.
4. `config/database.php` has the correct MySQL password.
5. PHP has the PDO MySQL extension enabled.

### Recommended PHP version
PHP 8.1+ is recommended.

### Backup
Use phpMyAdmin Export to create a database backup before major changes.

### Password recovery and security
- Set the Apache environment variable `APP_URL` to the full application URL, for example `http://localhost/Mukil-Tech-WAMP-Fixed` (use HTTPS on public deployments). Restart Apache after changing its environment.
- Configure PHP mail delivery and a valid business email in Settings. Recovery links are delivered only by email; they are never displayed in the browser. Existing reset links must be requested again after this update.
- Database backups and the WAMP diagnostic page require an authenticated administrator. Apache must allow this project's `.htaccess` rules to protect internal files and Git metadata.
- Replace the documented demonstration passwords before exposing the application. Use a dedicated database account with only the permissions needed by the application instead of the default WAMP root account.
- The login cooldown is session-based; use server-level rate limiting for an internet-facing deployment.

### Regression checks
Run `php tests/security.php` from the project folder. This checks authorization defaults, session-bound CSRF validation, and the login cooldown without modifying the database.
