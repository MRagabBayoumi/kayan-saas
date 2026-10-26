Write-Host "Stopping Kayan ERP Live servers..." -ForegroundColor Yellow

# 1. Stop processes on port 8000 (Apache) and worker ports 8001..8004
$ports = @(8000, 8001, 8002, 8003, 8004)
foreach ($port in $ports) {
    $connections = Get-NetTCPConnection -LocalPort $port -State Listen -ErrorAction SilentlyContinue
    foreach ($conn in $connections) {
        try {
            $proc = Get-Process -Id $conn.OwningProcess -ErrorAction SilentlyContinue
            if ($proc) {
                Write-Host "Stopping process $($proc.ProcessName) (PID: $($proc.Id)) on port $port..."
                Stop-Process -Id $proc.Id -Force -ErrorAction SilentlyContinue
            }
        } catch { }
    }
}

# 2. Stop any lingering Apache httpd instances started with our config
$httpdProcs = Get-CimInstance Win32_Process -Filter "name like '%httpd%'" -ErrorAction SilentlyContinue
foreach ($h in $httpdProcs) {
    if ($h.CommandLine -match "kayan-erp-apache.conf") {
        try {
            Stop-Process -Id $h.ProcessId -Force -ErrorAction SilentlyContinue
        } catch { }
    }
}

# 3. Stop any lingering PHP processes belonging to kayan-erp-live
$phpProcs = Get-CimInstance Win32_Process -Filter "name like '%php%'" -ErrorAction SilentlyContinue
foreach ($p in $phpProcs) {
    if ($p.CommandLine -match "kayan-erp-live" -or $p.CommandLine -match "8001" -or $p.CommandLine -match "8002" -or $p.CommandLine -match "8003" -or $p.CommandLine -match "8004") {
        try {
            Stop-Process -Id $p.ProcessId -Force -ErrorAction SilentlyContinue
        } catch { }
    }
}

Write-Host "Kayan ERP Live servers stopped successfully." -ForegroundColor Green
