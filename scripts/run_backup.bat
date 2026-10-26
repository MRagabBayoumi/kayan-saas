@echo off
REM ============================================================
REM Kayan ERP - Automated Database Backup Script
REM ============================================================
"C:\Users\PC\.config\herd-lite\bin\php.exe" "C:\Users\PC\Projects\kayan-erp\artisan" backup:run --only-db --disable-notifications
