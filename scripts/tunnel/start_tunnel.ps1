param(
    [int]$Port = 8000
)

$tunnelExe = "$PSScriptRoot\cloudflared.exe"
if (!(Test-Path $tunnelExe)) {
    Write-Host "[!] cloudflared.exe not found. Downloading latest version from Cloudflare..." -ForegroundColor Cyan
    try {
        Invoke-WebRequest -Uri "https://github.com/cloudflare/cloudflared/releases/latest/download/cloudflared-windows-amd64.exe" -OutFile $tunnelExe -UseBasicParsing
        Write-Host "[✓] Download complete!" -ForegroundColor Green
    } catch {
        Write-Host "Error downloading cloudflared.exe: $_" -ForegroundColor Red
        exit 1
    }
}

Write-Host "==========================================================" -ForegroundColor Cyan
Write-Host "       Kayan ERP - Sales Rep Mobile Cloudflare Tunnel     " -ForegroundColor Cyan
Write-Host "==========================================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "[1/2] Checking local web server on port $Port..." -ForegroundColor Yellow

$portCheck = Get-NetTCPConnection -LocalPort $Port -State Listen -ErrorAction SilentlyContinue
if (!$portCheck) {
    Write-Host "[!] Local server is not running on port $Port. Starting server..." -ForegroundColor Yellow
    $phpExe = "C:\Users\PC\.config\herd-lite\bin\php.exe"
    if (!(Test-Path $phpExe)) { $phpExe = "php" }
    Start-Process -FilePath $phpExe -ArgumentList "artisan serve --port=$Port" -WorkingDirectory "c:\Users\PC\Projects\kayan-erp" -WindowStyle Minimized
    Start-Sleep -Seconds 2
}

Write-Host "[2/2] Establishing secure Cloudflare Tunnel..." -ForegroundColor Yellow

$logFile = "$env:TEMP\cloudflared_quick.log"
if (Test-Path $logFile) { Remove-Item $logFile -Force }

$proc = Start-Process -FilePath $tunnelExe -ArgumentList "tunnel --url http://127.0.0.1:$Port" -RedirectStandardError $logFile -PassThru -WindowStyle Hidden

Write-Host "Connecting to Cloudflare global network..." -ForegroundColor Gray
$tunnelUrl = $null
for ($i = 0; $i -lt 30; $i++) {
    Start-Sleep -Seconds 1
    if (Test-Path $logFile) {
        $lines = Get-Content $logFile -ErrorAction SilentlyContinue
        foreach ($line in $lines) {
            if ($line -match 'https://[a-zA-Z0-9-]+\.trycloudflare\.com') {
                $tunnelUrl = $matches[0]
                break
            }
        }
    }
    if ($tunnelUrl) { break }
}

