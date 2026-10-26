@echo off
chcp 65001 > nul
title Kayan ERP - Firewall Port Opener

echo ========================================================
echo   فتح منافذ جدار حماية ويندوز لشبكة كايان ERP المحلية
echo ========================================================
echo.

:: التحقق من صلاحيات المسؤول
net session >nul 2>&1
if %errorlevel% neq 0 (
    echo [تنبيه]: يرجى تشغيل هذا الملف كمسؤول (Run as Administrator).
    echo اضغط كليك يمين على الملف واختر: Run as administrator
    echo.
    pause
    exit /b 1
)

echo [إجراء]: جاري فتح المنفذ 80 (Apache HTTP)...
netsh advfirewall firewall delete rule name="Kayan ERP Apache Port 80" >nul 2>&1
netsh advfirewall firewall add rule name="Kayan ERP Apache Port 80" dir=in action=allow protocol=TCP localport=80

echo [إجراء]: جاري فتح المنفذ 8000 (Artisan Serve)...
netsh advfirewall firewall delete rule name="Kayan ERP Artisan Port 8000" >nul 2>&1
netsh advfirewall firewall add rule name="Kayan ERP Artisan Port 8000" dir=in action=allow protocol=TCP localport=8000

echo [إجراء]: جاري فتح المنفذ 3306 (MySQL Network if needed)...
netsh advfirewall firewall delete rule name="Kayan ERP MySQL Port 3306" >nul 2>&1
netsh advfirewall firewall add rule name="Kayan ERP MySQL Port 3306" dir=in action=allow protocol=TCP localport=3306

echo.
echo ========================================================
echo   تم فتح المنافذ في جدار الحماية بنجاح تام!
echo   يمكن الآن لجميع أجهزة الكاشير والإدارة الدخول عبر IP السيرفر.
echo ========================================================
echo.
pause
