' Kayan ERP - Silent Background Service Launcher
' Strictly ASCII - Zero Encoding Issues Across All Windows Editions
Option Explicit
Dim WshShell, fso, appDir, phpPath, mysqlPath, projectPath, msedgePath

Set WshShell = CreateObject("WScript.Shell")
Set fso = CreateObject("Scripting.FileSystemObject")

appDir = fso.GetParentFolderName(WScript.ScriptFullName) & "\.."
phpPath = appDir & "\server\php\php.exe"
mysqlPath = appDir & "\server\mariadb\bin\mysqld.exe"
projectPath = appDir & "\kayan-erp"

' 1. Start MariaDB Database service silently if not already running
WshShell.Run "cmd /c tasklist | find /i ""mysqld.exe"" || """ & mysqlPath & """ --defaults-file=""" & appDir & "\server\mariadb\my.ini""", 0, False

' 2. Start PHP Built-in Server silently on 0.0.0.0:8000 with multi-worker support
WshShell.Environment("PROCESS")("PHP_CLI_SERVER_WORKERS") = "6"
WshShell.Run "cmd /c tasklist | find /i ""php.exe"" || """ & phpPath & """ -S 0.0.0.0:8000 -t """ & projectPath & "\public""", 0, False

WScript.Sleep 1500

' 3. Open Application in Native Edge App Mode (Clean frameless Desktop Window)
msedgePath = "C:\Program Files (x86)\Microsoft\Edge\Application\msedge.exe"
If Not fso.FileExists(msedgePath) Then
    msedgePath = "C:\Program Files\Microsoft\Edge\Application\msedge.exe"
End If

If fso.FileExists(msedgePath) Then
    WshShell.Run """" & msedgePath & """ --app=http://127.0.0.1:8000 --start-maximized --window-name=""Kayan ERP""", 1, False
Else
    WshShell.Run "http://127.0.0.1:8000", 1, False
End If
