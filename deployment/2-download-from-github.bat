@echo off
setlocal enabledelayedexpansion
title Kayan ERP - GitHub Project Downloader

echo =======================================================================
echo              KAYAN ERP - GITHUB PROJECT DOWNLOADER
echo =======================================================================
echo.
echo This tool downloads the latest version of Kayan ERP from GitHub.
echo Target Repository: https://github.com/MRagabBayoumi/kayan-erp-new.git
echo.

set "DEFAULT_DIR=C:\xampp\htdocs\kayan-erp"
if exist "C:\xampp\htdocs" (
    set "TARGET_DIR=%DEFAULT_DIR%"
) else (
    set "TARGET_DIR=%CD%\kayan-erp"
)

echo Default installation path: %TARGET_DIR%
set /p USER_DIR="Enter destination folder (Press Enter for default): "
if not "%USER_DIR%"=="" set "TARGET_DIR=%USER_DIR%"

echo.
echo Target folder: %TARGET_DIR%

if exist "%TARGET_DIR%\artisan" (
    echo.
    echo [NOTE] A Kayan ERP installation was detected in %TARGET_DIR%.
    set /p UPDATE_CHOICE="Do you want to PULL latest updates from GitHub? (Y/N): "
    if /i "!UPDATE_CHOICE!"=="Y" (
        cd /d "%TARGET_DIR%"
        where git >nul 2>nul
        if %errorlevel% equ 0 (
            echo Pulling latest updates via Git...
            git pull origin main
        ) else (
            echo [ERROR] Git is not installed to update existing repository.
        )
        goto SUCCESS
    ) else (
        echo Operation cancelled.
        pause
        exit /b 0
    )
)

:: -------------------------------------------------------------------------
:: Check if Git is available
:: -------------------------------------------------------------------------
where git >nul 2>nul
if %errorlevel% equ 0 (
    echo [METHOD 1] Git detected. Cloning repository...
    git clone https://github.com/MRagabBayoumi/kayan-erp-new.git "%TARGET_DIR%"
    if %errorlevel% equ 0 goto SUCCESS
)

:: -------------------------------------------------------------------------
:: Fallback: Download via ZIP Archive (Works without Git!)
:: -------------------------------------------------------------------------
echo [METHOD 2] Downloading project ZIP directly from GitHub...
set "TEMP_ZIP=%TEMP%\kayan-erp-main.zip"
set "TEMP_EXTRACT=%TEMP%\kayan_extract"

if exist "%TEMP_ZIP%" del /f /q "%TEMP_ZIP%"
if exist "%TEMP_EXTRACT%" rmdir /s /q "%TEMP_EXTRACT%"

where curl.exe >nul 2>nul
if %errorlevel% equ 0 (
    curl.exe -# -L -o "%TEMP_ZIP%" "https://github.com/MRagabBayoumi/kayan-erp-new/archive/refs/heads/main.zip"
) else (
    powershell -Command "[Net.ServicePointManager]::SecurityProtocol = [Net.SecurityProtocolType]::Tls12; (New-Object Net.WebClient).DownloadFile('https://github.com/MRagabBayoumi/kayan-erp-new/archive/refs/heads/main.zip', '%TEMP_ZIP%')"
)

if not exist "%TEMP_ZIP%" (
    echo [ERROR] Failed to download ZIP from GitHub. Please check internet connection.
    pause
    exit /b 1
)

echo Extracting project files...
powershell -Command "Expand-Archive -Path '%TEMP_ZIP%' -DestinationPath '%TEMP_EXTRACT%' -Force"

if not exist "%TARGET_DIR%" mkdir "%TARGET_DIR%"
xcopy /E /I /H /Y "%TEMP_EXTRACT%\kayan-erp-new-main\*" "%TARGET_DIR%\" >nul

del /f /q "%TEMP_ZIP%" >nul 2>nul
rmdir /s /q "%TEMP_EXTRACT%" >nul 2>nul

:SUCCESS
echo.
echo =======================================================================
echo   PROJECT DOWNLOADED SUCCESSFULLY!
echo =======================================================================
echo Location: %TARGET_DIR%
echo.
echo Next step:
echo   Run 'deployment\3-install-and-setup.bat' inside %TARGET_DIR%
echo   to configure the database and dependencies.
echo =======================================================================
echo.

set /p RUN_SETUP="Do you want to run the installer now? (Y/N) [Default Y]: "
if "%RUN_SETUP%"=="" set RUN_SETUP=Y
if /i "%RUN_SETUP%"=="Y" (
    cd /d "%TARGET_DIR%\deployment"
    call "3-install-and-setup.bat"
) else (
    pause
)
exit /b 0
