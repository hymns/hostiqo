# Setup Scripts for Git Webhook Manager

Automated installation scripts for easy deployment on Ubuntu servers.

## 📋 Scripts Overview

### 1. `setup-ubuntu.sh`
Installs all system prerequisites and dependencies.

**Installs:**
- Nginx web server
- PHP 7.4, 8.0, 8.1, 8.2, 8.3, 8.4 with PHP-FPM
- Composer (PHP package manager)
- Node.js 16, 18, 20, 21 via NVM
- PM2 (Node.js process manager)
- Redis (queue backend)
- MySQL (database server)
- Certbot (SSL certificate management)

**Usage:**
```bash
sudo bash scripts/setup-ubuntu.sh
```

**Time:** ~15-20 minutes depending on internet speed

---

### 2. `setup-sudoers.sh`
Configures passwordless sudo permissions for required commands.

**Configures permissions for:**
- Nginx management (reload, restart, test)
- SSL certificate management (certbot)
- PHP-FPM pool management
- Nginx configuration file management
- PM2 process management
- Git deployments

**Usage:**
```bash
# For www-data user (default)
sudo bash scripts/setup-sudoers.sh

# For custom web server user
sudo bash scripts/setup-sudoers.sh your-user
```

**Time:** < 1 minute

---

### 3. `setup-app.sh`
Sets up the Laravel application and its dependencies.

**Performs:**
- Installs Composer dependencies
- Generates application key
- Creates required directories
- Sets proper permissions
- Runs database migrations (optional)
- Builds frontend assets
- Creates storage link
- Optimizes application (cache config, routes, views)
- Creates Supervisor configurations

**Usage:**
```bash
bash scripts/setup-app.sh
```

**Time:** ~5-10 minutes

---

## 🚀 Quick Start - Complete Installation

### Step 1: System Prerequisites
```bash
# Update system and install prerequisites
sudo bash scripts/setup-ubuntu.sh
```

### Step 2: Configure Permissions
```bash
# Setup sudoers configuration
sudo bash scripts/setup-sudoers.sh
```

### Step 3: Application Setup
```bash
# Setup Laravel application
bash scripts/setup-app.sh
```

### Step 4: Configure Environment
```bash
# Edit .env file with your settings
nano .env

# Update these values:
# - DB_CONNECTION, DB_DATABASE, DB_USERNAME, DB_PASSWORD
# - APP_URL
# - MAIL_* settings
```

### Step 5: Run Migrations
```bash
php artisan migrate
```

### Step 6: Start Services
```bash
# If using Supervisor (production)
sudo supervisorctl start git-webhook-queue:*
sudo supervisorctl start git-webhook-scheduler:*

# Or manually (development)
php artisan queue:work &
php artisan schedule:work &
```

---

## 🔧 Manual Installation

If you prefer manual installation or need to customize, refer to [PREREQUISITES.md](../PREREQUISITES.md) for detailed instructions.

---

## 🐛 Troubleshooting

### Script fails during execution
```bash
# Check the error message
# Most common issues:
# 1. Not running with sudo (for system scripts)
# 2. Internet connection issues
# 3. Package repository issues

# Fix package repository issues:
sudo apt-get update --fix-missing
```

### Permissions errors
```bash
# Verify web server user
ps aux | grep nginx

# Re-run sudoers script with correct user
sudo bash scripts/setup-sudoers.sh www-data
```

### Database connection errors
```bash
# Check MySQL is running
sudo systemctl status mysql

# Test connection
mysql -u root -p

# Update .env file with correct credentials
```

### Queue worker not starting
```bash
# Check Supervisor status
sudo supervisorctl status

# View logs
tail -f storage/logs/queue-worker.log

# Restart manually
sudo supervisorctl restart git-webhook-queue:*
```

---

## 📝 Post-Installation

### Create Admin User
```bash
php artisan tinker
>>> $user = new App\Models\User();
>>> $user->name = 'Admin';
>>> $user->email = 'admin@example.com';
>>> $user->password = bcrypt('password');
>>> $user->save();
```

### Test SSL Certificate Request
```bash
# Dry run (test without actually requesting)
sudo certbot --nginx -d your-domain.com --dry-run
```

### Verify Services
```bash
# Check Nginx
sudo systemctl status nginx

# Check PHP-FPM (all versions)
sudo systemctl status php*-fpm

# Check Redis
sudo systemctl status redis

# Check MySQL
sudo systemctl status mysql
```

---

## ⚙️ Configuration Files

All configuration files are created in:

- **Nginx:** `/etc/nginx/sites-available/` and `/etc/nginx/sites-enabled/`
- **PHP-FPM Pools:** `/etc/php/*/fpm/pool.d/`
- **PM2 Configs:** `/etc/pm2/`
- **Supervisor:** `/etc/supervisor/conf.d/`
- **Sudoers:** `/etc/sudoers.d/git-webhook-manager`

---

## 🔒 Security Notes

### Sudoers File
The sudoers configuration file is created with restricted permissions (0440) and only allows specific commands needed for the application to function.

### File Permissions
All created files and directories follow the principle of least privilege:
- Web files: 755 (directories) / 644 (files)
- Sensitive configs: 440 (sudoers)
- Private keys: 600

### Review Permissions
After installation, review the sudoers file:
```bash
sudo cat /etc/sudoers.d/git-webhook-manager
```

---

## 📚 Additional Resources

- [Main README](../README.md) - Application documentation
- [PREREQUISITES.md](../PREREQUISITES.md) - Detailed system requirements
- [Laravel Documentation](https://laravel.com/docs) - Laravel framework docs

---

## 💡 Tips

1. **Take snapshots** before running scripts on production servers
2. **Test on staging** environment first
3. **Review logs** after installation: `/var/log/` and `storage/logs/`
4. **Keep backups** of your configurations
5. **Update regularly** with `sudo apt-get update && sudo apt-get upgrade`

---

## 🆘 Need Help?

If you encounter issues:
1. Check the error messages carefully
2. Review the logs
3. Verify your system meets the requirements
4. Consult the troubleshooting section above
5. Check Laravel logs: `storage/logs/laravel.log`
