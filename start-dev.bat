@echo off
chcp 65001 >nul
title Kayan ERP - وضع المطور المباشر
echo ================================================================
echo           تشغيل كيان ERP في وضع المطور (Live Development)
echo ================================================================
echo.
echo  - رابط الجهاز الحالي:  http://127.0.0.1:8001
echo  - رابط الشبكة المحلية: http://192.168.0.14:8001
echo.
echo  [1] جاري تشغيل مراقب التعديلات اللحظي (Vite Hot Reload)...
start "Kayan ERP - Vite Live" cmd /k "npm run dev"
echo.
echo  [2] جاري تشغيل سيرفر التطبيق...
echo  أغلق هذه النافذة أو اضغط Ctrl+C لإيقاف السيرفر.
echo ================================================================
where php >nul 2>nul
if %errorlevel% neq 0 (
    if exist "C:\xampp\php\php.exe" set "PATH=C:\xampp\php;%PATH%"
)
php artisan serve --host=0.0.0.0 --port=8000
pause
