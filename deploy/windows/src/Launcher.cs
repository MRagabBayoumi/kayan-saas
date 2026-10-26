using System;
using System.Diagnostics;
using System.IO;
using System.Net.Sockets;
using System.Threading;

namespace KayanERP
{
    static class Program
    {
        [STAThread]
        static void Main()
        {
            try
            {
                string baseDir = AppDomain.CurrentDomain.BaseDirectory.TrimEnd('\\', '/');

                // Determine paths for portable install or project directory
                string phpPath = Path.Combine(baseDir, "server", "php", "php.exe");
                string mysqlPath = Path.Combine(baseDir, "server", "mariadb", "bin", "mysqld.exe");
                string myIniPath = Path.Combine(baseDir, "server", "mariadb", "my.ini");
                string projectPath = Path.Combine(baseDir, "kayan-erp");

                // Fallback if running from within project root or dev environment
                if (!File.Exists(phpPath))
                {
                    string herdPhp = @"C:\Users\PC\.config\herd-lite\bin\php.exe";
                    if (File.Exists(herdPhp))
                    {
                        phpPath = herdPhp;
                    }
                    else
                    {
                        phpPath = "php.exe";
                    }
                    projectPath = baseDir;
                }

                // 1. Check & start MariaDB if present
                if (File.Exists(mysqlPath))
                {
                    Process[] mysqlProcs = Process.GetProcessesByName("mysqld");
                    if (mysqlProcs.Length == 0)
                    {
                        ProcessStartInfo mysqlInfo = new ProcessStartInfo
                        {
                            FileName = mysqlPath,
                            Arguments = "--defaults-file=\"" + myIniPath + "\"",
                            WindowStyle = ProcessWindowStyle.Hidden,
                            CreateNoWindow = true,
                            UseShellExecute = false
                        };
                        try { Process.Start(mysqlInfo); } catch { }
                    }
                }

                // 2. Check if web server is already listening on 127.0.0.1:8000
                bool serverListening = false;
                try
                {
                    using (TcpClient tcp = new TcpClient())
                    {
                        IAsyncResult ar = tcp.BeginConnect("127.0.0.1", 8000, null, null);
                        serverListening = ar.AsyncWaitHandle.WaitOne(400);
                        if (serverListening)
                        {
                            tcp.EndConnect(ar);
                        }
                    }
                }
                catch { }

                // 3. Start PHP Server if not running on 0.0.0.0:8000 for LAN/Wi-Fi access
                if (!serverListening)
                {
                    string publicDir = Path.Combine(projectPath, "public");
                    ProcessStartInfo phpInfo = new ProcessStartInfo
                    {
                        FileName = phpPath,
                        Arguments = "-S 0.0.0.0:8000 -t \"" + publicDir + "\"",
                        WindowStyle = ProcessWindowStyle.Hidden,
                        CreateNoWindow = true,
                        UseShellExecute = false,
                        WorkingDirectory = projectPath
                    };
                    phpInfo.EnvironmentVariables["PHP_CLI_SERVER_WORKERS"] = "6";
                    try { Process.Start(phpInfo); } catch { }
                    Thread.Sleep(1200);
                }

                // 4. Launch Edge in App Mode (Desktop Frameless Window)
                string edgePath = @"C:\Program Files (x86)\Microsoft\Edge\Application\msedge.exe";
                if (!File.Exists(edgePath))
                {
                    edgePath = @"C:\Program Files\Microsoft\Edge\Application\msedge.exe";
                }

                if (File.Exists(edgePath))
                {
                    ProcessStartInfo appInfo = new ProcessStartInfo
                    {
                        FileName = edgePath,
                        Arguments = "--app=http://127.0.0.1:8000 --start-maximized --window-name=\"Kayan ERP\""
                    };
                    Process.Start(appInfo);
                }
                else
                {
                    Process.Start("http://127.0.0.1:8000");
                }
            }
            catch (Exception ex)
            {
                // Silent catch or simple message if critical
            }
        }
    }
}
