# دليل نشر نظام كايان ERP على الاستضافة المشتركة (cPanel Deployment)

إذا كان العميل يمتلك استضافة ويب تقليدية بلوحة تحكم cPanel، اتبع هذه الخطوات المعمارية لضمان أمان ملفات Laravel وعمل الموقع بسلاسة.

---

## 1. المعمارية الصحيحة للملفات (Security Architecture)

> **تحذير أمني هام:** إياك ورفع مشروع Laravel كاملاً داخل مجلد `public_html`، لأن ذلك قد يعرض ملف `.env` وقاعدة البيانات للاختراق.

الهيكلية الموصى بها في مدير الملفات (File Manager):

```text
/home/username/
├── kayan-erp/            <-- هنا توضع جميع ملفات ومجلدات المشروع الأساسية
│   ├── app/
│   ├── bootstrap/
│   ├── config/
│   ├── database/
│   ├── resources/
│   ├── routes/
│   ├── storage/
│   ├── vendor/
│   └── .env
│
└── public_html/          <-- هنا توضع محتويات مجلد public فقط!
    ├── build/            (ملفات CSS و JS الناتجة عن npm run build)
    ├── storage/          (رابط مجلد الملفات المرفوعة)
    ├── .htaccess
    ├── favicon.svg
    ├── favicon.ico
    └── index.php
```

---

## 2. الخطوات العملية خطوة بخطوة:

### الخطوة 1: تجهيز الملفات محلياً قبل الرفع
على جهاز المطور الخاص بك:
1. قم ببناء أصول الواجهة وتجهيز مجلد vendor:
   ```bash
   composer install --no-dev --optimize-autoloader
   npm run build
   ```
2. اضغط جميع ملفات المشروع في ملف `kayan-erp.zip` (باستثناء مجلد `node_modules` ومجلد `.git` لتوفير الحجم).

### الخطوة 2: رفع الملفات عبر cPanel File Manager
1. ادخل إلى لوحة cPanel وافتح **File Manager**.
2. في المجلد الرئيسي لحسابك `/home/username/` (خارج `public_html`):
   - أنشئ مجلداً جديداً باسم `kayan-erp`.
   - ارفع الملف المضغوط `kayan-erp.zip` واستخرجه داخل `kayan-erp`.
3. ادخل لمجلد `/home/username/kayan-erp/public/`:
   - حدد كافة الملفات والمجلدات الموجودة بداخله (بما فيها مجلد `build` وملف `.htaccess` وملف `index.php`).
   - انقلها (Move) إلى المجلد الرئيسي للموقع: `/public_html/`.

### الخطوة 3: تعديل مسارات الربط في `public_html/index.php`
افتح الملف `/public_html/index.php` للتعديل واضبط المسارين ليشيران للمجلد الرئيسي:

```php
// ابحث عن هذين السطرين:
// require __DIR__.'/../vendor/autoload.php';
// $app = require_once __DIR__.'/../bootstrap/app.php';

// واستبدلهما بالمسار الحقيقي لمجلد kayan-erp:
require __DIR__.'/../kayan-erp/vendor/autoload.php';
$app = require_once __DIR__.'/../kayan-erp/bootstrap/app.php';
```

### الخطوة 4: إنشاء قاعدة البيانات في cPanel
1. من لوحة cPanel، افتح **MySQL Database Wizard**.
2. أنشئ قاعدة بيانات جديدة، مثلاً: `username_kayan`.
3. أنشئ مستخدم جديد وكلمة مرور قوية، مثلاً: `username_erpuser`.
4. امنح المستخدم **كافة الصلاحيات (ALL PRIVILEGES)** على قاعدة البيانات.

### الخطوة 5: ضبط ملف الإعدادات `.env`
1. داخل `/home/username/kayan-erp/`، اظهر الملفات المخفية (Show Hidden Files).
2. أنشئ أو عدل ملف `.env`:
   ```env
   APP_NAME="Kayan ERP"
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://your-domain.com

   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=username_kayan
   DB_USERNAME=username_erpuser
   DB_PASSWORD=your_secure_password
   ```

### الخطوة 6: ترحيل جداول قاعدة البيانات
- إذا كان لديك وصول **SSH (Terminal)** في cPanel:
  ```bash
  cd /home/username/kayan-erp
  php artisan migrate --force
  php artisan storage:link
  php artisan optimize
  ```
- إذا لم يتوفر Terminal:
  - يمكنك تصدير قاعدة البيانات المحلية من جهازك عبر phpMyAdmin كملف `.sql`.
  - استيراد الملف مباشرة عبر **phpMyAdmin** الموجود في لوحة cPanel.

### الخطوة 7: ربط مجلد التخزين (Storage Symlink)
إذا لم يتوفر Terminal لعمل `php artisan storage:link`:
أنشئ ملفاً باسم `link.php` داخل `public_html` وضع فيه:
```php
<?php
symlink('/home/username/kayan-erp/storage/app/public', '/home/username/public_html/storage');
echo 'Storage Symlink Created Successfully!';
```
ثم افتح من المتصفح: `https://your-domain.com/link.php` وبعد النجاح قم بحذف ملف `link.php` فوراً.
