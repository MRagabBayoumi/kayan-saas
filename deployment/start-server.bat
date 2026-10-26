@echo off
setlocal
title Kayan ERP Local Server Runner

cd /d "%~dp0\.."

where php >nul 2>nul
if %errorlevel% neq 0 (
    if exist "C:\xampp\php\php.exe" (
        set "PATH=C:\xampp\php;!PATH!"
    )
)

echo =======================================================================
echo          KAYAN ERP - LOCAL SERVER RUNNER
echo =======================================================================
echo.
echo Active Local IP Addresses:
for /f "tokens=2 delims=:" %%a in ('ipconfig ^| findstr /i "IPv4"') do (
    echo   LAN Access: http:%%a:8000
)
echo   Localhost:  http://localhost:8000
echo.
echo Press Ctrl+C to stop the server anytime.
echo =======================================================================
echo.

start http://localhost:8000
php artisan serve --host=0.0.0.0 --port=8000
pause
