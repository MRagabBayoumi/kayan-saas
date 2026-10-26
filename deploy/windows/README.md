# Kayan ERP — Windows One-Click Installer Guide
**Target Output:** `KayanERP-Setup.exe` (Standalone Client Installer)  
**Standard:** 100% English ASCII paths and filenames to guarantee zero encoding conflicts on all Windows editions.

---

## Architecture Overview

When the client installs `KayanERP-Setup.exe`, it installs into:
```
C:\KayanERP\
├── server\
│   ├── php\          (Portable PHP 8.3/8.5 x64)
│   └── mariadb\      (Portable MariaDB x64 with pre-created kayan_erp database)
├── scripts\
│   ├── start.vbs     (Starts services silently in background and opens Edge App mode)
│   ├── stop.vbs      (Stops background services cleanly)
│   └── backup.bat    (Daily automatic database dump to C:\KayanERP_Backups)
└── kayan-erp\        (Production Laravel application with pre-built Vite assets)
```

---

## 3-Step Build Process

### Step 1: Prepare the Staging Directory (`C:\KayanRelease`)
Create a staging folder `C:\KayanRelease` with the following structure:
```
C:\KayanRelease\
├── server\
│   ├── php\          <-- Unzip official PHP 8.3/8.5 Windows x64 Thread Safe here
│   └── mariadb\      <-- Unzip official MariaDB Windows x64 portable here
└── kayan-erp\        <-- Copy current project folder here
```

> **Important in `kayan-erp`:**
> 1. Run `npm run build` on your development machine first so `public/build` is populated.
> 2. Ensure `vendor` is installed (`composer install --no-dev --optimize-autoloader`).
> 3. Ensure `.env` has:
>    ```ini
>    APP_ENV=production
>    APP_DEBUG=false
>    APP_URL=http://127.0.0.1:8000
>    DB_CONNECTION=mysql
>    DB_HOST=127.0.0.1
>    DB_PORT=3306
>    DB_DATABASE=kayan_erp
>    DB_USERNAME=root
>    DB_PASSWORD=
>    ```

---

### Step 2: Configure Portable MariaDB & PHP

1. **MariaDB:**
   * Copy `deploy/windows/mariadb/my.ini` to `C:\KayanRelease\server\mariadb\my.ini`.
   * Initialize data directory once:
     ```cmd
     cd C:\KayanRelease\server\mariadb\bin
     mysql_install_db.exe --datadir=C:\KayanRelease\server\mariadb\data
     ```
   * Start MariaDB once and create database `kayan_erp`:
     ```cmd
     mysqld.exe --defaults-file=C:\KayanRelease\server\mariadb\my.ini
     ```
     In another terminal:
     ```cmd
     mysql.exe -u root -e "CREATE DATABASE IF NOT EXISTS kayan_erp CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
     ```

2. **PHP:**
   * In `C:\KayanRelease\server\php\php.ini`:
     Enable extensions by uncommenting:
     ```ini
     extension_dir = "ext"
     extension=curl
     extension=fileinfo
     extension=gd
     extension=intl
     extension=mbstring
     extension=openssl
     extension=pdo_mysql
     extension=zip
     extension=bcmath
     ```

---

### Step 3: Compile `KayanERP-Setup.exe` with Inno Setup

1. Download and install [Inno Setup](https://jrsoftware.org/isdl.php) (Free, open-source).
2. Open `deploy/windows/installer.iss` in Inno Setup Compiler.
3. Click **Build > Compile** (or press `Ctrl + F9`).
4. Output file will be created at:
   `deploy/windows/Output/KayanERP-Setup.exe`

---

## Client Experience

1. Client double clicks `KayanERP-Setup.exe`.
2. Follows standard setup wizard: Next -> Next -> Install -> Finish.
3. Desktop icon **Kayan ERP** is created on Desktop.
4. Clicking **Kayan ERP** launches the database and web server silently in the background and opens the POS / ERP in full-screen desktop window mode via Microsoft Edge WebView.
