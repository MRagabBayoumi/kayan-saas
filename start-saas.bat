@echo off
title Kayan ERP Cloud SaaS (Port 8080)
cd /d "C:\Users\PC\Projects\kayan-saas"
echo =======================================================
echo   Starting Kayan ERP Cloud SaaS (Multi-Tenant Engine)
echo   Central SaaS Dashboard: http://lvh.me:8080/saas
echo   Tenant 1 (Full):        http://demo1.lvh.me:8080
echo   Tenant 2 (Basic):       http://demo2.lvh.me:8080
echo =======================================================
"C:\Users\PC\.config\herd-lite\bin\php.exe" artisan serve --host=0.0.0.0 --port=8080
pause
