@echo off
chcp 65001 >nul
powershell.exe -NoProfile -ExecutionPolicy Bypass -File "C:\Users\PC\Projects\kayan-erp\scripts\deploy-to-live.ps1"
pause
