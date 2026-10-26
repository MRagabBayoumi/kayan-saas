# دليل رفع نظام كايان ERP على استضافة سحابية (Cloud / Web Hosting)

يوفر هذا الدليل التفاصيل الكاملة لنشر نظام **Kayan ERP** على الإنترنت لتمكين العميل من الوصول إلى النظام من أي مكان بالعالم وعبر الهواتف الذكية ومتابعة الفروع المتعددة.

---

## مقارنة خيارات الاستضافة للعميل:

| الخيار | المميزات | العيوب | التوصية |
| :--- | :--- | :--- | :--- |
| **سيرفر سحابي خاص (VPS)** | تحكم كامل، أمان عالي، أداء فائق وسرعة قصوى، دعم تشغيل المهام الخلفية (Queue) والـ Cron و SSL مجاناً. | يحتاج معرفة بأوامر لينكس الأساسية. | **(موصى به جداً لنظام ERP)** |
| **استضافة مشتركة (cPanel)** | سهلة الإدارة للمبتدئين عبر لوحة تحكم رسومية. | أداء أضعف، قيود على الصلاحيات وتشغيل بيئة Node.js و Shell. | مقبول للأنشطة الصغيرة جداً. |

---

# المسار الأول: النشر على سيرفر سحابي VPS (Ubuntu 22.04 / 24.04 LTS)
*(الخيار القياسي الاحترافي)*

### 1. حجز السيرفر والدومين
- يمكنك حجز VPS من مزودين موثوقين مثل: Hetzner (الأفضل سعراً وأداءً)، DigitalOcean، أو AWS Lightsail.
- المواصفات الموصى بها: **معالج 2 vCPU + 4GB RAM + 40GB NVMe SSD**.
- اختر نظام التشغيل: **Ubuntu 24.04 LTS** (أو Ubuntu 22.04 LTS).
- في لوحة تحكم الدومين، أضف سجل `A Record`:
  - `Host:` `erp` (أو `@` للنطاق الرئيسي).
  - `Points to:` `IP السيرفر الخاص بك`.

### 2. التجهيز التلقائي بضغطة زر
لقد أعددنا لك سكريبت باش متكامل **`vps-setup-ubuntu.sh`** يقوم بتثبيت كل شيء آلياً:
1. اتصل بالسيرفر عبر SSH:
   ```bash
   ssh root@your_server_ip
   ```
2. انسخ ملف `vps-setup-ubuntu.sh` أو شغله مباشرة لتثبيت:
   - Nginx Web Server
   - PHP 8.2 + كافة الإضافات المطلوبة (FPM, MySQL, XML, Mbstring, Curl, Zip, BCMath, GD, Intl)
   - MySQL 8.0 Database Server
   - Node.js 20 LTS + NPM
   - Composer
   - UFW Firewall (تأمين المنافذ 80 و 443 و 22 فقط)

### 3. سحب المشروع من GitHub
```bash
cd /var/www
git clone https://github.com/MRagabBayoumi/kayan-erp-new.git kayan-erp
cd kayan-erp

# نسخ ملف الإعدادات
cp .env.example .env
```

### 4. تعديل ملف `.env` لقيم الإنتاج
```bash
nano .env
```
قم بضبط القيم التالية:
```env
APP_NAME="Kayan ERP"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://erp.yourdomain.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=kayan_erp_prod
DB_USERNAME=kayan_user
DB_PASSWORD=ضع_كلمة_مرور_قوية_جدا_هنا
```

### 5. تثبيت الحزم وبناء الواجهات
```bash
composer install --no-dev --optimize-autoloader
npm install
npm run build
php artisan key:generate
php artisan storage:link
php artisan migrate --force
php artisan optimize
```

### 6. ضبط الصلاحيات لأمان لينكس
```bash
sudo chown -R www-data:www-data /var/www/kayan-erp/storage /var/www/kayan-erp/bootstrap/cache
sudo chmod -R 775 /var/www/kayan-erp/storage /var/www/kayan-erp/bootstrap/cache
```

### 7. إعداد خادم Nginx
1. انسخ ملف الإعدادات **`nginx-site.conf`** إلى مسار Nginx:
   ```bash
   sudo cp deployment/02-hosting-deployment/nginx-site.conf /etc/nginx/sites-available/kayan-erp
   ```
2. عدل اسم الدومين داخل الملف ليتوافق مع دومين العميل.
3. قم بتفعيل الموقع واختبار الإعدادات:
   ```bash
   sudo ln -s /etc/nginx/sites-available/kayan-erp /etc/nginx/sites-enabled/
   sudo nginx -t
   sudo systemctl restart nginx
   ```

### 8. تثبيت شهادة أمان SSL مجانية (HTTPS)
بأمر واحد فقط عبر Let's Encrypt Certbot:
```bash
sudo apt install -y certbot python3-certbot-nginx
sudo certbot --nginx -d erp.yourdomain.com
```

### 9. التحديث والتطوير المستمر (Continuous Deployment)
عند إجراء أي تعديلات جديدة على كود المشروع ورفعها لـ GitHub، يمكنك فقط تشغيل سكريبت التحديث:
```bash
cd /var/www/kayan-erp
bash deployment/02-hosting-deployment/deploy.sh
```

---

# المسار الثاني: النشر على استضافة مشتركة (cPanel Hosting)

إذا كان العميل يمتلك استضافة عادية ويريد رفع المشروع عليها دون حجز VPS:
- راجع الدليل المخصص المرفق: **[`cpanel-deployment-guide.md`](cpanel-deployment-guide.md)**.
- يتضمن الدليل طريقة تنظيم المجلدات، نقل محتويات مجلد `public` إلى `public_html`، وتعديل ملف `index.php` وقواعد البيانات.
