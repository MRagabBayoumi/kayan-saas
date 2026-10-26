# -*- coding: utf-8 -*-
import os
import sys
import time
import socket
import subprocess
import re

# Set console to UTF-8
if sys.platform == 'win32':
    os.system('chcp 65001 >nul')

def is_port_in_use(port):
    with socket.socket(socket.AF_INET, socket.SOCK_STREAM) as s:
        return s.connect_ex(('127.0.0.1', port)) == 0

def main():
    port = 8000
    base_dir = os.path.dirname(os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
    tunnel_exe = os.path.join(base_dir, 'scripts', 'tunnel', 'cloudflared.exe')

    if not os.path.exists(tunnel_exe):
        print("[!] cloudflared.exe not found. Downloading latest version from Cloudflare...")
        try:
            import urllib.request
            urllib.request.urlretrieve("https://github.com/cloudflare/cloudflared/releases/latest/download/cloudflared-windows-amd64.exe", tunnel_exe)
            print("[✓] Download complete!")
        except Exception as e:
            print(f"Error downloading cloudflared.exe: {e}")
            input("Press Enter to exit...")
            return

    print("==========================================================")
    print("       Kayan ERP - Sales Rep Mobile Cloudflare Tunnel     ")
    print("==========================================================")
    print("")

    # Check if local server is running on port 8000
    if not is_port_in_use(port):
        print(f"[!] Server is not running on port {port}. Starting PHP server...")
        php_exe = r"C:\Users\PC\.config\herd-lite\bin\php.exe"
        if not os.path.exists(php_exe):
            php_exe = "php"
        subprocess.Popen([php_exe, "artisan", "serve", f"--port={port}"], cwd=base_dir, creationflags=subprocess.CREATE_NO_WINDOW)
        time.sleep(2)
    else:
        print(f"[OK] Local server is active on port {port}.")

    print("[...] Connecting to Cloudflare secure network...")
    cmd = [tunnel_exe, "tunnel", "--url", f"http://127.0.0.1:{port}"]

    # Run cloudflared and read stderr to extract the URL
    process = subprocess.Popen(
        cmd,
        stdout=subprocess.PIPE,
        stderr=subprocess.PIPE,
        text=True,
        encoding='utf-8',
        errors='replace',
        bufsize=1
    )

    tunnel_url = None
    url_pattern = re.compile(r'https://[a-zA-Z0-9-]+\.trycloudflare\.com')

    # Monitor output for tunnel URL
    for line in process.stderr:
        match = url_pattern.search(line)
        if match:
            tunnel_url = match.group(0)
            break

    if tunnel_url:
        portal_url = f"{tunnel_url}/portal"
        login_url = f"{tunnel_url}/login"

        # Try to copy portal URL to clipboard
        try:
            subprocess.run('clip', input=portal_url.encode('utf-8'), check=False)
            clipboard_note = "(تم نسخ الرابط تلقائياً إلى الحافظة! يمكنك عمل Paste في الواتساب فوراً)"
        except Exception:
            clipboard_note = ""

        os.system('cls' if os.name == 'nt' else 'clear')
        print("==================================================================")
        print("     تم إنشاء النفق الآمن بنجاح وسيرفرك متاح الآن للمناديب!        ")
        print("==================================================================")
        print("")
        print("  رابط تطبيق المناديب الميداني (HTTPS مشفر وآمن):")
        print(f"  --> {portal_url}")
        if clipboard_note:
            print(f"      {clipboard_note}")
        print("")
        print("  رابط صفحة الدخول:")
        print(f"  --> {login_url}")
        print("")
        print("------------------------------------------------------------------")
        print("  تعليمات الاستخدام للمندوب في الشارع:")
        print("  1. ابعت الرابط ده للمندوب في رسالة واتساب.")
        print("  2. المندوب يفتح الرابط من متصفح الموبايل (Chrome أو Safari).")
        print("  3. لتحويله لأيقونة تطبيق على الموبايل:")
        print("     - يضغط على خيارات المتصفح (3 نقط أو زر المشاركة).")
        print("     - يختار (إضافة إلى الشاشة الرئيسية / Add to Home screen).")
        print("  4. يسجل دخوله بحساب المندوب وكلمة المرور.")
        print("------------------------------------------------------------------")
        print("  تنبيه: اترك هذه الشاشة مفتوحة طوال فترة عمل المناديب بالخارج.")
        print("  لإيقاف الاتصال اضغط Ctrl+C أو أغلق هذه النافذة.")
        print("==================================================================")
        print("")

        # Keep running
        try:
            process.wait()
        except KeyboardInterrupt:
            process.terminate()
            print("\nتم إيقاف النفق السحابي بنجاح.")
    else:
        print("[X] تعذر الحصول على الرابط السحابي. يرجى التأكد من اتصال الإنترنت.")
        input("اضغط Enter للإغلاق...")

if __name__ == '__main__':
    main()
