# Bare Metal Deployment Guide: Laravel Loan Management System on AWS EC2

This guide covers deploying the application directly on an Ubuntu 22.04 EC2 instance without Docker.

## Prerequisites

- AWS EC2 instance (Ubuntu 22.04 LTS) with SSH access
- At least 2GB RAM recommended
- Port 80 (HTTP) and 22 (SSH) open in Security Groups

## Step 1: Initial Server Setup

SSH into your server:
```bash
ssh -i /path/to/your-key.pem ubuntu@your-server-ip
```

Update system packages:
```bash
sudo apt update && sudo apt upgrade -y
```

## Step 2: Install Required Software

### Install Nginx
```bash
sudo apt install nginx -y
sudo systemctl enable nginx
sudo systemctl start nginx
```

### Install PHP 8.2 and Extensions
```bash
sudo apt install software-properties-common -y
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update

sudo apt install php8.2-fpm php8.2-cli php8.2-common php8.2-mysql \
    php8.2-zip php8.2-gd php8.2-mbstring php8.2-curl php8.2-xml \
    php8.2-bcmath php8.2-intl php8.2-redis -y
```

### Install MySQL
```bash
sudo apt install mysql-server -y
sudo systemctl enable mysql
sudo systemctl start mysql
```

Secure MySQL installation:
```bash
sudo mysql_secure_installation
```
- Set root password
- Remove anonymous users: Yes
- Disallow root login remotely: Yes
- Remove test database: Yes
- Reload privilege tables: Yes

### Install Composer
```bash
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
sudo chmod +x /usr/local/bin/composer
```

### Install Node.js 18
```bash
curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
sudo apt install nodejs -y
```

## Step 3: Create Database

Login to MySQL:
```bash
sudo mysql -u root -p
```

Create database and user:
```sql
CREATE DATABASE loan_system;
CREATE USER 'loan_user'@'localhost' IDENTIFIED BY 'your_secure_password';
GRANT ALL PRIVILEGES ON loan_system.* TO 'loan_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

## Step 4: Deploy Application

### Clone/Upload Your Project
```bash
cd /var/www
sudo git clone https://github.com/chandachewe10/loan-management-system.git
cd loan-management-system
```

Or if uploading manually:
```bash
sudo mkdir -p /var/www/loan-management-system
# Upload your files via SCP/SFTP to this directory
```

### Set Permissions
```bash
sudo chown -R www-data:www-data /var/www/loan-management-system
sudo chmod -R 755 /var/www/loan-management-system
sudo chmod -R 775 /var/www/loan-management-system/storage
sudo chmod -R 775 /var/www/loan-management-system/bootstrap/cache
```

### Configure Environment
```bash
cd /var/www/loan-management-system
cp .env.example .env
nano .env
```

Update the following in `.env`:
```ini
APP_NAME="Loan Management System"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=http://your-server-ip

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=loan_system
DB_USERNAME=loan_user
DB_PASSWORD=your_secure_password

# Set mail configuration if needed
# MAIL_MAILER=smtp
# MAIL_HOST=your-mail-server
# MAIL_PORT=587
```

### Install Dependencies and Setup
```bash
composer install --optimize-autoloader --no-dev
php artisan key:generate
php artisan migrate --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Build frontend assets
npm install
npm run build
```

## Step 5: Configure Nginx

Create Nginx configuration:
```bash
sudo nano /etc/nginx/sites-available/loan-system
```

Add the following configuration:
```nginx
server {
    listen 80;
    server_name your-server-ip;  # or your domain name
    root /var/www/loan-management-system/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

Enable the site and restart Nginx:
```bash
sudo ln -s /etc/nginx/sites-available/loan-system /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl restart nginx
```

## Step 6: Configure PHP-FPM

Edit PHP-FPM configuration:
```bash
sudo nano /etc/php/8.2/fpm/php.ini
```

Update these settings:
```ini
upload_max_filesize = 100M
post_max_size = 100M
memory_limit = 512M
max_execution_time = 300
```

Restart PHP-FPM:
```bash
sudo systemctl restart php8.2-fpm
```

## Step 7: Create Admin User & Setup

Create your first admin user:
```bash
cd /var/www/loan-management-system
php artisan make:filament-user
```

Manually verify the email:
```bash
php artisan tinker --execute="App\Models\User::where('email', 'your-email@example.com')->update(['email_verified_at' => now()]);"
```

Clear all caches:
```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
```

## Step 8: Set Up Cron Jobs (Optional but Recommended)

Edit crontab:
```bash
sudo crontab -e
```

Add Laravel scheduler:
```cron
* * * * * cd /var/www/loan-management-system && php artisan schedule:run >> /dev/null 2>&1
```

## Step 9: Verify Deployment

Visit `http://your-server-ip` in your browser. You should be redirected to `/admin/login`.

## Troubleshooting

### Permission Errors
```bash
sudo chown -R www-data:www-data /var/www/loan-management-system
sudo chmod -R 755 /var/www/loan-management-system
sudo chmod -R 775 /var/www/loan-management-system/storage
sudo chmod -R 775 /var/www/loan-management-system/bootstrap/cache
```

### Check Logs
```bash
# Nginx error log
sudo tail -f /var/log/nginx/error.log

# Laravel log
sudo tail -f /var/www/loan-management-system/storage/logs/laravel.log

# PHP-FPM log
sudo tail -f /var/log/php8.2-fpm.log
```

### Database Connection Issues
- Verify MySQL is running: `sudo systemctl status mysql`
- Test connection: `mysql -u loan_user -p loan_system`
- Check `.env` database credentials

### Subscription Error After Login
```bash
cd /var/www/loan-management-system
php artisan config:clear && php artisan cache:clear
```

## Security Hardening (Production)

### Enable Firewall
```bash
sudo ufw allow 22/tcp
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw enable
```

### Set Up SSL with Let's Encrypt (Recommended)
```bash
sudo apt install certbot python3-certbot-nginx -y
sudo certbot --nginx -d your-domain.com
```

### Disable Directory Listing
Already handled in the Nginx config above.

### Regular Updates
```bash
# Create a script for regular updates
sudo nano /usr/local/bin/update-loan-system.sh
```

Add:
```bash
#!/bin/bash
cd /var/www/loan-management-system
git pull
composer install --optimize-autoloader --no-dev
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
npm install
npm run build
sudo systemctl restart php8.2-fpm
```

Make executable:
```bash
sudo chmod +x /usr/local/bin/update-loan-system.sh
```

## Maintenance

### Backup Database
```bash
mysqldump -u loan_user -p loan_system > backup_$(date +%Y%m%d).sql
```

### Update Application
```bash
cd /var/www/loan-management-system
sudo -u www-data git pull
sudo -u www-data composer install --optimize-autoloader --no-dev
sudo -u www-data php artisan migrate --force
sudo -u www-data php artisan config:cache
sudo -u www-data npm install
sudo -u www-data npm run build
sudo systemctl restart php8.2-fpm
```

## Performance Optimization

### Enable OPcache
Edit `/etc/php/8.2/fpm/php.ini`:
```ini
opcache.enable=1
opcache.memory_consumption=128
opcache.interned_strings_buffer=8
opcache.max_accelerated_files=4000
opcache.revalidate_freq=60
```

### Configure Redis (Optional)
```bash
sudo apt install redis-server -y
sudo systemctl enable redis-server
```

Update `.env`:
```ini
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
```

Restart services:
```bash
php artisan config:cache
sudo systemctl restart php8.2-fpm
```
