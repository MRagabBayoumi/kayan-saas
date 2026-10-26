Option Explicit
Dim WshShell, projectDir, phpExe, apacheExe, apacheConf, serverScript

Set WshShell = CreateObject("WScript.Shell")

projectDir = "C:\Users\PC\Projects\kayan-erp"
phpExe = "C:\Users\PC\.config\herd-lite\bin\php.exe"
apacheExe = "C:\xampp\apache\bin\httpd.exe"
apacheConf = projectDir & "\deploy\apache\kayan-erp-apache.conf"
serverScript = projectDir & "\server.php"

' 1. Stop any existing instances cleanly
WshShell.Run "powershell.exe -NoProfile -ExecutionPolicy Bypass -File """ & projectDir & "\stop-live-server.ps1""", 0, True

WScript.Sleep 600

' 2. Launch 4 background PHP workers with ZERO console windows
WshShell.CurrentDirectory = projectDir
WshShell.Run """" & phpExe & """ -S 127.0.0.1:8001 """ & serverScript & """", 0, False
WshShell.Run """" & phpExe & """ -S 127.0.0.1:8002 """ & serverScript & """", 0, False
WshShell.Run """" & phpExe & """ -S 127.0.0.1:8003 """ & serverScript & """", 0, False
WshShell.Run """" & phpExe & """ -S 127.0.0.1:8004 """ & serverScript & """", 0, False

WScript.Sleep 600

' 3. Launch Apache Front Server on port 8000 with ZERO console windows
WshShell.CurrentDirectory = "C:\xampp\apache"
WshShell.Run """" & apacheExe & """ -d ""C:/xampp/apache"" -f """ & apacheConf & """", 0, False

WScript.Sleep 800
