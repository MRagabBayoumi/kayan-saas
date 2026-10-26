# دليل تشغيل نظام كايان ERP على الشبكة المحلية (Local Network / LAN)

هذا الدليل مخصص لتثبيت وتشغيل النظام على جهاز سيرفر محلي داخل المحل أو المعرض أو المخزن، وربط جميع أجهزة الكاشير والإدارة والهواتف المحمولة به بدون الحاجة إلى اتصال بالإنترنت.

---

## 1. المتطلبات الأساسية على جهاز السيرفر (Server Requirements)

تأكد من تثبيت البرامج التالية على الجهاز الرئيسي الذي سيعمل كسيرفر:
1. **حزمة XAMPP** (أو أي خادم محلي يدعم PHP 8.2 فما فوق و MySQL 8.0).
   - رابط التحميل: [Apache Friends XAMPP](https://www.apachefriends.org/)
2. **Composer** (مدير حزم PHP).
   - رابط التحميل: [getcomposer.org](https://getcomposer.org/)
3. **Node.js LTS** (الجيل 18 أو 20).
   - رابط التحميل: [nodejs.org](https://nodejs.org/)
4. **Git for Windows**.
   - رابط التحميل: [git-scm.com](https://git-scm.com/)

---

## 2. تنزيل المشروع من GitHub (Clone)

افتح موجه الأوامر (CMD أو PowerShell) داخل مجلد الخادم `C:\xampp\htdocs\` ونفذ:

```bash
cd C:\xampp\htdocs
git clone https://github.com/MRagabBayoumi/kayan-erp-new.git kayan-erp
cd kayan-erp
```

---

## 3. التثبيت السريع عبر ملف التثبيت التلقائي (Recommended)

لقد وفرنا لك ملف تثبيت آلي يقوم بجميع الخطوات بنقرة واحدة:
- اضغط مرتين على ملف **`install.bat`** الموجود داخل هذا المجلد.
- سيقوم السكريبت تلقائياً بـ:
  1. إنشاء ملف الإعدادات `.env` من `.env.example`.
  2. تثبيت مكتبات الـ PHP عبر Composer.
  3. تثبيت وبناء أصول الواجهة عبر `npm run build`.
  4. توليد مفتاح التشفير `APP_KEY`.
  5. ربط مجلد التخزين `storage:link`.
  6. ترحيل قاعدة البيانات وإعداد التخزين المؤقت للإنتاج.

---

## 4. التثبيت اليدوي (خطوة بخطوة)

إذا أردت تنفيذ الأوامر يدوياً بنفسك:

```bash
# 1. نسخ ملف الإعدادات
cp .env.example .env

# 2. تثبيت الحزم
composer install --no-dev --optimize-autoloader
npm install
npm run build

# 3. توليد المفتاح
php artisan key:generate

# 4. ربط وسائط التخزين (الصور والشعار)
php artisan storage:link

# 5. ترحيل الجداول في قاعدة البيانات
php artisan migrate --force

# 6. تسريع أداء النظام للإنتاج
php artisan optimize
```

---

## 5. ضبط إعدادات قاعدة البيانات في ملف `.env`

افتح ملف `.env` وعدل الأسطر التالية وفقاً لإعدادات XAMPP لديك:

```env
APP_NAME="Kayan ERP"
APP_ENV=production
APP_DEBUG=false
APP_URL=http://192.168.1.100

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=kayan_erp
DB_USERNAME=root
DB_PASSWORD=
```

> **ملاحظة:** تأكد من إنشاء قاعدة بيانات فارغة باسم `kayan_erp` في phpMyAdmin قبل تشغيل أمر التهجير `php artisan migrate`.

---

## 6. تثبيت عنوان الـ IP لجهاز السيرفر (Static IP)

لكي لا يتغير عنوان السيرفر عند إعادة تشغيل الراوتر:
1. من لوحة التحكم في ويندوز، افتح `Network and Sharing Center`.
2. اضغط على كارت الشبكة (`Ethernet` أو `Wi-Fi`) واختر `Properties`.
3. اختر `Internet Protocol Version 4 (TCP/IPv4)` واضغط `Properties`.
4. اختر `Use the following IP address` وضع:
   - **IP address:** `192.168.1.100` (أو أي رقم تختاره خارج نطاق توزيع الراوتر العشوائي).
   - **Subnet mask:** `255.255.255.0`
   - **Default gateway:** `192.168.1.1` (عنوان صفحة الراوتر).
   - **Preferred DNS:** `8.8.8.8`

---

## 7. ضبط خادم Apache ليعمل السيرفر بالـ IP مباشرة

لكي يفتح النظام بمجرد كتابة `http://192.168.1.100` دون الحاجة لكتابة `/public`:
1. افتح الملف: `C:\xampp\apache\conf\extra\httpd-vhosts.conf`
2. أضف في نهاية الملف الكود الموجود في ملف **`httpd-vhosts-example.conf`**:
   ```apache
   <VirtualHost *:80>
       DocumentRoot "C:/xampp/htdocs/kayan-erp/public"
       ServerName 192.168.1.100
       <Directory "C:/xampp/htdocs/kayan-erp/public">
           Options Indexes FollowSymLinks
           AllowOverride All
           Require all granted
       </Directory>
   </VirtualHost>
   ```
3. افتح الملف `C:\xampp\apache\conf\httpd.conf` وتأكد أن السطر التالي غير معطل (لا توجد أمامه علامة `#`):
   ```apache
   Include conf/extra/httpd-vhosts.conf
   ```
4. أعد تشغيل خادم Apache من لوحة تحكم XAMPP.

---

## 8. فتح المنفذ في جدار الحماية (Windows Firewall)

لكي يسمح ويندوز للأجهزة الأخرى بالاتصال بالسيرفر:
- اضغط بزر الفأرة الأيمن على ملف **`setup-firewall.bat`** واختر **"تشغيل كمسؤول" (Run as Administrator)**.
- أو افتح CMD كمسؤول واكتب الأمر التالي:
  ```cmd
  netsh advfirewall firewall add rule name="Kayan ERP Local LAN" dir=in action=allow protocol=TCP localport=80,8000
  ```

---

## 9. دخول أجهزة الكاشير والإدارة على الشبكة المحلية

من أي جهاز كمبيوتر، لابتوب، جهاز لوحي (تابلت)، أو هاتف محمول متصل بنفس شبكة الراوتر (سواء كابل أو واي فاي):
1. افتح متصفح Google Chrome أو Edge.
2. اكتب في شريط العنوان:
   ```text
   http://192.168.1.100
   ```
3. ستظهر شاشة تسجيل الدخول لنظام كايان ERP مباشرة.

### نصيحة لتجربة كاشير ممتازة (Kiosk Mode):
يمكنك إنشاء اختصار على سطح مكتب جهاز الكاشير ليفتح شاشة البيع بملء الشاشة تلقائياً:
- كليك يمين على سطح المكتب -> `New` -> `Shortcut`.
- اكتب في المسار:
  ```text
  "C:\Program Files\Google\Chrome\Application\chrome.exe" --kiosk http://192.168.1.100/sales/create
  ```

---

## 10. النسخ الاحتياطي التلقائي اليومي (Daily Backup)

لحماية بيانات العميل من أي عطل مفاجئ في الهارد ديسك:
1. وفر فلاش ميموري أو قرصاً صلباً خارجياً (مثلاً Drive `D:\`).
2. افتح ملف **`backup-database.bat`** المرفق وعدل مسار الفلاشة/الهارد ومسار MySQL.
3. افتح أداة **Windows Task Scheduler**:
   - أنشئ مهمة جديدة `Create Basic Task`.
   - اجعلها تعمل يومياً الساعة `11:59 PM`.
   - حدد الإجراء لتشغيل ملف `backup-database.bat`.
   - بهذه الطريقة سيكون لدى العميل نسخة احتياطية يومية مؤرخة تلقائياً.
