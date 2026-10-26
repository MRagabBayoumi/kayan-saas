@echo off
chcp 65001 > nul
title Kayan ERP Automated Database Backup

:: إعداد المسارات والمتغيرات
:: يمكنك تغيير مسار المجلد الاحتياطي ومسار XAMPP بما يناسب جهاز العميل
set BACKUP_DIR=C:\KayanERP_Backups
set MYSQL_BIN=C:\xampp\mysql\bin
set DB_NAME=kayan_erp
set DB_USER=root
set DB_PASS=

:: إنشاء مجلد النسخ الاحتياطية إذا لم يكن موجوداً
if not exist "%BACKUP_DIR%" (
    mkdir "%BACKUP_DIR%"
)

:: تنسيق التاريخ والوقت
for /f "tokens=2 delims==" %%I in ('wmic os get localdatetime /value') do set datetime=%%I
set TIMESTAMP=%datetime:~0,4%-%datetime:~4,2%-%datetime:~6,2%_%datetime:~8,2%-%datetime:~10,2%

set BACKUP_FILE=%BACKUP_DIR%\%DB_NAME%_backup_%TIMESTAMP%.sql

echo ========================================================
echo        جاري إنشاء نسخة احتياطية لقاعدة البيانات...
echo ========================================================
echo قاعدة البيانات: %DB_NAME%
echo التاريخ والوقت: %TIMESTAMP%
echo ملف الحفظ: %BACKUP_FILE%
echo.

if "%DB_PASS%"=="" (
    "%MYSQL_BIN%\mysqldump.exe" -u %DB_USER% --routines --triggers --single-transaction %DB_NAME% > "%BACKUP_FILE%"
) else (
    "%MYSQL_BIN%\mysqldump.exe" -u %DB_USER% -p%DB_PASS% --routines --triggers --single-transaction %DB_NAME% > "%BACKUP_FILE%"
)

if %errorlevel% equ 0 (
    echo [نجاح]: تم إنشاء النسخة الاحتياطية بنجاح وحفظها في:
    echo %BACKUP_FILE%
) else (
    echo [خطأ]: فشلت عملية النسخ الاحتياطي! يرجى التحقق من مسار MySQL وبيانات الاتصال.
)

echo ========================================================
echo.
:: لإبقاء النافذة مفتوحة عند التشغيل اليدوي (أزل التعليق عن pause إذا لزم)
:: pause
