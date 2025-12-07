# 🚀 Git Webhook Manager

A comprehensive Laravel-based webhook management system for automated Git deployments from GitHub and GitLab. This application allows you to configure webhooks, manage SSH keys, and automate your deployment workflow with a beautiful Bootstrap 5 interface.

![Laravel](https://img.shields.io/badge/Laravel-12.x-FF2D20?style=flat-square&logo=laravel)
![PHP](https://img.shields.io/badge/PHP-8.2+-777BB4?style=flat-square&logo=php)
![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-7952B3?style=flat-square&logo=bootstrap)

## ✨ Features

### 🚀 Git Webhook Management
- 🎯 **Multi-Provider Support** - Works with GitHub and GitLab
- 🔐 **Auto SSH Key Generation** - Unique SSH key pairs for each webhook
- 👤 **Deploy User Control** - Execute deployments as specific system users
- 📊 **Beautiful Dashboard** - Modern Bootstrap 5 UI with statistics
- 🔄 **Automated Deployments** - Trigger deployments via webhooks or manually
- 📝 **Deployment History** - Track all deployments with detailed logs
- 🔒 **Webhook Verification** - Secure webhook signatures validation
- ⚙️ **Pre/Post Deploy Scripts** - Run custom commands before and after deployment

### 🌐 Virtual Host Management
- 🏠 **Multi-Project Support** - Manage both PHP and Node.js projects
- ⚡ **Auto Nginx Configuration** - Automatic vhost generation and deployment
- 🔒 **SSL/TLS Support** - Automated Let's Encrypt SSL certificate management with TLS 1.2/1.3
- 🔄 **Auto SSL Renewal** - Daily automatic certificate renewal (runs at 2:30 AM)
- 🛡️ **Security Hardened** - Auto-applied security headers, HSTS, file protection, and hardened SSL
- 🔄 **Version Management** - Support for multiple PHP (7.4-8.4) and Node.js (16.x-21.x) versions
- 🎯 **Background Processing** - Queue-based Nginx deployment and SSL requests
- 📊 **Status Tracking** - Real-time Nginx and SSL status monitoring
- 🔧 **Easy Configuration** - Simple web interface for website management
- ⚡ **Performance Optimized** - Static caching, gzip compression, optimized buffers

### 📊 Server Health Monitoring
- 💻 **System Metrics** - Real-time CPU, Memory, and Disk usage monitoring
- 📈 **I/O Performance** - Track Disk I/O (read/write) and Network I/O (upload/download) rates
- 📉 **Timeline Charts** - Visual trend analysis with Chart.js integration
- ⏱️ **Configurable Intervals** - Customizable monitoring intervals and data retention
- 🔄 **Background Collection** - Automated metrics collection via Laravel Scheduler
- 🎯 **Cross-Platform** - Supports both macOS and Linux/Ubuntu servers

### 🎨 General Features
- 🚦 **Queue System** - Asynchronous deployment and configuration processing
- 📱 **Responsive Design** - Works on all devices
- 🎨 **PSR-Compliant Code** - Clean, maintainable codebase
- 🔐 **Secure by Design** - Proper permission management and validation

## 📋 Requirements

> **⚠️ Important**: For complete system requirements and installation instructions for Nginx, PHP, Redis, and other dependencies, please see **[PREREQUISITES.md](PREREQUISITES.md)**.

### Minimum Requirements
- PHP >= 8.2
- Composer
- Laravel 12.x
- Database (MySQL, PostgreSQL, SQLite, etc.)
- Git
- SSH (ssh-keygen command)
- Queue worker (for background processing)

### Additional Requirements for Virtual Host Management
- Nginx >= 1.18
- PHP-FPM (multiple versions: 7.4, 8.0, 8.1, 8.2, 8.3, 8.4)
- Node.js (multiple versions: 16.x, 18.x, 20.x, 21.x)
- PM2 (for Node.js process management)
- Redis >= 6.0
- Certbot (for SSL certificates)
- Proper sudo permissions (see [PREREQUISITES.md](PREREQUISITES.md))

## 🔧 Installation

### Quick Setup (Automated) 🚀

For Ubuntu/Debian servers, use our automated setup scripts:

```bash
# 1. Install system prerequisites
sudo bash scripts/setup-ubuntu.sh

# 2. Configure permissions
sudo bash scripts/setup-sudoers.sh

# 3. Setup Laravel application
bash scripts/setup-app.sh
```

**Time:** ~20-30 minutes total

📚 **For detailed instructions**, see [scripts/README.md](scripts/README.md)

---

### Manual Installation

### 1. Clone or Setup Project

```bash
# If cloning
git clone <your-repo-url>
cd git-webhook

# Install dependencies
composer install
npm install
```

### 2. Environment Configuration

```bash
# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Configure your environment
# For local development, keep APP_ENV=local
# This will write configs to storage/server/ instead of /etc/
APP_ENV=local

# Configure your database in .env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=git_webhook
DB_USERNAME=root
DB_PASSWORD=

# Configure queue connection
# Redis recommended for production (better performance)
# Database acceptable for local development (simpler setup)
QUEUE_CONNECTION=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Alternative for local dev (if Redis not available):
# QUEUE_CONNECTION=database
```

### 3. Database Migration

```bash
# Run migrations
php artisan migrate

# Or run with fresh installation
php artisan migrate:fresh
```

### 4. Build Assets

```bash
# Build frontend assets
npm run build

# Or for development
npm run dev
```

### 5. Start Queue Worker and Scheduler

**Important:** Both the queue worker and scheduler must be running!

```bash
# Start queue worker (for deployments)
php artisan queue:work

# Start scheduler (for system monitoring)
php artisan schedule:work

# Or use queue:listen for development
php artisan queue:listen
```

**For Production:** Use a process manager like Supervisor:
```ini
[program:webhook-queue]
command=php /path/to/artisan queue:work --sleep=3 --tries=3
user=www-data
autostart=true
autorestart=true

[program:webhook-scheduler]
command=php /path/to/artisan schedule:work
user=www-data
autostart=true
autorestart=true
```

### 6. Start Development Server

```bash
# Start Laravel development server
php artisan serve

# Access the application at
# http://localhost:8000
```

## 📖 Usage Guide

### Creating a Webhook

1. **Navigate to Webhooks** → Click "Create Webhook"
2. **Fill in Basic Information:**
   - **Name:** Descriptive name for your webhook
   - **Domain:** Optional website reference
   - **Status:** Active/Inactive

3. **Configure Repository:**
   - **Git Provider:** GitHub or GitLab
   - **Repository URL:** SSH or HTTPS URL (e.g., `git@github.com:user/repo.git`)
   - **Branch:** Branch to deploy (e.g., `main`, `develop`)
   - **Local Path:** Absolute path for deployment (e.g., `/var/www/html/myproject`)
   - **Deploy User:** User to execute deployment commands (e.g., `www-data`, `deployer`, `nginx`)

4. **SSH Key Configuration:**
   - Check "Auto-generate SSH Key Pair" to create unique SSH keys
   - Public key will be shown after creation

5. **Deploy Scripts (Optional):**
   - **Pre-Deploy Script:** Commands to run before deployment
   - **Post-Deploy Script:** Commands to run after deployment

### Setting Up Git Provider Webhook

#### For GitHub:

1. Go to your repository → **Settings** → **Webhooks** → **Add webhook**
2. **Payload URL:** Copy from webhook details page
3. **Content type:** `application/json`
4. **Secret:** Copy the secret token from webhook details
5. **Which events?** Just the push event
6. **Active:** ✓ Checked

#### For GitLab:

1. Go to your repository → **Settings** → **Webhooks** → **Add webhook**
2. **URL:** Copy from webhook details page
3. **Secret Token:** Copy from webhook details
4. **Trigger:** Push events
5. **SSL verification:** Enable SSL verification

### Adding SSH Deploy Key

#### For GitHub:

1. Go to repository → **Settings** → **Deploy keys** → **Add deploy key**
2. **Title:** Webhook Deploy Key
3. **Key:** Paste the public SSH key from webhook details
4. **Allow write access:** Not required (read-only is fine)

#### For GitLab:

1. Go to repository → **Settings** → **Repository** → **Deploy Keys**
2. **Title:** Webhook Deploy Key
3. **Key:** Paste the public SSH key
4. Click **Add key**

### Manual Deployment

1. Navigate to **Webhooks** → Select your webhook
2. Click **Deploy Now** button
3. Deployment will be queued and processed by queue worker
4. View deployment status in real-time

### Viewing Deployment Logs

1. Navigate to **Deployments** or click on a deployment
2. View detailed logs including:
   - Deployment status
   - Commit information
   - Terminal output
   - Error messages (if failed)
   - Execution time

### Server Health Monitoring

The Server Health page provides real-time system performance metrics and historical trends.

#### Accessing Server Health

1. Navigate to **Server Health** from the sidebar menu
2. View current system status:
   - **CPU Usage** - Current processor utilization percentage
   - **Memory Usage** - RAM usage with used/total display
   - **Disk Usage** - Storage utilization percentage

#### Understanding the Charts

**System Performance Chart:**
- Displays CPU, Memory, and Disk usage trends over time
- Default shows last 6 hours (configurable)
- Hover over chart for detailed values at specific times

**I/O Performance Chart:**
- **Disk I/O** - Read and write speeds in MB/s
- **Network I/O** - Download and upload rates in MB/s
- Real-time calculation based on metric intervals
- Helps identify performance bottlenecks

#### Configuration

Configure monitoring settings in `.env`:

```bash
# Enable/disable monitoring
MONITORING_ENABLED=true

# Collection interval in minutes (how often to collect metrics)
MONITORING_INTERVAL=2

# Data retention in hours (how long to keep historical data)
MONITORING_RETENTION_HOURS=24

# Chart display hours (how many hours to show in charts)
MONITORING_CHART_HOURS=6
```

#### Requirements

**Scheduler must be running** for metrics collection:

```bash
# Development
php artisan schedule:work

# Production (use Supervisor or systemd)
[program:webhook-scheduler]
command=php /path/to/artisan schedule:work
user=www-data
autostart=true
autorestart=true
```

### Alert & Monitoring System

Monitor system metrics and receive notifications when thresholds are exceeded.

#### Features

- 🚨 **Real-time Monitoring** - Automatic metric checking every minute
- 📊 **Metric Types** - CPU, Memory, Disk usage, and Service status monitoring
- 🔔 **Multi-Channel Notifications** - Email and Slack webhook support
- ⚙️ **Customizable Thresholds** - Define your own alert conditions
- 🎯 **Smart Alerting** - Duration-based triggers to prevent false alarms
- 📝 **Alert History** - Track and resolve triggered alerts

#### Creating Alert Rules

1. Navigate to **Alerts & Monitoring** → **Create Alert Rule**
2. Configure your alert:
   - **Name:** e.g., "High CPU Alert"
   - **Metric:** Choose from CPU, Memory, Disk, or Service
   - **Condition:** `>`, `<`, `==`, `!=`
   - **Threshold:** e.g., `80` (for 80% CPU usage)
   - **Duration:** Minutes before alerting (prevents false alarms)
   - **Channel:** Email, Slack, or Both

#### Slack Integration

**Setting Up Slack Notifications:**

1. **Create Slack Incoming Webhook:**
   - Go to your Slack workspace settings
   - Navigate to: **Apps** → **Incoming Webhooks**
   - Or visit: https://api.slack.com/messaging/webhooks
   - Click **Add to Slack**
   - Choose channel for notifications (e.g., `#alerts`, `#monitoring`)
   - Copy the Webhook URL (looks like: `https://hooks.slack.com/services/T00000000/B00000000/XXXXXXXXXXXX`)

2. **Configure Alert Rule with Slack:**
   - In the alert rule form, select **Slack** or **Both** for notification channel
   - Paste your Slack Webhook URL in the **Slack Webhook URL** field
   - Save the alert rule

3. **Test Your Slack Integration:**
   ```bash
   # Quick test via curl
   curl -X POST YOUR_WEBHOOK_URL \
     -H 'Content-Type: application/json' \
     -d '{"text":"Test Alert from Git Webhook Manager 🚀"}'
   ```

**Slack Notification Format:**

Alerts sent to Slack include:
- 🔴 **Critical** alerts (red color)
- ⚠️ **Warning** alerts (yellow color)
- ℹ️ **Info** alerts (green color)
- Alert title and message
- Timestamp
- Formatted as rich attachments with colors

**Example Slack Message:**
```
🚨 Alert: High CPU Usage
━━━━━━━━━━━━━━━━━━━━
CPU is 85% (threshold: 80%)
━━━━━━━━━━━━━━━━━━━━
Git Webhook Manager
Today at 2:30 PM
```

#### Email Notifications

**Configuring Email:**

Set up email in your `.env` file:

```bash
# Email Configuration
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@webhook.com
MAIL_FROM_NAME="Git Webhook Manager"
```

**Supported Mail Drivers:**
- SMTP (Gmail, Outlook, SendGrid, Mailgun)
- Mailgun API
- Postmark
- Amazon SES
- Sendmail
- Log (for testing)

**For Gmail:**
1. Enable 2-factor authentication
2. Generate App Password: https://myaccount.google.com/apppasswords
3. Use App Password in `MAIL_PASSWORD`

**Email Notification Format:**

```
🔴 Alert: High Memory Usage

Memory is 92% (threshold: 90%)

Time: 2024-12-07 14:30:00
```

#### Example Alert Configurations

**1. High CPU Alert:**
```
Name: High CPU Usage
Metric: CPU
Condition: > (greater than)
Threshold: 80
Duration: 5 minutes
Channel: Both (Email + Slack)
```

**2. Low Disk Space:**
```
Name: Low Disk Space Warning
Metric: Disk
Condition: > (greater than)
Threshold: 90
Duration: 10 minutes
Channel: Email
```

**3. Service Down Alert:**
```
Name: Nginx Service Down
Metric: Service
Condition: != (not equal)
Threshold: 1
Service Name: nginx
Duration: 1 minute
Channel: Slack
```

**4. Memory Spike:**
```
Name: Memory Threshold Exceeded
Metric: Memory
Condition: > (greater than)
Threshold: 85
Duration: 3 minutes
Channel: Both
```

#### How It Works

```
Every 2 minutes: SystemMonitorJob collects metrics
    ↓
Every 1 minute: CheckAlertsJob checks rules
    ↓
If condition met for duration → Trigger alert
    ↓
Send notifications (Email/Slack)
    ↓
Store alert in database
    ↓
User can view and resolve in UI
```

#### Alert Severity Levels

Alerts are automatically categorized by severity:

- **Critical (🔴)** - Threshold exceeded by >20% or service is down
- **Warning (⚠️)** - Threshold exceeded by 10-20%
- **Info (ℹ️)** - Threshold exceeded by <10%

#### Managing Alerts

**View Alerts:**
- Navigate to **Alerts & Monitoring**
- See recent triggered alerts
- Filter by severity and status

**Resolve Alerts:**
- Click **Resolve** button on an alert
- Marks alert as resolved
- Prevents duplicate notifications

**Alert History:**
- All alerts are stored with timestamps
- Track patterns and trends
- Audit alert activity

#### Requirements

The scheduler must be running for alert checking:

```bash
# Development
php artisan schedule:work

# Production (use Supervisor)
[program:webhook-scheduler]
command=php /path/to/artisan schedule:work
user=www-data
autostart=true
autorestart=true
```

### Managing Websites (Virtual Hosts)

#### Creating a PHP Website

1. Navigate to **Websites** → **PHP Projects** → **Add PHP Website**
2. Fill in website details:
   - **Name:** Project identifier
   - **Domain:** e.g., `example.com`
   - **Root Path:** e.g., `/var/www/example_com`
   - **Working Directory:** e.g., `/public` (Laravel), `/public_html` (other)
   - **PHP Version:** Select from 7.4 to 8.3
   - **PHP Pool Name:** Custom FPM pool name (optional)
   - **SSL Enabled:** Check for HTTPS support

3. System automatically generates:
   - Nginx virtual host configuration
   - PHP-FPM pool configuration
   - Webroot directory (in local mode)
   - Sample index.html (in local mode)

#### Creating a Node.js Website

1. Navigate to **Websites** → **Node Projects** → **Add Node Website**
2. Fill in website details:
   - **Name:** Project identifier
   - **Domain:** e.g., `api.example.com`
   - **Root Path:** e.g., `/var/www/api_example_com`
   - **Node Version:** Select from 16.x to 21.x
   - **Port:** Application port (e.g., `3000`, `8080`)
   - **SSL Enabled:** Check for HTTPS support

3. System automatically generates:
   - Nginx reverse proxy configuration
   - **PM2 ecosystem configuration file**
   - Log directories

#### Node.js Deployment Workflow with PM2

**Complete workflow for Node.js applications:**

1. **Add Website** → Generate Nginx + PM2 config
2. **Setup Webhook** → Configure git deployment
3. **Configure Post-Deploy Script:**

```bash
# Install dependencies
npm install --production

# Start or restart PM2 app (works for both first deploy and updates)
pm2 restart api-example-com --update-env || pm2 start /etc/pm2/ecosystem.api-example-com.config.js

# Save PM2 process list
pm2 save
```

4. **Push to Git** → Webhook triggers:
   - Git pulls code
   - Runs post-deploy script
   - PM2 starts/restarts application automatically

**PM2 Generated Configuration:**

The system creates PM2 ecosystem files with:
- Node.js version from website settings
- Application port configuration
- Cluster mode (auto-scale based on CPU cores)
- Auto-restart on failure
- Memory limits (1GB)
- Environment variables (NODE_ENV, PORT)
- Log file paths

**File Locations:**
- **Production:** `/etc/pm2/ecosystem.{domain}.config.js`
- **Local/Dev:** `storage/server/pm2/ecosystem.{domain}.config.js`

**The Magic Command:**

```bash
pm2 restart {app-name} || pm2 start {config-path}
```

This single command handles both scenarios:
- **First deployment:** App doesn't exist → PM2 starts it
- **Subsequent deployments:** App exists → PM2 restarts it

No need to change webhook scripts after first deployment!

#### SSL Certificate Auto-Renewal

The system automatically renews Let's Encrypt SSL certificates to prevent expiration.

**How It Works:**

1. **Automated Schedule:** Runs daily at 2:30 AM
2. **Certbot Renewal:** Executes `certbot renew` to check and renew expiring certificates
3. **Auto-Reload:** Nginx automatically reloads after successful renewal
4. **Zero Downtime:** Renewal happens without service interruption

**Manual Renewal (if needed):**

```bash
# Run renewal manually
sudo certbot renew

# Force renewal (even if not expiring soon)
sudo certbot renew --force-renewal

# Check certificate expiration
sudo certbot certificates
```

**Monitoring:**

- Renewal attempts are logged to `storage/logs/laravel.log`
- Check logs with: `tail -f storage/logs/laravel.log | grep "SSL"`
- Certbot logs: `/var/log/letsencrypt/letsencrypt.log`

**Important Notes:**

- Certificates auto-renew when they have 30 days or less remaining
- Let's Encrypt certificates are valid for 90 days
- Daily checks ensure you never miss a renewal
- Failed renewals are logged for investigation

## 📁 Project Structure

```
app/
├── Http/Controllers/
│   ├── DashboardController.php      # Dashboard & statistics
│   ├── ServerHealthController.php   # Server health monitoring
│   ├── WebhookController.php        # Webhook CRUD operations
│   ├── WebsiteController.php        # Website/vhost management
│   ├── DeploymentController.php     # Deployment management
│   └── WebhookHandlerController.php # Webhook API handler
├── Jobs/
│   ├── ProcessDeployment.php        # Async deployment job
│   ├── DeployNginxConfig.php        # Async Nginx/PHP-FPM deployment
│   └── SystemMonitorJob.php         # System metrics collection job
├── Models/
│   ├── Webhook.php                  # Webhook model
│   ├── Website.php                  # Website/vhost model
│   ├── SshKey.php                   # SSH key model
│   ├── Deployment.php               # Deployment model
│   └── SystemMetric.php             # System metrics model
└── Services/
    ├── SshKeyService.php            # SSH key generation
    ├── DeploymentService.php        # Git deployment logic
    ├── NginxService.php             # Nginx config generation
    ├── PhpFpmService.php            # PHP-FPM pool management
    ├── Pm2Service.php               # PM2 ecosystem management
    └── SystemMonitorService.php     # System metrics collection

resources/views/
├── layouts/
│   └── app.blade.php                # Main Bootstrap 5 layout
├── dashboard.blade.php              # Dashboard view
├── server-health.blade.php          # Server health monitoring view
├── websites/                        # Website views
│   ├── index.blade.php
│   ├── create.blade.php
│   ├── edit.blade.php
│   └── show.blade.php
├── webhooks/                        # Webhook views
│   ├── index.blade.php
│   ├── create.blade.php
│   ├── edit.blade.php
│   └── show.blade.php
└── deployments/                     # Deployment views
    ├── index.blade.php
    └── show.blade.php

config/
└── monitoring.php                   # System monitoring configuration

storage/server/                      # Local development configs
├── nginx/
│   └── sites-available/             # Generated Nginx configs
├── php/{version}/
│   └── pool.d/                      # Generated PHP-FPM pools
├── pm2/                             # Generated PM2 ecosystems
├── www/{domain}/                    # Webroot directories (local only)
└── logs/                            # Application logs
    ├── nginx/
    ├── php*/
    └── pm2/
```

## 🎯 Example Post-Deploy Scripts

### Laravel Application:
```bash
#!/bin/bash
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
npm install
npm run build
```

### Node.js Application:
```bash
#!/bin/bash
# Install dependencies
npm install --production

# Build if needed
npm run build

# Start or restart PM2 app (handles both first deploy and updates)
# Replace 'app-name' with your actual app name (domain with dashes)
pm2 restart app-name --update-env || pm2 start /etc/pm2/ecosystem.app-name.config.js

# Save PM2 process list
pm2 save
```

### Static Website:
```bash
#!/bin/bash
npm install
npm run build
rsync -avz dist/ /var/www/html/
```

## 🔒 Security Best Practices

1. **Never commit `.env` file** - Contains sensitive credentials
2. **Use unique secret tokens** - Auto-generated per webhook
3. **Enable webhook signature verification** - Always verify signatures
4. **Restrict file permissions** - Ensure proper permissions on deployment directories
5. **Use read-only deploy keys** - Don't give write access unless necessary
6. **Run queue worker as limited user** - Don't run as root
7. **Validate deploy scripts** - Review scripts before saving

## 🐛 Troubleshooting

### Deployments Not Processing

**Problem:** Deployments stuck in "pending" status

**Solution:**
- Ensure queue worker is running: `php artisan queue:work`
- Check queue table: `SELECT * FROM jobs;`
- Review logs: `tail -f storage/logs/laravel.log`

### SSH Key Permission Denied

**Problem:** Git clone/pull fails with permission denied

**Solution:**
- Verify SSH key is added to Git provider
- Check key permissions: `chmod 600 storage/app/temp/temp_key_*`
- Test SSH connection: `ssh -T git@github.com`

### Webhook Not Triggering

**Problem:** Git provider webhook not triggering deployments

**Solution:**
- Verify webhook URL is correct and accessible
- Check webhook secret token matches
- Review Git provider webhook delivery logs
- Ensure webhook is active

### Permission Issues

**Problem:** Cannot write to deployment directory

**Solution:**
```bash
# Set proper ownership
sudo chown -R www-data:www-data /var/www/html/myproject

# Set proper permissions
sudo chmod -R 755 /var/www/html/myproject
```

### Deploy User Configuration

**Feature:** Execute deployment commands as specific system user

**Use Case:**
- When deployment path is owned by a different user
- For better security and permission management
- To isolate deployment processes

**Setup:**
1. Configure sudo permissions (see `DEPLOYMENT_USER.md` for details)
2. Set deploy user in webhook configuration
3. Ensure user has proper path permissions

**Example:**
```bash
# Configure sudoers
sudo visudo -f /etc/sudoers.d/laravel-webhook

# Add:
www-data ALL=(ALL) NOPASSWD: /usr/bin/git
www-data ALL=(ALL) NOPASSWD: /bin/bash
```

📖 **Full Documentation:** See [DEPLOYMENT_USER.md](DEPLOYMENT_USER.md) for comprehensive guide

## 🚀 Production Deployment

### 1. Optimize Laravel

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
composer install --optimize-autoloader --no-dev
```

### 2. Setup Supervisor for Queue Worker

Create `/etc/supervisor/conf.d/git-webhook-worker.conf`:

```ini
[program:git-webhook-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasflimit=3600
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/path/to/storage/logs/worker.log
stopwaitsecs=3600
```

Then:
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start git-webhook-worker:*
```

### 3. Setup Nginx (Example)

```nginx
server {
    listen 80;
    server_name webhook.example.com;
    root /path/to/public;

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

## 📝 Code Standards

This project follows **PSR-12** coding standards:

- ✅ PSR-4 autoloading
- ✅ Type declarations
- ✅ Proper docblocks
- ✅ Meaningful variable names
- ✅ Single responsibility principle

## 🤝 Contributing

Contributions are welcome! Please ensure your code:

1. Follows PSR-12 standards
2. Includes proper documentation
3. Has meaningful commit messages
4. Is tested before submission

## 📄 License

This project is open-sourced software licensed under the MIT license.

## 💬 Support

For issues, questions, or suggestions:
- Create an issue in the repository
- Check existing documentation
- Review troubleshooting section

---

**Built with ❤️ using Laravel 12 & Bootstrap 5**

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
