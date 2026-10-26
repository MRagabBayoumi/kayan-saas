#!/usr/bin/env bash
# ========================================================
# Kayan ERP - Automated VPS Setup Script for Ubuntu 22.04 / 24.04 LTS
# ========================================================

set -e

echo "=== [1/7] تحديث الحزم وترقية النظام ==="
sudo apt update && sudo apt upgrade -y
sudo apt install -y software-properties-common curl git unzip zip ufw

echo "=== [2/7] تثبيت مستودع PHP 8.2 والحزم الأساسية ==="
sudo add-apt-repository -y ppa:ondrej/php
sudo apt update
sudo apt install -y php8.2 php8.2-fpm php8.2-mysql php8.2-xml php8.2-mbstring \
    php8.2-curl php8.2-zip php8.2-bcmath php8.2-gd php8.2-intl php8.2-readline

echo "=== [3/7] تثبيت خادم Nginx ==="
sudo apt install -y nginx

echo "=== [4/7] تثبيت خادم قواعد البيانات MySQL 8.0 ==="
sudo apt install -y mysql-server

echo "=== [5/7] تثبيت Composer ==="
if ! command -v composer &> /dev/null; then
    curl -sS https://getcomposer.org/installer | php
    sudo mv composer.phar /usr/local/bin/composer
    sudo chmod +x /usr/local/bin/composer
fi

echo "=== [6/7] تثبيت Node.js 20 LTS & NPM ==="
if ! command -v node &> /dev/null; then
    curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
    sudo apt install -y nodejs
fi

echo "=== [7/7] ضبط جدار الحماية (UFW) ==="
sudo ufw allow OpenSSH
sudo ufw allow 'Nginx Full'
sudo ufw --force enable

echo "========================================================"
echo "  تم اكتمال تجهيز بيئة السيرفر بنجاح تام!"
echo "  الإصدارات المثبتة:"
php -v | head -n 1
nginx -v
mysql --version
composer --version
node -v
npm -v
echo "========================================================"
