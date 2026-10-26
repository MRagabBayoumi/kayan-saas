#!/usr/bin/env bash
# ========================================================
# Kayan ERP - Fast Zero-Downtime Deployment & Update Script
# يستخدم لتحديث المشروع على سيرفر VPS بنقرة واحدة بعد أي Git Push
# ========================================================

set -e

echo "=== [1/6] وضع النظام في وضع الصيانة المؤقت ==="
php artisan down --render="errors::503" || true

echo "=== [2/6] سحب آخر التحديثات من GitHub ==="
git pull origin main

echo "=== [3/6] تثبيت وتحديث حزم Composer ==="
composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev

echo "=== [4/6] ترحيل تحديثات قاعدة البيانات بشكل آمن ==="
php artisan migrate --force

echo "=== [5/6] بناء وتجميع أصول الواجهات (Vite) وتحديث الكاش ==="
npm install --silent
npm run build

php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "=== [6/6] إعادة تشغيل خدمات PHP-FPM وخروج النظام من الصيانة ==="
sudo systemctl reload php8.2-fpm || true
php artisan up

echo "========================================================"
echo "  تم تحديث ونشر نظام كايان ERP بنجاح تام!"
echo "========================================================"
