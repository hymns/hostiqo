# 🚀 Hostiqo - Simple Server Management

A simple self-managed server panel built with Laravel for automating Git deployments and managing your web server. Deploy websites from GitHub/GitLab, configure Nginx virtual hosts, monitor system health, manage SSL certificates, set up alerts, control your firewall, and more—all through a clean, modern web interface.

![Laravel](https://img.shields.io/badge/Laravel-12.x-FF2D20?style=flat-square&logo=laravel)
![PHP](https://img.shields.io/badge/PHP-8.2+-777BB4?style=flat-square&logo=php)
![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-7952B3?style=flat-square&logo=bootstrap)
[![License](https://img.shields.io/badge/License-MIT%20%2B%20CommonsClause-blue.svg)](LICENSE)

## ✨ Features

### 🚀 Hostiqo Management
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
- 📉 **Timeline Charts** - Visual trend analysis with Chart.js integration (1h, 3h, 6h, 12h filters)
- ⏱️ **Configurable Intervals** - Customizable monitoring intervals and data retention
- 🔄 **Background Collection** - Automated metrics collection via Laravel Scheduler
- 🎯 **Cross-Platform** - Supports both macOS and Linux/Ubuntu servers

### 🚨 Alert & Monitoring System
- 📊 **Metric Monitoring** - CPU, Memory, Disk usage, and Service status tracking
- 🔔 **Multi-Channel Notifications** - Email and Slack webhook integration
- ⚙️ **Custom Thresholds** - Define alert conditions with flexible operators (>, <, ==, !=)
- ⏰ **Duration-Based Alerts** - Prevent false alarms with time-based triggers
- 📝 **Alert History** - Track, view, and resolve triggered alerts
- 🎯 **Severity Levels** - Info, Warning, and Critical alert classification
- 🔄 **Auto-Check** - Runs every minute via Laravel Scheduler

### 🛡️ Firewall Management
- 🔥 **Firewall Control** - Enable/disable firewall from web interface (UFW/firewalld)
- 📋 **Rule Management** - Add, edit, and delete firewall rules
- 🎯 **Port-Based Rules** - Allow/deny specific ports (e.g., 80, 443, 22)
- 🌐 **IP Filtering** - Restrict access by IP address or CIDR range
- ⬆️⬇️ **Direction Control** - Configure inbound, outbound, or both
- 🔄 **Quick Actions** - Reset to defaults, reload rules
- 🖥️ **Multi-Platform** - UFW (Debian/Ubuntu) and firewalld (RHEL/Rocky/Alma)

### ⏰ Cron Jobs Management
- 📅 **Crontab GUI** - Web interface for managing cron jobs
- ⚙️ **Schedule Builder** - Easy configuration with predefined intervals
- 🔄 **Sync to System** - Direct integration with system crontab
- ✅ **Enable/Disable** - Toggle jobs without deletion
- 📝 **Command History** - Track all scheduled commands
- 🖥️ **User-Specific** - Manages www-data user crontab for web tasks

### 📄 Log Viewer
- 📋 **Multi-Log Support** - View Laravel, Nginx access/error, and system logs
- 🔍 **Search & Filter** - Quick search through log entries
- 📊 **Real-time Display** - Shows last 500 lines with latest-first ordering
- 🗑️ **Log Management** - Clear Laravel logs with one click
- 🖥️ **Terminal-Style UI** - Dark theme for easy log reading

### ☁️ CloudFlare Integration
- 🌐 **DNS Management** - Automatic DNS record creation for websites
- 🔄 **Auto-Sync** - One-click DNS synchronization
- ✅ **Status Tracking** - Monitor DNS record status (active/pending/failed)
- 🔐 **Secure API** - Uses CloudFlare API tokens for authentication
- 🎯 **A Record Support** - Automatic A record creation pointing to server IP

### ⚙️ Service Manager
- 🔧 **System Services** - Manage Nginx, PHP-FPM, MySQL, Redis, Supervisor from web UI
- 📊 **Service Status** - Real-time status, PID, uptime, CPU and RAM usage per service
- 🔄 **Service Control** - Start, stop, restart, reload services with one click
- 📋 **Service Logs** - View service logs (systemd journal) with configurable line counts
- ⚡ **Multi-Version PHP** - Manage all PHP versions (7.4-8.4) individually

### 🎨 General Features
- 🚦 **Queue System** - Asynchronous deployment and configuration processing
- 📱 **Responsive Design** - Modern card-based UI, works on all devices
- 🎨 **PSR-Compliant Code** - Clean, maintainable codebase
- 🔐 **Secure by Design** - Proper permission management and validation
- 🌓 **Beautiful UI** - Clean, modern Bootstrap 5 interface with collapsible cards

## 📋 Requirements

- **Ubuntu 20.04+** / **Debian 11+**
- **Rocky Linux 8+** / **AlmaLinux 8+** / **CentOS Stream 8+**
- Root access or sudo privileges
- Domain name pointed to your server

All other dependencies (PHP, MySQL, Nginx, Redis, etc.) will be installed automatically by the installer.

## 🔧 Installation

### One-Command Install 🚀

```bash
curl -fsSL https://raw.githubusercontent.com/hymns/hostiqo/master/scripts/install.sh | sudo bash
```

Or download and run manually:

```bash
wget https://raw.githubusercontent.com/hymns/hostiqo/master/scripts/install.sh
sudo bash install.sh
```

#### Run a specific installer phase

Need to re-run just part of the installer? Each phase is callable on its own:

```bash
sudo bash install.sh --phase1   # System prerequisites
sudo bash install.sh --phase2   # Sudoers configuration
sudo bash install.sh --phase3   # Laravel application setup
sudo bash install.sh --phase4   # Nginx + SSL configuration
```

**The installer will:**
1. Clone Hostiqo to `/var/www/hostiqo`
2. Install all system prerequisites (Nginx, PHP 8.2, MySQL, Redis, Node.js, Supervisor, etc.)
3. Configure sudo permissions for www-data
4. Setup Laravel application (database, migrations, admin user)
5. Configure Nginx with SSL and security hardening

**Time:** ~15-25 minutes

### What Gets Installed

| Component | Details |
|-----------|---------|
| PHP | 7.4 - 8.4 (with OPcache + JIT auto-tuning) |
| MySQL/MariaDB | MySQL 8.0 (Debian) / MariaDB (RHEL) |
| Nginx | Latest |
| Redis | Latest |
| Node.js | 20.x LTS |
| Supervisor | Latest |
| Certbot | Latest |
| fail2ban | Latest |

### Post-Installation

Access your Hostiqo panel at `https://your-domain.com`

Login with the admin credentials you created during installation.

## 🔄 Updating

Update to the latest version:

```bash
cd /var/www/hostiqo
sudo php artisan hostiqo:update
```

Options:
- `--force` - Skip confirmation prompt
- `--no-backup` - Skip database backup
- `--sudoers` - Refresh sudo permissions after update

Refresh sudoers automatically during the update:

```bash
cd /var/www/hostiqo
sudo php artisan hostiqo:update --sudoers
```

> Note: The `--sudoers` flag executes `sudo bash scripts/install.sh --phase2` to update `/etc/sudoers.d/hostiqo-manager`.

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
MONITORING_INTERVAL=1

# Data retention in hours (how long to keep historical data)
MONITORING_RETENTION_HOURS=24

# Chart display hours (how many hours to show in charts)
MONITORING_CHART_HOURS=6
```

#### Requirements

> ⚙️ The automated installer already provisions Supervisor programs `hostiqo-queue` and `hostiqo-scheduler`, so background workers start automatically on fresh installs. Only follow the manual steps below if you performed a custom/manual setup or need to reconfigure services.

**Queue worker and Scheduler must be running** for deployments and metrics collection:

```bash
# Development
php artisan queue:work
php artisan schedule:work

# Production (use Supervisor)
[program:hostiqo-queue]
command=php artisan queue:work --sleep=3 --tries=3 --max-time=3600
directory=/var/www/hostiqo
user=www-data
numprocs=2
autostart=true
autorestart=true
stopwaitsecs=3600

[program:hostiqo-scheduler]
command=php artisan schedule:work
directory=/var/www/hostiqo
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
     -d '{"text":"Test Alert from Hostiqo Manager 🚀"}'
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
Hostiqo Manager
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
MAIL_FROM_NAME="Hostiqo Manager"
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
├── Contracts/                       # Service interfaces
│   ├── FirewallInterface.php        # Firewall service contract
│   ├── NginxInterface.php           # Nginx service contract
│   ├── PhpFpmInterface.php          # PHP-FPM service contract
│   └── ServiceManagerInterface.php  # Service manager contract
├── Http/Controllers/
│   ├── AlertController.php          # Alert rules & history management
│   ├── ArtisanController.php        # Artisan command runner
│   ├── CloudflareController.php     # CloudFlare DNS management
│   ├── CronJobController.php        # Cron jobs management
│   ├── DashboardController.php      # Dashboard & statistics
│   ├── DatabaseController.php       # Database management
│   ├── DeploymentController.php     # Deployment management
│   ├── FileManagerController.php    # File manager
│   ├── FirewallController.php       # Firewall management (UFW/firewalld)
│   ├── HealthCheckController.php    # Health check endpoint
│   ├── LogViewerController.php      # Log viewer
│   ├── QueueController.php          # Queue monitoring
│   ├── ServerHealthController.php   # Server health monitoring
│   ├── ServiceManagerController.php # Service manager (systemctl)
│   ├── SupervisorProgramController.php # Supervisor program management
│   ├── WebhookController.php        # Webhook CRUD operations
│   ├── WebhookHandlerController.php # Webhook API handler
│   ├── WebsiteController.php        # Website/vhost management
│   └── WordPressDeploymentController.php # WordPress deployment
├── Jobs/
│   ├── CheckAlertsJob.php           # Alert checking & notification
│   ├── CheckSslCertificates.php     # SSL certificate monitoring
│   ├── DeployNginxConfig.php        # Async Nginx/PHP-FPM deployment
│   ├── ProcessDeployment.php        # Async deployment job
│   ├── RenewSslCertificates.php     # SSL auto-renewal job
│   ├── RequestSslCertificate.php    # SSL certificate request
│   └── SystemMonitorJob.php         # System metrics collection
├── Models/
│   ├── Alert.php                    # Triggered alerts model
│   ├── AlertRule.php                # Alert rules model
│   ├── CronJob.php                  # Cron jobs model
│   ├── Database.php                 # Database model
│   ├── Deployment.php               # Deployment model
│   ├── FirewallRule.php             # Firewall rules model
│   ├── SshKey.php                   # SSH key model
│   ├── SupervisorProgram.php        # Supervisor program model
│   ├── SystemMetric.php             # System metrics model
│   ├── Webhook.php                  # Webhook model
│   └── Website.php                  # Website/vhost model
└── Services/
    ├── CloudflareService.php        # CloudFlare API integration
    ├── DatabaseService.php          # Database management
    ├── DeploymentService.php        # Git deployment logic
    ├── FileManagerService.php       # File manager service
    ├── Pm2Service.php               # PM2 ecosystem management
    ├── QueueService.php             # Queue management
    ├── SshKeyService.php            # SSH key generation
    ├── SslService.php               # SSL certificate management
    ├── SupervisorService.php        # Supervisor management
    ├── SystemMonitorService.php     # System metrics collection
    ├── WordPressInstallerService.php # WordPress installer
    ├── Firewall/                    # Firewall services (multi-platform)
    │   ├── FirewallFactory.php      # Factory for OS detection
    │   ├── UfwService.php           # UFW (Debian/Ubuntu)
    │   └── FirewalldService.php     # firewalld (RHEL/Rocky/Alma)
    ├── Nginx/                       # Nginx services (multi-platform)
    │   ├── NginxFactory.php         # Factory for OS detection
    │   ├── DebianNginxService.php   # Debian/Ubuntu Nginx
    │   ├── RhelNginxService.php     # RHEL/Rocky/Alma Nginx
    │   └── LocalNginxService.php    # Local development
    ├── PhpFpm/                      # PHP-FPM services (multi-platform)
    │   ├── PhpFpmFactory.php        # Factory for OS detection
    │   ├── DebianPhpFpmService.php  # Debian/Ubuntu PHP-FPM
    │   ├── RhelPhpFpmService.php    # RHEL/Rocky/Alma (Remi) PHP-FPM
    │   └── LocalPhpFpmService.php   # Local development
    └── ServiceManager/              # Service manager (multi-platform)
        ├── ServiceManagerFactory.php # Factory for OS detection
        ├── DebianServiceManagerService.php # Debian/Ubuntu services
        └── RhelServiceManagerService.php   # RHEL/Rocky/Alma services

resources/views/
├── layouts/app.blade.php            # Main Bootstrap 5 layout
├── dashboard.blade.php              # Dashboard with system overview
├── server-health.blade.php          # Server health monitoring
├── websites/                        # Website management views
├── webhooks/                        # Webhook views
├── deployments/                     # Deployment views
├── alerts/                          # Alert management views
├── firewall/                        # Firewall management views
├── cron-jobs/                       # Cron jobs views
├── logs/                            # Log viewer views
├── databases/                       # Database management views
├── queues/                          # Queue monitoring views
├── file-manager/                    # File manager views
└── supervisor/                      # Supervisor management views

config/
└── monitoring.php                   # System monitoring configuration

storage/server/                      # Local development configs
├── nginx/sites-available/           # Generated Nginx configs
├── php/{version}/pool.d/            # Generated PHP-FPM pools
├── pm2/                             # Generated PM2 ecosystems
└── logs/                            # Application logs
```

## 🎯 Example Post-Deploy Scripts

### Laravel Application:
```bash
#!/bin/bash

# Debian/Ubuntu - use specific PHP version (e.g., PHP 8.3)
/usr/bin/php8.3 /usr/bin/composer install --no-dev --optimize-autoloader
/usr/bin/php8.3 artisan migrate --force
/usr/bin/php8.3 artisan config:cache
/usr/bin/php8.3 artisan route:cache
/usr/bin/php8.3 artisan view:cache

# RHEL/Rocky/Alma - use Remi PHP paths (e.g., PHP 8.3)
# /opt/remi/php83/root/usr/bin/php /usr/bin/composer install --no-dev --optimize-autoloader
# /opt/remi/php83/root/usr/bin/php artisan migrate --force
# /opt/remi/php83/root/usr/bin/php artisan config:cache
# /opt/remi/php83/root/usr/bin/php artisan route:cache
# /opt/remi/php83/root/usr/bin/php artisan view:cache

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
# Copy built files to public directory (adjust paths as needed)
rsync -avz dist/ ./public/
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
- Check queue driver in `.env`: `QUEUE_CONNECTION=database` or `QUEUE_CONNECTION=redis`
- Check pending jobs:
  - Database: `SELECT * FROM jobs;`
  - Redis: `redis-cli LLEN queues:default`
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

## 🤝 Contributing

Contributions are welcome! Please ensure your code:

1. Follows PSR-12 standards
2. Includes proper documentation
3. Has meaningful commit messages
4. Is tested before submission

## 📄 License

This project is licensed under the **MIT License + Commons Clause**.

### Summary
- ✅ Free for personal, educational, or hobby use
- ❌ Commercial use (selling, distribution as part of a paid product, or use by a company for profit) requires prior written permission or a commercial license  

For commercial licensing inquiries, please contact: hello@hamizi.net

### Examples

- A solo developer can fork, modify, and use Hostiqo.dev for their own learning projects or small client work **as long as it is not sold as software**.  
- A company or freelancer who wants to **sell or include it in a commercial product** must contact for licensing.

See the [LICENSE](LICENSE) file for full details.

## 💬 Support

For issues, questions, or suggestions:

- Create an issue in the repository
- Check existing documentation

---

<p align="center">
  <b>Made with ❤️ by <a href="https://github.com/hymns">Muhammad Hamizi Jaminan</a></b><br>
  <sub>Powered by Laravel & Bootstrap 5</sub>
</p>
