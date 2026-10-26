@echo off
setlocal enabledelayedexpansion
title Kayan ERP - Prerequisites Downloader and Installer

echo =======================================================================
echo          KAYAN ERP - PREREQUISITES DOWNLOADER AND INSTALLER
echo =======================================================================
echo.
echo This tool helps you automatically download and install:
echo   - XAMPP (Apache + PHP 8.2 + MySQL 8.0)
echo   - Composer (PHP Dependency Manager)
echo   - Node.js LTS (JavaScript runtime for Vite)
echo   - Git for Windows
echo =======================================================================
echo.

set "INSTALL_DIR=C:\Kayan_Installers"
if not exist "%INSTALL_DIR%" mkdir "%INSTALL_DIR%"

:MENU
echo What would you like to download and install?
echo.
echo   [1] Download and install ALL missing tools (Recommended)
echo   [2] Download XAMPP (PHP 8.2 + MySQL + Apache)
echo   [3] Download Composer Setup
echo   [4] Download Node.js LTS (v20)
echo   [5] Download Git for Windows
echo   [6] Exit
echo.
set /p CHOICE="Enter your choice (1-6) [Default 1]: "
if "%CHOICE%"=="" set CHOICE=1

if "%CHOICE%"=="1" goto INSTALL_ALL
if "%CHOICE%"=="2" goto GET_XAMPP
if "%CHOICE%"=="3" goto GET_COMPOSER
if "%CHOICE%"=="4" goto GET_NODE
if "%CHOICE%"=="5" goto GET_GIT
if "%CHOICE%"=="6" goto END
goto MENU

:: -------------------------------------------------------------------------
:INSTALL_ALL
echo.
echo =======================================================================
echo [Step 1/4] Checking Git...
where git >nul 2>nul
if %errorlevel% equ 0 (
    echo [OK] Git is already installed.
) else (
    echo [ACTION] Git is missing. Downloading Git installer...
    call :DOWNLOAD_FILE "https://github.com/git-for-windows/git/releases/download/v2.44.0.windows.1/Git-2.44.0-64-bit.exe" "%INSTALL_DIR%\Git-Setup.exe"
    echo Launching Git installer...
    start /wait "" "%INSTALL_DIR%\Git-Setup.exe"
)

echo.
echo [Step 2/4] Checking Node.js...
where node >nul 2>nul
if %errorlevel% equ 0 (
    echo [OK] Node.js is already installed.
) else (
    echo [ACTION] Node.js is missing. Downloading Node.js installer...
    call :DOWNLOAD_FILE "https://nodejs.org/dist/v20.18.0/node-v20.18.0-x64.msi" "%INSTALL_DIR%\NodeJS-Setup.msi"
    echo Launching Node.js installer...
    msiexec /i "%INSTALL_DIR%\NodeJS-Setup.msi"
)

echo.
echo [Step 3/4] Checking XAMPP / PHP...
where php >nul 2>nul
if %errorlevel% equ 0 (
    echo [OK] PHP is already installed.
) else if exist "C:\xampp\php\php.exe" (
    echo [OK] XAMPP is already installed in C:\xampp.
) else (
    echo [ACTION] XAMPP is missing. Downloading XAMPP (PHP 8.2 + MySQL)...
    call :DOWNLOAD_FILE "https://sourceforge.net/projects/xampp/files/XAMPP%20Windows/8.2.12/xampp-windows-x64-8.2.12-0-VS16-installer.exe/download" "%INSTALL_DIR%\XAMPP-Setup.exe"
    echo Launching XAMPP installer...
    echo Please install XAMPP to default path: C:\xampp
    start /wait "" "%INSTALL_DIR%\XAMPP-Setup.exe"
)

echo.
echo [Step 4/4] Checking Composer...
where composer >nul 2>nul
if %errorlevel% equ 0 (
    echo [OK] Composer is already installed.
) else (
    echo [ACTION] Composer is missing. Downloading Composer installer...
    call :DOWNLOAD_FILE "https://getcomposer.org/Composer-Setup.exe" "%INSTALL_DIR%\Composer-Setup.exe"
    echo Launching Composer installer...
    start /wait "" "%INSTALL_DIR%\Composer-Setup.exe"
)

goto FINISH

:: -------------------------------------------------------------------------
:GET_XAMPP
echo.
echo Downloading XAMPP (PHP 8.2 + MySQL)...
call :DOWNLOAD_FILE "https://sourceforge.net/projects/xampp/files/XAMPP%20Windows/8.2.12/xampp-windows-x64-8.2.12-0-VS16-installer.exe/download" "%INSTALL_DIR%\XAMPP-Setup.exe"
echo Launching XAMPP installer...
start "" "%INSTALL_DIR%\XAMPP-Setup.exe"
goto FINISH

:: -------------------------------------------------------------------------
:GET_COMPOSER
echo.
echo Downloading Composer...
call :DOWNLOAD_FILE "https://getcomposer.org/Composer-Setup.exe" "%INSTALL_DIR%\Composer-Setup.exe"
echo Launching Composer installer...
start "" "%INSTALL_DIR%\Composer-Setup.exe"
goto FINISH

:: -------------------------------------------------------------------------
:GET_NODE
echo.
echo Downloading Node.js LTS (v20)...
call :DOWNLOAD_FILE "https://nodejs.org/dist/v20.18.0/node-v20.18.0-x64.msi" "%INSTALL_DIR%\NodeJS-Setup.msi"
echo Launching Node.js installer...
start "" "%INSTALL_DIR%\NodeJS-Setup.msi"
goto FINISH

:: -------------------------------------------------------------------------
:GET_GIT
echo.
echo Downloading Git for Windows...
call :DOWNLOAD_FILE "https://github.com/git-for-windows/git/releases/download/v2.44.0.windows.1/Git-2.44.0-64-bit.exe" "%INSTALL_DIR%\Git-Setup.exe"
echo Launching Git installer...
start "" "%INSTALL_DIR%\Git-Setup.exe"
goto FINISH

:: -------------------------------------------------------------------------
:: Helper function to download file via curl or PowerShell
:: -------------------------------------------------------------------------
:DOWNLOAD_FILE
set "URL=%~1"
set "DEST=%~2"
echo   Source: %URL%
echo   Target: %DEST%
where curl.exe >nul 2>nul
if %errorlevel% equ 0 (
    curl.exe -# -L -o "%DEST%" "%URL%"
) else (
    powershell -Command "[Net.ServicePointManager]::SecurityProtocol = [Net.SecurityProtocolType]::Tls12; $wc = New-Object Net.WebClient; $wc.DownloadFile('%URL%', '%DEST%')"
)
exit /b 0

:: -------------------------------------------------------------------------
:FINISH
echo.
echo =======================================================================
echo All selected installers have been downloaded to:
echo   %INSTALL_DIR%
echo.
echo After completing the installations:
echo   1. Start Apache and MySQL from XAMPP Control Panel.
echo   2. Run '2-download-from-github.bat' or '3-install-and-setup.bat'.
echo =======================================================================
echo.
pause
exit /b 0

:END
exit /b 0
