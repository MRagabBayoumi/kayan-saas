@echo off
setlocal
title Kayan ERP - Automated Firewall Configuration

echo =======================================================================
echo          KAYAN ERP - WINDOWS FIREWALL AUTOMATED CONFIGURATION
echo =======================================================================
echo.

:: -------------------------------------------------------------------------
:: Check for Administrator privileges and auto-elevate if needed
:: -------------------------------------------------------------------------
net session >nul 2>&1
if %errorlevel% neq 0 (
    echo [ACTION] Requesting Administrator privileges...
    powershell -Command "Start-Process cmd -ArgumentList '/c \"\"%~f0\"\"' -Verb RunAs"
    exit /b
)

echo [STATUS] Running with Administrator privileges.
echo.
echo Opening ports in Windows Defender Firewall:
echo   - Port 80   (Apache HTTP Web Server)
echo   - Port 8000 (Artisan Built-in Server)
echo   - Port 3306 (MySQL Database Network Access)
echo.

echo [1/3] Adding rule for Port 80 (Apache)...
netsh advfirewall firewall delete rule name="Kayan ERP Apache Port 80" >nul 2>&1
netsh advfirewall firewall add rule name="Kayan ERP Apache Port 80" dir=in action=allow protocol=TCP localport=80

echo [2/3] Adding rule for Port 8000 (Artisan Server)...
netsh advfirewall firewall delete rule name="Kayan ERP Artisan Port 8000" >nul 2>&1
netsh advfirewall firewall add rule name="Kayan ERP Artisan Port 8000" dir=in action=allow protocol=TCP localport=8000

echo [3/3] Adding rule for Port 3306 (MySQL)...
netsh advfirewall firewall delete rule name="Kayan ERP MySQL Port 3306" >nul 2>&1
netsh advfirewall firewall add rule name="Kayan ERP MySQL Port 3306" dir=in action=allow protocol=TCP localport=3306

echo.
echo =======================================================================
echo   FIREWALL PORTS OPENED SUCCESSFULLY!
echo   All cashier, POS, and manager devices on the local LAN can now connect.
echo =======================================================================
echo.
pause
