' Kayan ERP - Stop Background Services
' Strictly ASCII
Option Explicit
Dim WshShell
Set WshShell = CreateObject("WScript.Shell")

WshShell.Run "taskkill /F /IM php.exe /T", 0, True
WshShell.Run "taskkill /F /IM mysqld.exe /T", 0, True
MsgBox "Kayan ERP services stopped successfully.", 64, "Kayan ERP"
