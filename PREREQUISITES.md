# Prerequisites & System Requirements

This document covers all the system requirements and prerequisites needed to run the Git Webhook Manager with Virtual Host management features.

## Table of Contents

- [System Requirements](#system-requirements)
- [Required Software](#required-software)
- [Installation Guide](#installation-guide)
  - [Database Installation](#database-installation)
  - [Nginx Installation](#nginx-installation)
  - [PHP Installation](#php-installation)
  - [Node.js Installation](#nodejs-installation)
  - [Redis Installation](#redis-installation)
  - [Certbot Installation](#certbot-installation)
- [Configuration](#configuration)
  - [Nginx Configuration](#nginx-configuration)
  - [PHP-FPM Configuration](#php-fpm-configuration)
  - [Sudoers Configuration](#sudoers-configuration)
  - [Directory Permissions](#directory-permissions)
- [Queue Worker Setup](#queue-worker-setup)
- [Verification](#verification)

---

## System Requirements

- **Operating System**: Ubuntu 20.04 LTS or later (or Debian-based Linux)
- **RAM**: Minimum 2GB, Recommended 4GB+
- **Disk Space**: Minimum 10GB free space
- **User Access**: Root or sudo access required for initial setup

---

## Required Software

| Software | Version | Purpose |
|----------|---------|---------|
| MySQL/PostgreSQL | MySQL 8.0+ or PostgreSQL 12+ | Database server |
| Nginx | 1.18+ | Web server and reverse proxy |
| PHP | 7.4, 8.0, 8.1, 8.2, 8.3, 8.4 | Multiple PHP versions for virtual hosts |
| PHP-FPM | Same as PHP versions | FastCGI Process Manager |
| Node.js | 16.x, 18.x, 20.x, 21.x | Multiple Node versions for applications |
| Redis | 6.0+ | Queue backend and caching |
| Certbot | 1.0+ | Let's Encrypt SSL certificate management |
| Composer | 2.x | PHP dependency management |
| NPM/Yarn | Latest | Node.js package management |

---

## Installation Guide

### Database Installation

Choose either MySQL or PostgreSQL as your database server.

#### MySQL Installation (Recommended)

```bash
# Install MySQL Server
sudo apt install mysql-server -y

# Start and enable MySQL
sudo systemctl start mysql
sudo systemctl enable mysql

# Secure installation (recommended)
sudo mysql_secure_installation
```

**Create Database and User:**

```bash
# Login to MySQL
sudo mysql

# Create database
CREATE DATABASE git_webhook CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

# Create user with password
CREATE USER 'git_webhook'@'localhost' IDENTIFIED BY 'your_secure_password';

# Grant privileges
GRANT ALL PRIVILEGES ON git_webhook.* TO 'git_webhook'@'localhost';

# Flush privileges
FLUSH PRIVILEGES;

# Exit
EXIT;
```

#### PostgreSQL Installation (Alternative)

```bash
# Install PostgreSQL
sudo apt install postgresql postgresql-contrib -y

# Start and enable PostgreSQL
sudo systemctl start postgresql
sudo systemctl enable postgresql
```

**Create Database and User:**

```bash
# Switch to postgres user
sudo -u postgres psql

# Create database
CREATE DATABASE git_webhook;

# Create user with password
CREATE USER git_webhook WITH PASSWORD 'your_secure_password';

# Grant privileges
GRANT ALL PRIVILEGES ON DATABASE git_webhook TO git_webhook;

# Exit
\q
```

**Database Permissions Required:**
- `SELECT`, `INSERT`, `UPDATE`, `DELETE` - For basic CRUD operations
- `CREATE`, `ALTER`, `DROP` - For migrations
- `INDEX` - For database optimization
- `REFERENCES` - For foreign key constraints

### Nginx Installation

```bash
# Update package list
sudo apt update

# Install Nginx
sudo apt install nginx -y

# Start and enable Nginx
sudo systemctl start nginx
sudo systemctl enable nginx

# Verify installation
nginx -v

# Check status
sudo systemctl status nginx
```

### PHP Installation

Install multiple PHP versions for flexibility:

```bash
# Add PHP repository
sudo apt install software-properties-common -y
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update

# Install PHP 7.4
sudo apt install php7.4 php7.4-fpm php7.4-cli php7.4-common php7.4-mysql \
    php7.4-mbstring php7.4-xml php7.4-curl php7.4-zip php7.4-gd \
    php7.4-bcmath php7.4-json php7.4-redis -y

# Install PHP 8.0
sudo apt install php8.0 php8.0-fpm php8.0-cli php8.0-common php8.0-mysql \
    php8.0-mbstring php8.0-xml php8.0-curl php8.0-zip php8.0-gd \
    php8.0-bcmath php8.0-redis -y

# Install PHP 8.1
sudo apt install php8.1 php8.1-fpm php8.1-cli php8.1-common php8.1-mysql \
    php8.1-mbstring php8.1-xml php8.1-curl php8.1-zip php8.1-gd \
    php8.1-bcmath php8.1-redis -y

# Install PHP 8.2
sudo apt install php8.2 php8.2-fpm php8.2-cli php8.2-common php8.2-mysql \
    php8.2-mbstring php8.2-xml php8.2-curl php8.2-zip php8.2-gd \
    php8.2-bcmath php8.2-redis -y

# Install PHP 8.3
sudo apt install php8.3 php8.3-fpm php8.3-cli php8.3-common php8.3-mysql \
    php8.3-mbstring php8.3-xml php8.3-curl php8.3-zip php8.3-gd \
    php8.3-bcmath php8.3-redis -y

# Start and enable PHP-FPM services
sudo systemctl start php7.4-fpm php8.0-fpm php8.1-fpm php8.2-fpm php8.3-fpm
sudo systemctl enable php7.4-fpm php8.0-fpm php8.1-fpm php8.2-fpm php8.3-fpm

# Verify installations
php7.4 -v
php8.0 -v
php8.1 -v
php8.2 -v
php8.3 -v
```

### Node.js Installation

Install multiple Node.js versions using NVM (Node Version Manager):

```bash
# Install NVM
curl -o- https://raw.githubusercontent.com/nvm-sh/nvm/v0.39.0/install.sh | bash

# Reload shell configuration
source ~/.bashrc

# Install Node.js versions
nvm install 16
nvm install 18
nvm install 20
nvm install 21

# Set default version
nvm alias default 20

# Verify installations
nvm list

# Install PM2 globally for process management
npm install -g pm2

# Setup PM2 to start on system boot
pm2 startup

# Follow the command output instructions to enable PM2 on boot
```

### PM2 Configuration (Node.js Process Manager)

PM2 is used to manage Node.js applications. The system automatically generates PM2 ecosystem configuration files.

**PM2 Log Directory:**

```bash
# Create PM2 log directory
sudo mkdir -p /var/log/pm2
sudo chown -R www-data:www-data /var/log/pm2
sudo chmod -R 755 /var/log/pm2
```

**PM2 Basic Commands:**

```bash
# Start an application
pm2 start /etc/pm2/ecosystem.app-name.config.js

# List all running applications
pm2 list

# Stop an application
pm2 stop app-name

# Restart an application
pm2 restart app-name

# View logs
pm2 logs app-name

# Monitor applications
pm2 monit

# Save PM2 process list
pm2 save

# Resurrect saved processes after reboot
pm2 resurrect
```

**Generated Ecosystem Files:**

The system automatically creates PM2 ecosystem configuration files at:
- **Production**: `/etc/pm2/ecosystem.{domain}.config.js`
- **Local/Dev**: `storage/server/pm2/ecosystem.{domain}.config.js`

These files include:
- Node.js version configuration
- Environment variables (PORT, NODE_ENV)
- Cluster mode settings
- Auto-restart configuration
- Log file paths
- Memory limits

### Redis Installation

```bash
# Install Redis
sudo apt install redis-server -y

# Configure Redis to start on boot
sudo systemctl enable redis-server

# Start Redis
sudo systemctl start redis-server

# Test Redis
redis-cli ping
# Should return: PONG

# Check status
sudo systemctl status redis-server
```

### Certbot Installation

```bash
# Install Certbot and Nginx plugin
sudo apt install certbot python3-certbot-nginx -y

# Verify installation
certbot --version
```

---

## Configuration

### Nginx Configuration

Ensure the sites-available and sites-enabled directories exist:

```bash
# Create directories if they don't exist
sudo mkdir -p /etc/nginx/sites-available
sudo mkdir -p /etc/nginx/sites-enabled

# Verify Nginx main configuration includes sites-enabled
sudo nano /etc/nginx/nginx.conf
```

Ensure this line exists in the `http` block:

```nginx
include /etc/nginx/sites-enabled/*;
```

#### Nginx Security Hardening (Global)

Apply these security settings to the main Nginx configuration:

```bash
sudo nano /etc/nginx/nginx.conf
```

Add or verify these settings in the `http` block:

```nginx
http {
    # Hide Nginx version
    server_tokens off;
    
    # Security headers (global defaults)
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    
    # Buffer size limits (prevent buffer overflow attacks)
    client_body_buffer_size 1K;
    client_header_buffer_size 1k;
    client_max_body_size 100M;
    large_client_header_buffers 2 1k;
    
    # Timeouts
    client_body_timeout 10;
    client_header_timeout 10;
    keepalive_timeout 15;
    send_timeout 10;
    
    # Limit connections
    limit_conn_zone $binary_remote_addr zone=addr:10m;
    limit_conn addr 10;
    
    # Gzip compression
    gzip on;
    gzip_vary on;
    gzip_proxied any;
    gzip_comp_level 6;
    gzip_types text/plain text/css text/xml text/javascript application/json application/javascript application/xml+rss application/rss+xml font/truetype font/opentype application/vnd.ms-fontobject image/svg+xml;
    
    # Your existing configuration...
    include /etc/nginx/sites-enabled/*;
}
```

**Test and reload:**

```bash
sudo nginx -t
sudo systemctl reload nginx
```

### PHP-FPM Configuration

Configure PHP-FPM to listen on Unix sockets:

```bash
# PHP 7.4
sudo nano /etc/php/7.4/fpm/pool.d/www.conf

# PHP 8.0
sudo nano /etc/php/8.0/fpm/pool.d/www.conf

# PHP 8.1
sudo nano /etc/php/8.1/fpm/pool.d/www.conf

# PHP 8.2
sudo nano /etc/php/8.2/fpm/pool.d/www.conf

# PHP 8.3
sudo nano /etc/php/8.3/fpm/pool.d/www.conf
```

Verify these settings in each `www.conf`:

```ini
; Unix user/group of processes
user = www-data
group = www-data

; The address on which to accept FastCGI requests
listen = /var/run/php/phpX.X-fpm.sock

; Set permissions for unix socket
listen.owner = www-data
listen.group = www-data
listen.mode = 0660
```

Restart PHP-FPM services after changes:

```bash
sudo systemctl restart php7.4-fpm php8.0-fpm php8.1-fpm php8.2-fpm php8.3-fpm
```

#### Web Server User Configuration

The application auto-detects the web server user based on your environment:

- **Linux**: Uses `www-data` (default)
- **macOS/BSD**: Uses `www`, `_www`, or current user
- **Custom**: Set via environment variables

To override auto-detection, add to your `.env` file:

```bash
# Custom web server user (optional)
WEB_SERVER_USER=www
WEB_SERVER_GROUP=www
```

**Common web server users by OS**:

| OS | User | Group |
|---|---|---|
| Ubuntu/Debian | `www-data` | `www-data` |
| macOS | `_www` or `www` | `_www` or `www` |
| CentOS/RHEL | `nginx` or `apache` | `nginx` or `apache` |

**Note**: After changing the web server user, redeploy all websites to regenerate PHP-FPM pool configurations with the correct user.

### Sudoers Configuration

**CRITICAL**: The web server user needs sudo privileges to manage Nginx configurations.

Create a sudoers file for the application:

```bash
sudo visudo -f /etc/sudoers.d/git-webhook-manager
```

Add these lines (replace `www-data` with your web server user if different):

```bash
# Git Webhook Manager - Nginx Management
www-data ALL=(ALL) NOPASSWD: /usr/sbin/nginx -t
www-data ALL=(ALL) NOPASSWD: /bin/systemctl reload nginx
www-data ALL=(ALL) NOPASSWD: /bin/systemctl restart nginx

# Certbot - SSL Certificate Management
www-data ALL=(ALL) NOPASSWD: /usr/bin/certbot --nginx -d * --non-interactive --agree-tos --email *
www-data ALL=(ALL) NOPASSWD: /usr/bin/certbot renew *
www-data ALL=(ALL) NOPASSWD: /usr/bin/certbot certificates

# PHP-FPM Pool Management
www-data ALL=(ALL) NOPASSWD: /usr/sbin/php-fpm* -t
www-data ALL=(ALL) NOPASSWD: /bin/systemctl reload php*-fpm
www-data ALL=(ALL) NOPASSWD: /bin/systemctl restart php*-fpm
www-data ALL=(ALL) NOPASSWD: /bin/mkdir -p /var/log/php*-fpm*
www-data ALL=(ALL) NOPASSWD: /bin/chown www-data:www-data /var/log/php*-fpm*
www-data ALL=(ALL) NOPASSWD: /bin/cp /tmp/* /etc/php/*/fpm/pool.d/*
www-data ALL=(ALL) NOPASSWD: /bin/chmod 644 /etc/php/*/fpm/pool.d/*
www-data ALL=(ALL) NOPASSWD: /bin/rm -f /etc/php/*/fpm/pool.d/*

# File Management - Nginx Config Files
www-data ALL=(ALL) NOPASSWD: /bin/cp /tmp/* /etc/nginx/sites-available/*
www-data ALL=(ALL) NOPASSWD: /bin/chmod 644 /etc/nginx/sites-available/*
www-data ALL=(ALL) NOPASSWD: /bin/ln -sf /etc/nginx/sites-available/* /etc/nginx/sites-enabled/*
www-data ALL=(ALL) NOPASSWD: /bin/rm -f /etc/nginx/sites-available/*
www-data ALL=(ALL) NOPASSWD: /bin/rm -f /etc/nginx/sites-enabled/*

# Webroot Directory Management
www-data ALL=(ALL) NOPASSWD: /bin/mkdir -p /var/www/*
www-data ALL=(ALL) NOPASSWD: /bin/chown -R www-data:www-data /var/www/*
www-data ALL=(ALL) NOPASSWD: /bin/chmod -R 755 /var/www/*

# PM2 Configuration Management (Node.js)
www-data ALL=(ALL) NOPASSWD: /bin/mkdir -p /etc/pm2
www-data ALL=(ALL) NOPASSWD: /bin/chmod 755 /etc/pm2
www-data ALL=(ALL) NOPASSWD: /bin/cp /tmp/* /etc/pm2/*
www-data ALL=(ALL) NOPASSWD: /bin/chmod 644 /etc/pm2/*
www-data ALL=(ALL) NOPASSWD: /bin/rm -f /etc/pm2/*

# PM2 Process Control (Node.js)
www-data ALL=(ALL) NOPASSWD: /usr/bin/pm2 start *
www-data ALL=(ALL) NOPASSWD: /usr/bin/pm2 stop *
www-data ALL=(ALL) NOPASSWD: /usr/bin/pm2 restart *
www-data ALL=(ALL) NOPASSWD: /usr/bin/pm2 save
www-data ALL=(ALL) NOPASSWD: /usr/bin/pm2 jlist

# Git Webhook Deployments - Run as deployment users
www-data ALL=(ALL) NOPASSWD: /usr/bin/git
www-data ALL=(ALL) NOPASSWD: /bin/bash
```

Set proper permissions:

```bash
sudo chmod 0440 /etc/sudoers.d/git-webhook-manager
```

**Security Note**: These permissions allow the web server to:
1. Manage Nginx configurations for virtual hosts
2. Request and renew SSL certificates via Let's Encrypt (certbot)
3. Manage PHP-FPM pools for PHP projects
4. Create and manage webroot directories in `/var/www/`
5. Manage PM2 ecosystem configurations for Node.js projects
6. Control PM2 processes (start, stop, restart Node.js applications)
7. Execute git deployments as specified deployment users
8. Run deployment scripts (pre/post deploy hooks)

Ensure your application has proper authentication and authorization to prevent unauthorized access.

#### Deployment Users Setup

For the Git Webhook deployment feature, you need to create system users for running deployments:

```bash
# Create deployment users (example)
sudo useradd -m -s /bin/bash deploy
sudo useradd -m -s /bin/bash nodeapp
sudo useradd -m -s /bin/bash phpapp

# Grant www-data permission to sudo as these users
# Already covered in the sudoers config above
```

**Important**: 
- Each deployment can run as a different system user for isolation
- The web server (`www-data`) uses `sudo -u <deploy_user>` to run git commands
- Deployment users need read/write access to their project directories
- This provides security isolation between different projects

**For Node.js Projects with PM2**:

Deployment users need direct PM2 access (without sudo). Install PM2 globally or ensure it's in the user's PATH:

```bash
# Option 1: Install PM2 globally (recommended)
sudo npm install -g pm2

# Option 2: If deploy user needs to run PM2 commands with sudo
# Add to /etc/sudoers.d/git-webhook-manager:
deploy ALL=(ALL) NOPASSWD: /usr/bin/pm2 *
nodeapp ALL=(ALL) NOPASSWD: /usr/bin/pm2 *
# Replace with your actual deploy user names
```

**Post-Deploy Script Examples**:

```bash
# No sudo needed for PM2
npm install --production
pm2 restart app-name --update-env || pm2 start /etc/pm2/ecosystem.app-name.config.js
pm2 save
```

### Directory Permissions

Set up proper directory permissions:

```bash
# Create website root directory
sudo mkdir -p /var/www

# Set ownership
sudo chown -R www-data:www-data /var/www

# Set permissions
sudo chmod -R 755 /var/www

# Create Nginx log directory for vhosts
sudo mkdir -p /var/log/nginx
sudo chown -R www-data:www-data /var/log/nginx
sudo chmod -R 755 /var/log/nginx
```

---

## Queue Worker Setup

The application uses Laravel queues for background processing of Nginx deployments and SSL certificates.

### Configure Queue Worker

#### Option 1: Using Supervisor (Recommended for Production)

Install Supervisor:

```bash
sudo apt install supervisor -y
```

Create supervisor configuration:

```bash
sudo nano /etc/supervisor/conf.d/git-webhook-manager.conf
```

Add this configuration (adjust paths as needed):

```ini
[program:git-webhook-manager-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/your/application/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=4
redirect_stderr=true
stdout_logfile=/path/to/your/application/storage/logs/worker.log
stopwaitsecs=3600
```

Start the queue worker:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start git-webhook-manager-worker:*
```

Check status:

```bash
sudo supervisorctl status
```

#### Option 2: Using Systemd

Create systemd service file:

```bash
sudo nano /etc/systemd/system/git-webhook-manager-queue.service
```

Add this configuration:

```ini
[Unit]
Description=Git Webhook Manager Queue Worker
After=network.target

[Service]
Type=simple
User=www-data
Group=www-data
Restart=always
ExecStart=/usr/bin/php /path/to/your/application/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600

[Install]
WantedBy=multi-user.target
```

Enable and start the service:

```bash
sudo systemctl daemon-reload
sudo systemctl enable git-webhook-manager-queue
sudo systemctl start git-webhook-manager-queue
sudo systemctl status git-webhook-manager-queue
```

### Configure Laravel Queue Connection

Update your `.env` file:

```env
QUEUE_CONNECTION=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

---

## Verification

### Test Nginx

```bash
# Test configuration
sudo nginx -t

# Check if running
sudo systemctl status nginx

# Check listening ports
sudo netstat -tlnp | grep nginx
```

### Test PHP-FPM

```bash
# Check all PHP-FPM services
sudo systemctl status php7.4-fpm
sudo systemctl status php8.0-fpm
sudo systemctl status php8.1-fpm
sudo systemctl status php8.2-fpm
sudo systemctl status php8.3-fpm

# Check PHP-FPM sockets
ls -la /var/run/php/
```

### Test Redis

```bash
# Test connection
redis-cli ping

# Check queue
redis-cli
> KEYS *
> exit
```

### Test Sudo Permissions

```bash
# Switch to web server user
sudo -u www-data bash

# Test Nginx commands
sudo nginx -t
sudo systemctl reload nginx

# Test file operations
echo "test" > /tmp/test-nginx-config
sudo cp /tmp/test-nginx-config /etc/nginx/sites-available/test-config
sudo chmod 644 /etc/nginx/sites-available/test-config

# Clean up
sudo rm /etc/nginx/sites-available/test-config
rm /tmp/test-nginx-config
exit
```

### Test Queue Worker

```bash
# Check queue worker status (if using supervisor)
sudo supervisorctl status

# Or check systemd service
sudo systemctl status git-webhook-manager-queue

# Monitor queue in real-time
php artisan queue:listen --tries=1
```

---

## Troubleshooting

### Common Issues

#### 1. Nginx Test Fails

```bash
# Check syntax errors
sudo nginx -t

# Check error log
sudo tail -f /var/log/nginx/error.log
```

#### 2. PHP-FPM Socket Not Found

```bash
# Restart PHP-FPM
sudo systemctl restart php8.2-fpm

# Check socket exists
ls -la /var/run/php/
```

#### 3. Permission Denied Errors

```bash
# Check file ownership
ls -la /etc/nginx/sites-available/

# Fix ownership if needed
sudo chown www-data:www-data /etc/nginx/sites-available/*
```

#### 4. Queue Not Processing

```bash
# Check queue worker is running
sudo supervisorctl status

# Or for systemd
sudo systemctl status git-webhook-manager-queue

# Check Laravel logs
tail -f storage/logs/laravel.log

# Manually process queue
php artisan queue:work --once
```

#### 5. SSL Certificate Request Fails

```bash
# Test certbot manually
sudo certbot --nginx -d yourdomain.com --dry-run

# Check certbot logs
sudo tail -f /var/log/letsencrypt/letsencrypt.log
```

---

## Built-in Security Features

The Git Webhook Manager automatically applies comprehensive security hardening to all generated Nginx configurations:

### Automatic Security Headers

Every virtual host includes these security headers:

- **X-Frame-Options**: Prevents clickjacking attacks
- **X-Content-Type-Options**: Prevents MIME-sniffing attacks
- **X-XSS-Protection**: Enables XSS filtering in browsers
- **Referrer-Policy**: Controls referrer information
- **Permissions-Policy**: Restricts browser features (geolocation, microphone, camera)
- **Server Tokens Off**: Hides Nginx version number

### SSL/TLS Hardening

When SSL is enabled, the system applies:

- **TLS 1.2 and 1.3 only**: Deprecated protocols disabled
- **Strong cipher suites**: Modern, secure ciphers (ECDHE, ChaCha20-Poly1305, AES-GCM)
- **HSTS**: HTTP Strict Transport Security with 1-year max-age
- **OCSP Stapling**: Improved SSL performance and privacy
- **Session management**: Secure session cache and no session tickets
- **Automatic HTTP → HTTPS redirect**

### Protection Against Common Attacks

Each configuration includes:

1. **Hidden Files Protection**: Blocks access to `.git`, `.env`, `.htaccess`, etc.
2. **Sensitive Files Blocking**: Denies access to `.log`, `.sql`, `.conf`, `.bak`, etc.
3. **Exploit Files Blocking**: Blocks `composer.json`, `package.json`, `Dockerfile`, etc.
4. **Buffer Overflow Protection**: Proper buffer size limits
5. **Request Timeouts**: Prevents slowloris attacks
6. **PHP Security**: `try_files` to prevent PHP execution vulnerabilities

### Performance Optimizations

- **Static file caching**: 30-day cache for images, CSS, JS
- **Gzip compression**: Reduces bandwidth usage
- **FastCGI buffering**: Optimized for PHP applications
- **Proxy buffering**: Optimized for Node.js applications

### PHP-Specific Security

Each PHP website gets its own isolated PHP-FPM pool with hardened settings:

- **Dedicated PHP-FPM Pool**: Each website runs in an isolated pool with custom configuration
- **Disabled Dangerous Functions**: By default blocks `exec`, `shell_exec`, `system`, `passthru`, `proc_open`, `popen`, `curl_exec`, `parse_ini_file`, `show_source`
- **Memory Limits**: Configurable memory_limit (default: 256M)
- **Execution Timeouts**: Configurable max_execution_time (default: 300s)
- **Upload Limits**: Configurable upload_max_filesize and post_max_size (default: 100M)
- **PHP Exposure**: `expose_php` disabled to hide PHP version
- **Display Errors**: Disabled in production (`display_errors = Off`)
- **URL Include**: `allow_url_include` disabled to prevent remote code execution
- **Dynamic Loading**: `enable_dl` disabled for security
- **Custom Settings Per Website**: Ability to customize PHP settings per website through the web interface
- **PHP file execution check**: Prevents arbitrary file execution
- **FastCGI parameters**: Properly configured to prevent injection
- **Optimized buffers**: Prevents memory exhaustion

### Node.js-Specific Security

- **WebSocket support**: Proper Upgrade header handling
- **Proxy headers**: Correct forwarding of client information
- **Timeout settings**: Prevents hanging connections
- **Fallback routing**: Secure asset handling

---

## Security Recommendations

1. **Firewall Configuration**:

   ```bash
   sudo ufw allow 80/tcp
   sudo ufw allow 443/tcp
   sudo ufw enable
   ```

2. **Regular Updates**:

   ```bash
   sudo apt update && sudo apt upgrade -y
   ```

3. **SSL Certificate Auto-Renewal**:

   ```bash
   # Certbot auto-renewal is configured by default
   # Test renewal
   sudo certbot renew --dry-run
   ```

4. **Secure Redis** (if exposed):

   ```bash
   # Edit Redis config
   sudo nano /etc/redis/redis.conf
   
   # Set password
   requirepass your_secure_password
   
   # Bind to localhost only
   bind 127.0.0.1
   
   # Restart Redis
   sudo systemctl restart redis-server
   ```

5. **Application Authentication**: Ensure your application has proper user authentication before exposing it to the internet.

---

## Next Steps

After completing these prerequisites, proceed to:

1. Install the application (see `README.md`)
2. Configure your `.env` file
3. Run migrations: `php artisan migrate`
4. Start the queue worker
5. Access the application and create your first virtual host

---

## Support

If you encounter issues not covered in this guide:

1. Check the application logs: `storage/logs/laravel.log`
2. Check Nginx logs: `/var/log/nginx/error.log`
3. Check PHP-FPM logs: `/var/log/php*.log`
4. Open an issue on the project repository

---

**Version**: 1.0.0  
**Last Updated**: November 2025  
**Tested On**: Ubuntu 22.04 LTS
