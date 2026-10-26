[Console]::OutputEncoding = [System.Text.Encoding]::UTF8

Write-Host "==========================================================" -ForegroundColor Cyan
Write-Host "                Kayan ERP Launcher                        " -ForegroundColor Cyan
Write-Host "==========================================================" -ForegroundColor Cyan

# 1. Check MySQL Service
$mysql = Get-Service -Name "MySQL80" -ErrorAction SilentlyContinue
if ($mysql -and $mysql.Status -ne "Running") {
    Write-Host "`n[1/3] Starting MySQL Service..." -ForegroundColor Yellow
    Start-Service -Name "MySQL80" -ErrorAction SilentlyContinue
    Start-Sleep -Seconds 2
} else {
    Write-Host "`n[1/3] MySQL Database is running." -ForegroundColor Green
}

# 2. Check Port 8000
$conn = Get-NetTCPConnection -LocalPort 8000 -State Listen -ErrorAction SilentlyContinue
$needStart = $true

if ($conn) {
    try {
        $resp = Invoke-WebRequest -Uri "http://127.0.0.1:8000" -UseBasicParsing -TimeoutSec 2 -ErrorAction SilentlyContinue
        if ($resp -and $resp.StatusCode -eq 200) {
            $proc = Get-CimInstance Win32_Process -Filter "ProcessId = $($conn[0].OwningProcess)" -ErrorAction SilentlyContinue
            if ($proc -and ($proc.CommandLine -match "kayan-erp" -or $proc.Name -match "httpd")) {
                Write-Host "`n[2/3] Server is already running." -ForegroundColor Green
                $needStart = $false
            } else {
                Write-Host "`n[2/3] Cleaning up port 8000..." -ForegroundColor Yellow
                & "C:\Users\PC\Projects\kayan-erp-live\stop-live-server.ps1"
                Start-Sleep -Seconds 1
            }
        }
    } catch {
        Write-Host "`n[2/3] Cleaning up port 8000..." -ForegroundColor Yellow
        & "C:\Users\PC\Projects\kayan-erp-live\stop-live-server.ps1"
        Start-Sleep -Seconds 1
    }
}

if ($needStart) {
    Write-Host "`n[2/3] Starting Kayan ERP Live server..." -ForegroundColor Yellow
    & "C:\Users\PC\Projects\kayan-erp-live\start-live-server.ps1"
    
    $ready = $false
    for ($i = 0; $i -lt 6; $i++) {
        Start-Sleep -Seconds 1
        try {
            $check = Invoke-WebRequest -Uri "http://127.0.0.1:8000" -UseBasicParsing -TimeoutSec 2 -ErrorAction SilentlyContinue
            if ($check -and $check.StatusCode -eq 200) {
                $ready = $true
                break
            }
        } catch {}
    }
}

# 3. Open browser
Write-Host "`n[3/3] Opening Kayan ERP in your browser..." -ForegroundColor Green
Start-Process "http://localhost:8000"

Write-Host "`n==========================================================" -ForegroundColor Green
Write-Host "      Kayan ERP is now running and opened successfully!   " -ForegroundColor Green
Write-Host "   - Local URL:   http://localhost:8000                   " -ForegroundColor Green
Write-Host "   - Network URL: http://192.168.0.14:8000 أو http://192.168.0.14 " -ForegroundColor Green
Write-Host "==========================================================" -ForegroundColor Green
Write-Host "Server is running in background. You can close this window." -ForegroundColor Gray

Start-Sleep -Seconds 3