if ($tunnelUrl) {
    Clear-Host
    Write-Host "==========================================================" -ForegroundColor Green
    Write-Host "   طھظ… ط¥ظ†ط´ط§ط، ط§ظ„ظ†ظپظ‚ ط§ظ„ط¢ظ…ظ† ط¨ظ†ط¬ط§ط­ ظˆط³ظٹط±ظپط±ظƒ ظ…طھط§ط­ ط§ظ„ط¢ظ† ط¹ط§ظ„ظ…ظٹط§ظ‹!   " -ForegroundColor Green
    Write-Host "==========================================================" -ForegroundColor Green
    Write-Host ""
    Write-Host "ط±ط§ط¨ط· ط¨ظˆط§ط¨ط© ط§ظ„ظ…ظ†ط§ط¯ظٹط¨ ط§ظ„ط®ط§ط±ط¬ظٹ (HTTPS ظ…ط´ظپط±):" -ForegroundColor White
    Write-Host "  $tunnelUrl/portal" -ForegroundColor Cyan -BackgroundColor Black
    Write-Host ""
    Write-Host "ط±ط§ط¨ط· طµظپط­ط© ط§ظ„ط¯ط®ظˆظ„ ط§ظ„ط±ط¦ظٹط³ظٹط©:" -ForegroundColor White
    Write-Host "  $tunnelUrl/login" -ForegroundColor Yellow -BackgroundColor Black
    Write-Host ""
    Write-Host "----------------------------------------------------------" -ForegroundColor Gray
    Write-Host "طھط¹ظ„ظٹظ…ط§طھ ظ‡ط§ظ…ط© ظ„ظ„ظ…ظ†ط§ط¯ظٹط¨:" -ForegroundColor White
    Write-Host "1. ط§ظ†ط³ط® ظ‡ط°ط§ ط§ظ„ط±ط§ط¨ط· ظˆط£ط±ط³ظ„ظ‡ ظ„ظ„ظ…ظ†ط¯ظˆط¨ ظپظٹ ط±ط³ط§ظ„ط© ظˆط§طھط³ط§ط¨." -ForegroundColor Gray
    Write-Host "2. ط¹ظ†ط¯ ظپطھط­ ط§ظ„ط±ط§ط¨ط· ظ…ظ† ظ…طھطµظپط­ ط§ظ„ظ…ظˆط¨ط§ظٹظ„ (Chrome ط£ظˆ Safari):" -ForegroundColor Gray
    Write-Host "   - ط§ط¶ط؛ط· ط¹ظ„ظ‰ ط®ظٹط§ط±ط§طھ ط§ظ„ظ…طھطµظپط­ (ط«ظ„ط§ط« ظ†ظ‚ط§ط· ط£ظˆ ط²ط± ط§ظ„ظ…ط´ط§ط±ظƒط©)." -ForegroundColor Gray
    Write-Host "   - ط§ط®طھط± (Add to Home screen / ط¥ط¶ط§ظپط© ط¥ظ„ظ‰ ط§ظ„ط´ط§ط´ط© ط§ظ„ط±ط¦ظٹط³ظٹط©)." -ForegroundColor Gray
    Write-Host "   - ط³ظٹطھط­ظˆظ„ ط§ظ„ط±ط§ط¨ط· ظپظˆط±ط§ظ‹ ط¥ظ„ظ‰ ط£ظٹظ‚ظˆظ†ط© طھط·ط¨ظٹظ‚ ظ…ط«ظ„ طھط·ط¨ظٹظ‚ط§طھ ط§ظ„ظ…ظˆط¨ط§ظٹظ„!" -ForegroundColor Gray
    Write-Host "3. ظٹط³ط¬ظ„ ط§ظ„ظ…ظ†ط¯ظˆط¨ ط§ظ„ط¯ط®ظˆظ„ ط¨ط­ط³ط§ط¨ظ‡ ظˆظƒظ„ظ…ط© ظ…ط±ظˆط±ظ‡ ظ„ظ„ط¨ط¯ط، ط¨ط§ظ„ط¹ظ…ظ„." -ForegroundColor Gray
    Write-Host "----------------------------------------------------------" -ForegroundColor Gray
    Write-Host "ظ…ظ„ط§ط­ط¸ط©: ط§طھط±ظƒ ظ‡ط°ظ‡ ط§ظ„ظ†ط§ظپط°ط© ظ…ظپطھظˆط­ط© ط·ظˆط§ظ„ ظپطھط±ط© ط¹ظ…ظ„ ط§ظ„ظ…ظ†ط§ط¯ظٹط¨ ط¨ط§ظ„ط®ط§ط±ط¬." -ForegroundColor Magenta
    Write-Host "ظ„ط¥ظٹظ‚ط§ظپ ط§ظ„ط§طھطµط§ظ„ ط§ط¶ط؛ط· Ctrl+C ط£ظˆ ط£ط؛ظ„ظ‚ ظ‡ط°ظ‡ ط§ظ„ظ†ط§ظپط°ط©." -ForegroundColor DarkGray
    Write-Host ""

    # Keep script waiting until process exits or user closes
    $proc.WaitForExit()
} else {
    Write-Host "طھط¹ط°ط± ط§ظ„ط­طµظˆظ„ ط¹ظ„ظ‰ ط§ظ„ط±ط§ط¨ط· ط§ظ„ط³ط­ط§ط¨ظٹ. ظٹط±ط¬ظ‰ ظ…ط±ط§ط¬ط¹ط© ط§ظ„ط§طھطµط§ظ„ ط¨ط§ظ„ط¥ظ†طھط±ظ†طھ." -ForegroundColor Red
}
