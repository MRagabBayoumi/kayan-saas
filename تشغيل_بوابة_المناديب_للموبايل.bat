@echo off
chcp 65001 >nul
title Kayan ERP - Sales Rep Mobile Portal

echo ==================================================================
echo    Kayan ERP - تشغيل بوابة المندوب والمبيعات الميدانية
echo ==================================================================
echo.

cd /d "C:\Users\PC\Projects\kayan-erp"

echo [1/2] التحقق من السيرفر المحلي...
netstat -ano | findstr :8000 | findstr LISTENING >nul
if %ERRORLEVEL% neq 0 (
    echo [!] جاري تشغيل سيرفر النظام...
    start /min "Kayan ERP Server" "C:\Users\PC\.config\herd-lite\bin\php.exe" artisan serve --port=8000
    timeout /t 3 /nobreak >nul
) else (
    echo [OK] السيرفر المحلي يعمل بنجاح.
)

echo.
echo [2/2] جاري الاتصال بشبكة Cloudflare العالمية وتوليد الرابط المشفر...
echo.
echo ==================================================================
echo تعليمات:
echo 1. سيظهر لك في المربع أدناه رابط ينتهي بـ .trycloudflare.com
echo 2. قم بنسخ الرابط وإضافة /portal في آخره (أو استخدم الرابط كما هو)
echo    مثال: https://xxxx-xxxx.trycloudflare.com/portal
echo 3. أرسل الرابط للمندوب عبر الواتساب ليفتحه من هاتفه.
echo 4. اترك هذه الشاشة مفتوحة طوال فترة عمل المناديب بالخارج.
echo ==================================================================
echo.

"C:\Users\PC\Projects\kayan-erp\scripts\tunnel\cloudflared.exe" tunnel --url http://127.0.0.1:8000

echo.
echo تم إيقاف النفق السحابي.
pause
