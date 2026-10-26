@echo off
rem Kayan ERP - Automated Database Backup Script (Strictly ASCII)
set BACKUP_DIR=C:\KayanERP_Backups
if not exist "%BACKUP_DIR%" mkdir "%BACKUP_DIR%"

for /f "tokens=2-4 delims=/ " %%a in ('date /t') do (set mydate=%%c-%%a-%%b)
for /f "tokens=1-2 delims=/:" %%a in ('time /t') do (set mytime=%%a%%b)

set DUMP_BIN=C:\KayanERP\server\mariadb\bin\mysqldump.exe
if exist "%DUMP_BIN%" (
    "%DUMP_BIN%" -u root kayan_erp > "%BACKUP_DIR%\kayan_erp_%mydate%_%mytime%.sql"
)
