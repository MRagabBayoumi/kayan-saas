# Check if port 8000 is already active
$conn = Get-NetTCPConnection -LocalPort 8000 -State Listen -ErrorAction SilentlyContinue
if ($conn) {
    Write-Host "Kayan ERP Live is already running on port 8000." -ForegroundColor Green
    exit 0
}

Write-Host "Starting Kayan ERP Multi-Worker Production Stack silently..." -ForegroundColor Cyan

# Launch via WScript for 100% silent execution (Zero console windows)
wscript.exe "C:\Users\PC\Projects\kayan-erp-live\start-live.vbs"

# Wait for port 8000 to be ready
$ready = $false
for ($i = 0; $i -lt 12; $i++) {
    Start-Sleep -Milliseconds 400
    try {
        $resp = Invoke-WebRequest -Uri "http://127.0.0.1:8000/login" -UseBasicParsing -TimeoutSec 2 -ErrorAction SilentlyContinue
        if ($resp -and $resp.StatusCode -eq 200) {
            $ready = $true
            break
        }
    } catch { }
}

if ($ready) {
    Write-Host "Kayan ERP Live is now running cleanly on http://127.0.0.1:8000 (No popup windows)." -ForegroundColor Green
} else {
    Write-Host "Server initialized." -ForegroundColor Yellow
}
