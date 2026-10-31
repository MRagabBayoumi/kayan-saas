$phpExe = "C:\Users\PC\.config\herd-lite\bin\php.exe"
$apacheExe = "C:\xampp\apache\bin\httpd.exe"
$apacheConf = "C:\Users\PC\Projects\kayan-erp-live\deploy\apache\kayan-erp-apache.conf"
$serverScript = "C:\Users\PC\Projects\kayan-erp-live\server.php"

# Start the 4 background PHP workers
$p1 = Start-Process -FilePath $phpExe -ArgumentList "-S", "127.0.0.1:8001", "$serverScript" -WorkingDirectory "C:\Users\PC\Projects\kayan-erp-live" -PassThru
$p2 = Start-Process -FilePath $phpExe -ArgumentList "-S", "127.0.0.1:8002", "$serverScript" -WorkingDirectory "C:\Users\PC\Projects\kayan-erp-live" -PassThru
$p3 = Start-Process -FilePath $phpExe -ArgumentList "-S", "127.0.0.1:8003", "$serverScript" -WorkingDirectory "C:\Users\PC\Projects\kayan-erp-live" -PassThru
$p4 = Start-Process -FilePath $phpExe -ArgumentList "-S", "127.0.0.1:8004", "$serverScript" -WorkingDirectory "C:\Users\PC\Projects\kayan-erp-live" -PassThru

Write-Host "Started 4 PHP workers on ports 8001-8004. Starting Apache on port 8000..." -ForegroundColor Green

# Run Apache in foreground
& $apacheExe -d "C:/xampp/apache" -f "$apacheConf"
