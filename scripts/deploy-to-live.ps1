# ==============================================================================
# Kayan ERP Deployment Script: Development -> Production (Live)
# ==============================================================================

$ErrorActionPreference = "Stop"

$devDir  = "C:\Users\PC\Projects\kayan-erp"
$liveDir = "C:\Users\PC\Projects\kayan-erp-live"
$phpPath = "C:\Users\PC\.config\herd-lite\bin\php.exe"

Write-Host "==========================================================" -ForegroundColor Cyan
Write-Host "   Starting Deployment: Development -> Kayan ERP Live     " -ForegroundColor Cyan
Write-Host "==========================================================" -ForegroundColor Cyan

# 1. Check directories
if (-not (Test-Path $devDir)) {
    Write-Error "Development directory not found: $devDir"
    exit 1
}
if (-not (Test-Path $liveDir)) {
    Write-Error "Live directory not found: $liveDir"
    exit 1
}

# 2. Build assets in development if needed
Write-Host "`n[1/5] Building frontend assets..." -ForegroundColor Yellow
Set-Location $devDir
& npm run build
if ($LASTEXITCODE -ne 0) {
    Write-Error "npm run build failed. Aborting deployment."
    exit 1
}

# 3. Synchronize code from dev to live (preserving .env and storage)
Write-Host "`n[2/5] Synchronizing application files..." -ForegroundColor Yellow

# Target directories to sync safely
$syncFolders = @("app", "bootstrap", "config", "database", "public", "resources", "routes", "scripts")
foreach ($folder in $syncFolders) {
    $src = Join-Path $devDir $folder
    $dst = Join-Path $liveDir $folder
    if (Test-Path $src) {
        robocopy $src $dst /E /PURGE /XD .git .gemini .claude storage\logs /XF .env | Out-Null
    }
}

# Sync vendor packages to ensure newly installed composer dependencies are present in Live
$srcVendor = Join-Path $devDir "vendor"
$dstVendor = Join-Path $liveDir "vendor"
if (Test-Path $srcVendor) {
    robocopy $srcVendor $dstVendor /E /XD .git | Out-Null
}

# Sync root project files (excluding .env, storage, etc.)
$rootFiles = @("composer.json", "composer.lock", "package.json", "package-lock.json", "vite.config.js", "artisan")
foreach ($file in $rootFiles) {
    $srcFile = Join-Path $devDir $file
    $dstFile = Join-Path $liveDir $file
    if (Test-Path $srcFile) {
        Copy-Item -Path $srcFile -Destination $dstFile -Force
    }
}

Write-Host "Files synchronized successfully (storage and .env were protected)." -ForegroundColor Green

# 4. Run database migrations in Live
Write-Host "`n[3/5] Running database migrations on live database..." -ForegroundColor Yellow
Set-Location $liveDir
& $phpPath artisan migrate --force
if ($LASTEXITCODE -ne 0) {
    Write-Warning "Artisan migrate returned exit code $LASTEXITCODE. Continuing..."
}

# 5. Clear and optimize caches in Live
Write-Host "`n[4/5] Optimizing live caches (config, routes, views)..." -ForegroundColor Yellow
& $phpPath artisan optimize:clear | Out-Null
& $phpPath artisan optimize
if ($LASTEXITCODE -ne 0) {
    Write-Warning "Artisan optimize returned exit code $LASTEXITCODE."
}

# 6. Restart live server
Write-Host "`n[5/5] Restarting live server..." -ForegroundColor Yellow
& "$liveDir\restart-live-server.bat"

# Wait a moment for server to bind
Start-Sleep -Seconds 3

# Verify response
try {
    $response = Invoke-WebRequest -Uri "http://127.0.0.1:8000" -UseBasicParsing -TimeoutSec 10
    if ($response.StatusCode -eq 200) {
        Write-Host "`n==========================================================" -ForegroundColor Green
        Write-Host "   SUCCESS: Kayan ERP Live updated and running!          " -ForegroundColor Green
        Write-Host "   Local:   http://localhost:8000                        " -ForegroundColor Green
        Write-Host "   Network: http://192.168.0.14:8000                     " -ForegroundColor Green
        Write-Host "==========================================================" -ForegroundColor Green
    }
} catch {
    Write-Warning "Could not verify HTTP 200 on port 8000 immediately. Server may still be starting."
}

Set-Location $devDir
