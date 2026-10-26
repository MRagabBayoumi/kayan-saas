@echo off
setlocal
title Kayan ERP - Automated Database Backup

:: -------------------------------------------------------------------------
:: Configuration
:: -------------------------------------------------------------------------
set "BACKUP_DIR=C:\KayanERP_Backups"
set "DB_NAME=kayan_erp"
set "DB_USER=root"
set "DB_PASS="

echo =======================================================================
echo              KAYAN ERP - AUTOMATED DATABASE BACKUP
echo =======================================================================
echo.

:: -------------------------------------------------------------------------
:: Locate mysqldump.exe
:: -------------------------------------------------------------------------
set "MYSQLDUMP_CMD=mysqldump"
where mysqldump >nul 2>nul
if %errorlevel% neq 0 (
    if exist "C:\xampp\mysql\bin\mysqldump.exe" (
        set "MYSQLDUMP_CMD=C:\xampp\mysql\bin\mysqldump.exe"
    ) else (
        echo [ERROR] mysqldump.exe was not found in PATH or C:\xampp\mysql\bin.
        echo Please ensure MySQL is installed.
        pause
        exit /b 1
    )
)

if not exist "%BACKUP_DIR%" mkdir "%BACKUP_DIR%"

:: -------------------------------------------------------------------------
:: Generate Timestamp (YYYY-MM-DD_HH-MM)
:: -------------------------------------------------------------------------
for /f "tokens=2 delims==" %%I in ('wmic os get localdatetime /value') do set "dt=%%I"
set "TIMESTAMP=%dt:~0,4%-%dt:~4,2%-%dt:~6,2%_%dt:~8,2%-%dt:~10,2%"
set "BACKUP_FILE=%BACKUP_DIR%\%DB_NAME%_backup_%TIMESTAMP%.sql"

echo Target Database: %DB_NAME%
echo Backup File:     %BACKUP_FILE%
echo.
echo Exporting database structure and data...

if "%DB_PASS%"=="" (
    "%MYSQLDUMP_CMD%" -u %DB_USER% --routines --triggers --single-transaction %DB_NAME% > "%BACKUP_FILE%"
) else (
    "%MYSQLDUMP_CMD%" -u %DB_USER% -p%DB_PASS% --routines --triggers --single-transaction %DB_NAME% > "%BACKUP_FILE%"
)

if %errorlevel% equ 0 (
    echo.
    echo =======================================================================
    echo   BACKUP CREATED SUCCESSFULLY!
    echo   File saved to: %BACKUP_FILE%
    echo =======================================================================
) else (
    echo.
    echo [ERROR] Backup failed. Make sure MySQL service is running.
)
echo.
pause
