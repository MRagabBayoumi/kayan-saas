; ==============================================================================
; Kayan ERP - Inno Setup Script (Strictly English/ASCII Paths & Character Set)
; Generates: KayanERP-Setup.exe (One-Click Windows Installer)
; ==============================================================================

#define MyAppName "Kayan ERP"
#define MyAppVersion "2.0"
#define MyAppPublisher "BayoSoft Systems"
#define MyAppURL "http://127.0.0.1:8000"
#define MyAppExeName "start.vbs"

[Setup]
AppId={{9C52E71B-8A4F-4C82-B2A4-F2D7E92A13B4}
AppName={#MyAppName}
AppVersion={#MyAppVersion}
AppPublisher={#MyAppPublisher}
AppPublisherURL={#MyAppURL}
AppSupportURL={#MyAppURL}
AppUpdatesURL={#MyAppURL}
DefaultDirName=C:\KayanERP
DefaultGroupName={#MyAppName}
DisableProgramGroupPage=yes
LicenseFile=
OutputDir=.\Output
OutputBaseFilename=KayanERP-Setup
SetupIconFile=..\..\public\favicon.ico
Compression=lzma2/ultra64
SolidCompression=yes
WizardStyle=modern
PrivilegesRequired=admin
ArchitecturesInstallIn64BitMode=x64
UninstallDisplayIcon={app}\kayan-erp\public\favicon.ico

[Languages]
Name: "english"; MessagesFile: "compiler:Default.isl"

[Files]
; 1. Copy portable runtime servers (PHP and MariaDB)
Source: "C:\KayanRelease\server\*"; DestDir: "{app}\server"; Flags: ignoreversion recursesubdirs createallsubdirs
; 2. Copy scripts
Source: "scripts\*"; DestDir: "{app}\scripts"; Flags: ignoreversion recursesubdirs createallsubdirs
; 3. Copy full Kayan ERP Laravel application
Source: "C:\KayanRelease\kayan-erp\*"; DestDir: "{app}\kayan-erp"; Flags: ignoreversion recursesubdirs createallsubdirs; Excludes: "node_modules,\.git,\.gemini,\.vscode,storage\logs\*.log"

[Dirs]
Name: "{app}\kayan-erp\storage"
Name: "{app}\kayan-erp\storage\app"
Name: "{app}\kayan-erp\storage\framework"
Name: "{app}\kayan-erp\storage\framework\cache"
Name: "{app}\kayan-erp\storage\framework\sessions"
Name: "{app}\kayan-erp\storage\framework\views"
Name: "{app}\kayan-erp\storage\logs"
Name: "{app}\kayan-erp\bootstrap\cache"
Name: "C:\KayanERP_Backups"

[Icons]
Name: "{autodesktop}\{#MyAppName}"; Filename: "wscript.exe"; Parameters: """{app}\scripts\start.vbs"""; WorkingDir: "{app}"; IconFilename: "{app}\kayan-erp\public\favicon.ico"
Name: "{group}\{#MyAppName}"; Filename: "wscript.exe"; Parameters: """{app}\scripts\start.vbs"""; WorkingDir: "{app}"; IconFilename: "{app}\kayan-erp\public\favicon.ico"
Name: "{group}\Stop Services"; Filename: "wscript.exe"; Parameters: """{app}\scripts\stop.vbs"""; WorkingDir: "{app}"
Name: "{group}\Backup Database"; Filename: "{app}\scripts\backup.bat"; WorkingDir: "{app}"
Name: "{group}\Uninstall {#MyAppName}"; Filename: "{uninstallexe}"

[Run]
; Auto-initialize database & Laravel environment silently on fresh install
Filename: "{app}\server\php\php.exe"; Parameters: "{app}\kayan-erp\artisan key:generate --force"; WorkingDir: "{app}\kayan-erp"; Flags: runhidden
Filename: "{app}\server\php\php.exe"; Parameters: "{app}\kayan-erp\artisan storage:link"; WorkingDir: "{app}\kayan-erp"; Flags: runhidden
Filename: "{app}\server\php\php.exe"; Parameters: "{app}\kayan-erp\artisan migrate --force"; WorkingDir: "{app}\kayan-erp"; Flags: runhidden
Filename: "{app}\server\php\php.exe"; Parameters: "{app}\kayan-erp\artisan config:cache"; WorkingDir: "{app}\kayan-erp"; Flags: runhidden
Filename: "{app}\server\php\php.exe"; Parameters: "{app}\kayan-erp\artisan route:cache"; WorkingDir: "{app}\kayan-erp"; Flags: runhidden
Filename: "{app}\server\php\php.exe"; Parameters: "{app}\kayan-erp\artisan view:cache"; WorkingDir: "{app}\kayan-erp"; Flags: runhidden

; Post-install launch option
Filename: "wscript.exe"; Parameters: """{app}\scripts\start.vbs"""; Description: "Launch {#MyAppName} now"; Flags: nowait postinstall skipifsilent
