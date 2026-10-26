# Kayan ERP - Local HTTPS Server for Mobile
$ErrorActionPreference = "Stop"
Set-Location $PSScriptRoot

$phpProcess = $null

try {
    Write-Host "==========================================================" -ForegroundColor Cyan
    Write-Host " Kayan ERP - Starting Laravel and Caddy HTTPS Proxy" -ForegroundColor Green
    Write-Host "==========================================================" -ForegroundColor Cyan

    # 1. Stop any dangling caddy process
    Stop-Process -Name caddy -Force -ErrorAction SilentlyContinue

    # 2. Check MySQL database availability (Port 3306)
    $mysqlListening = Get-NetTCPConnection -LocalPort 3306 -ErrorAction SilentlyContinue
    if (-not $mysqlListening) {
        Write-Host "Checking MySQL server..." -ForegroundColor Yellow
        $xamppMysqld = "C:\xampp\mysql\bin\mysqld.exe"
        if (Test-Path $xamppMysqld) {
            Write-Host "Starting MySQL from XAMPP..." -ForegroundColor Cyan
            Start-Process -FilePath $xamppMysqld -ArgumentList "--defaults-file=C:\xampp\mysql\bin\my.ini", "--standalone" -WindowStyle Hidden
            Start-Sleep -Seconds 2
        }
        else {
            Write-Warning "MySQL is not running on port 3306! Please start MySQL from XAMPP Control Panel."
        }
    }
    else {
        Write-Host "MySQL is running on port 3306." -ForegroundColor Green
    }

    # 3. Detect local IPv4 addresses (Wi-Fi and Ethernet)
    $allIps = Get-NetIPAddress -AddressFamily IPv4 |
        Where-Object {
            $_.IPAddress -notlike '127.*' -and
            $_.IPAddress -notlike '169.254.*' -and
            $_.InterfaceAlias -notlike '*Loopback*'
        }

    $wifiIp = ($allIps | Where-Object { $_.InterfaceAlias -like '*Wi-Fi*' } | Select-Object -First 1).IPAddress
    $primaryIp = if ($wifiIp) { $wifiIp } else { ($allIps | Select-Object -First 1).IPAddress }

    if (-not $primaryIp) {
        throw "Could not detect a local IPv4 address."
    }

    Write-Host "`nNetwork IP Addresses:" -ForegroundColor Yellow
    foreach ($entry in $allIps) {
        Write-Host "  $($entry.InterfaceAlias): $($entry.IPAddress)" -ForegroundColor Cyan
    }

    # 4. Locate a compatible PHP runtime
    $herdPhp = Join-Path $HOME ".config\herd-lite\bin\php.exe"

    if (Test-Path $herdPhp) {
        $phpExe = $herdPhp
    }
    else {
        $phpCommand = Get-Command php -ErrorAction SilentlyContinue
        if (-not $phpCommand) {
            throw "PHP was not found. Install PHP or configure PATH."
        }
        $phpExe = $phpCommand.Source
    }

    # 5. Copy Caddy root certificate if available
    $caddyRoot = Join-Path $env:APPDATA "Caddy\pki\authorities\local\root.crt"
    $publicDir = Join-Path $PSScriptRoot "public"

    if ((Test-Path $caddyRoot) -and (Test-Path $publicDir)) {
        Copy-Item $caddyRoot (Join-Path $publicDir "caddy-root.crt") -Force
        Write-Host "Caddy root certificate ready." -ForegroundColor Green
    }

    # 6. Start Laravel on localhost
    Write-Host "`nStarting Laravel on 127.0.0.1:8000..." -ForegroundColor Yellow

    $phpProcess = Start-Process `
        -FilePath $phpExe `
        -ArgumentList @("artisan", "serve", "--host=127.0.0.1", "--port=8000") `
        -WorkingDirectory $PSScriptRoot `
        -PassThru `
        -NoNewWindow

    Start-Sleep -Seconds 2

    if ($phpProcess.HasExited) {
        throw "Laravel server stopped during startup. Check PHP and Laravel configuration."
    }

    # 7. Check Caddy availability
    $caddyCommand = Get-Command caddy -ErrorAction SilentlyContinue
    if (-not $caddyCommand) {
        throw "Caddy was not found in PATH."
    }

    Write-Host "`nStarting Caddy HTTPS proxy..." -ForegroundColor Yellow
    Write-Host "Open this address on a phone connected to the same Wi-Fi:" -ForegroundColor White
    Write-Host "👉 https://$primaryIp" -ForegroundColor Green
    Write-Host "👉 https://$($primaryIp):8443 (Alternative Port)" -ForegroundColor Green
    Write-Host ""
    Write-Host "Press Ctrl+C to stop the servers." -ForegroundColor Gray

    # Uses the Caddy configuration in the project directory
    & $caddyCommand.Source run
}
catch {
    Write-Host "`nERROR: $($_.Exception.Message)" -ForegroundColor Red
}
finally {
    Write-Host "`nStopping servers..." -ForegroundColor Yellow

    if ($phpProcess -and -not $phpProcess.HasExited) {
        Stop-Process -Id $phpProcess.Id -Force -ErrorAction SilentlyContinue
    }
    Stop-Process -Name caddy -Force -ErrorAction SilentlyContinue

    Write-Host "Server cleanup completed." -ForegroundColor Green
}
