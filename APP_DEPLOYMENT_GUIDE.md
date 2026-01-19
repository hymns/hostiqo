# Application Deployment Guide

This guide provides step-by-step instructions for deploying different types of applications on Hostiqo.

---

## Table of Contents

1. [PHP Applications](#php-applications)
2. [Node.js Applications](#nodejs-applications)
3. [Python Applications](#python-applications)
4. [Go Applications](#go-applications)
5. [Ruby Applications](#ruby-applications)
6. [Java Applications](#java-applications)
7. [Static Sites](#static-sites)
8. [Environment Variables](#environment-variables)
9. [Process Management](#process-management)

---

## PHP Applications

### Project Type: `php`

### What You Need:
- ✅ Domain name
- ✅ Root path (e.g., `/var/www/myapp`)
- ✅ PHP version (7.4, 8.0, 8.1, 8.2, 8.3)
- ✅ Framework (optional): Laravel, WordPress, CodeIgniter, etc.

### What Hostiqo Handles Automatically:
- ✅ PHP-FPM pool configuration
- ✅ Nginx configuration with PHP processing
- ✅ SSL certificate (Let's Encrypt)
- ✅ PHP settings (memory_limit, max_execution_time, etc.)

---

### Step 1: Create Website

1. **Navigate to Websites**
   - Go to **Websites** → **Create Website**

2. **Fill Website Details**
   - **Domain:** `myapp.com`
   - **Project Type:** Select `PHP`
   - **PHP Version:** Choose version (e.g., `8.3`)
   - **Root Path:** `/var/www/myapp`
   - **Working Directory:** Aet subdirectory (e.g., `public`) or leave "/"
   - **SSL Enabled:** Check if you want SSL
   - **Redirect WWW:** Optional
   - **Set Website Active:** Optional

3. **Click "Create Website"**
   - Hostiqo will generate Nginx and PHP-FPM configurations
   - Website created but it just empty website

---

### Step 2A: Manual Deployment

**Use this method if you're uploading files manually.**

1. **Upload Your Code**
   
   **Option 1: File Manager**
   - Go to **File Manager** in Hostiqo
   - Navigate to `/var/www/myapp`
   - Upload your PHP files
   - Extract if uploaded as ZIP
   
   **Option 2: SFTP**
   - Connect via SFTP client (FileZilla, Cyberduck)
   - Host: Your server IP
   - Username: Your SSH user
   - Upload to: `/var/www/myapp`
   
   **Option 3: SSH/SCP**
   ```bash
   scp -r /local/path/myapp user@server:/var/www/myapp
   ```

2. **Set Permissions**
   ```bash
   # SSH into server
   ssh user@server
   
   # Laravel
   cd /var/www/myapp
   chmod -R 755 storage bootstrap/cache
   chown -R www-data:www-data storage bootstrap/cache
   
   # WordPress
   chmod -R 755 wp-content/uploads
   chown -R www-data:www-data wp-content/uploads
   ```

3. **Configure Environment**
   ```bash
   # Laravel
   cp .env.example .env
   nano .env  # Edit database credentials
   php artisan key:generate
   
   # WordPress
   cp wp-config-sample.php wp-config.php
   nano wp-config.php  # Edit database credentials
   ```

4. **Install Dependencies**
   ```bash
   cd /var/www/myapp
   composer install --no-dev --optimize-autoloader
   ```

5. **Database Setup**
   - Create database via **Hostiqo → Databases → Create Database**
   - Run migrations:
   ```bash
   # Laravel
   php artisan migrate --force
   
   # WordPress
   # Use web installer at http://myapp.com/wp-admin/install.php
   ```

6. **Deploy Configuration**
   - Go back to **Websites → Your Website → Edit**
   - Scroll down and click **"Redeploy Config"** button
   - This will write Nginx config and reload services
   - Your website is now live!

---

### Step 2B: Auto Deployment (Recommended)

**Use this method for Git-based deployments with automatic updates.**

1. **Create Webhook**
   - Go to **Webhooks** → **Create Webhook**

2. **Fill Webhook Details**
   - **Name:** `myapp-deployment`
   - **Domain/Website Reference:** Enter you domain name as reference
   - **Repository URL:** `https://github.com/username/myapp.git`
   - **Branch:** `main` or `master` etc...
   - **Deploy Path:** `/var/www/myapp` (auto-filled from website)

3. **SSH Key Configuration** (for private repos)
   - Leave it **Auto-generate SSH Key Pair"** checkbox checked

4. **Configure Pre-Deploy Script** (Optional)
   ```bash
   #!/bin/bash
   
   # Put site in maintenance mode (update this if already deployed once)
   php artisan down
   ```

5. **Configure Post-Deploy Script** (Required)
   ```bash
   #!/bin/bash
   cd /var/www/myapp

   # Build assets (If required by frontend)
   npm install
   npm run build

   # Install/update dependencies
   composer install --no-dev --optimize-autoloader
   
   # Run migrations
   php artisan migrate --force
   
   # Clear and cache config
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   
   # Set permissions
   chmod -R 755 storage bootstrap/cache
   chown -R www-data:www-data storage bootstrap/cache
   
   # Bring site back up (if you put in maintenance mode)
   php artisan up
   
   # Reload PHP-FPM (optional)
   sudo systemctl reload php8.3-fpm
   ```

6. **Click "Create Webhook"**
   - Webhook created with deployment details information
   
7. **Setup Git Webhook**
   - Go to your Git repository settings
   - Go to Repository -> Settings -> Deploy Keys -> Add deploy key
   - Copy the "SSH Key" from Hostiqo
   - Go to Repository -> Settings -> Webhooks -> Add webhook
   - Copy the "Webhook URL" & "Secret" from Hostiqo
   - Set trigger: Push events
   - Content type: `application/json`

8. **Test Deployment**
   - Push a commit to your repository
   - Webhook will trigger automatically
   - Check **Webhooks → Deployments** for status
   - Or you can check all deployments on **Deployments** page

9. **Deploy Configuration**
   - After first successful deployment
   - Go to **Websites → Your Website → Edit**
   - Click **"Redeploy Config"** to activate Nginx
   - Your website is now live with auto-deployment!

---

### Step 3: Process Manager (Not Required for PHP)

**PHP applications don't need process managers** because:
- ✅ PHP-FPM handles process management automatically
- ✅ Nginx passes requests to PHP-FPM
- ✅ No manual process setup needed

**However, if you need background workers:**
- Use **Supervisor** for Laravel queues & scheduler
- See [Process Management](#process-management) section

---

### Additional Configuration

**Cron Jobs** (Laravel Scheduler):
```bash
# Go to Cron Jobs → Create Cron Job
# Command: cd /var/www/myapp && php artisan schedule:run
# Schedule: * * * * * (every minute)
```

**SSL Certificate**:
- Go to **Websites → Your Website**
- Click **"Enable SSL"** button on Quick Actions menu
- Let's Encrypt will issue certificate automatically

**Environment Variables**:
- Edit `.env` file via File Manager or SSH
- Never commit `.env` to Git
- Use `.env.example` as template

---

## Node.js Applications

### Project Type: `reverse-proxy` with Runtime: `Node.js`

### What You Need:
- ✅ Domain name
- ✅ Root path
- ✅ Application port (e.g., 3000)
- ✅ Entry point script (e.g., `index.js`, `server.js`)
- ❌ Node version selection (uses system default or NVM)

### What Hostiqo Handles Automatically:
- ✅ PM2 ecosystem configuration
- ✅ Nginx reverse proxy to your app port
- ✅ SSL certificate
- ✅ Process management (start, stop, restart)
- ✅ Auto-restart on failure
- ✅ Cluster mode (multi-instance)

---

### Step 1: Create Website

1. **Navigate to Websites**
   - Go to **Websites** → **Create Website**

2. **Fill Website Details**
   - **Domain:** `myapp.com`
   - **Project Type:** Select `Reverse Proxy`
   - **Runtime:** Select `Node.js`
   - **Port:** `3000` (your app's port)
   - **Run opt:** `start` (npm script name in package.json, e.g., "start", "prod", "server")
   - **Root Path:** `/var/www/myapp`
   - **Working Directory:** Leave empty
   - **SSL Enabled:** Check if you want SSL
   - **Redirect WWW:** Optional
   - **Redirect HTTPS:** Optional

3. **Click "Create Website"**
   - Hostiqo will generate Nginx reverse proxy config
   - PM2 ecosystem config will be generated on first deploy
   - Website created but not yet deployed

---

### Step 2A: Manual Deployment

**Use this method if you're uploading files manually.**

1. **Upload Your Code**
   
   **Option 1: File Manager**
   - Go to **File Manager** in Hostiqo
   - Navigate to `/var/www/myapp`
   - Upload your Node.js files
   - Extract if uploaded as ZIP
   
   **Option 2: SFTP**
   - Connect via SFTP client
   - Upload to: `/var/www/myapp`
   
   **Option 3: SSH/SCP**
   ```bash
   scp -r /local/path/myapp user@server:/var/www/myapp
   ```

2. **Install Dependencies**
   ```bash
   # SSH into server
   ssh user@server
   cd /var/www/myapp
   
   npm install --production
   # or
   yarn install --production
   ```

3. **Configure Environment Variables**
   ```bash
   # Create .env file
   nano .env
   ```
   
   Add your environment variables:
   ```bash
   PORT=3000
   NODE_ENV=production
   DATABASE_URL=postgresql://user:pass@localhost/db
   API_KEY=your_secret_key
   JWT_SECRET=your_jwt_secret
   ```
   
   Set permissions:
   ```bash
   chmod 600 .env
   chown www-data:www-data .env
   ```

4. **Verify Entry Point**
   - Make sure your `index.js` (or specified run opt) exists
   - Ensure your app listens on the correct port:
   ```javascript
   const PORT = process.env.PORT || 3000;
   app.listen(PORT, () => {
     console.log(`Server running on port ${PORT}`);
   });
   ```

5. **Deploy Configuration**
   - Go to **Websites → Your Website → Edit**
   - Scroll down and click **"Redeploy Config"** button
   - This will:
     - Generate PM2 ecosystem config at `/etc/pm2/ecosystem.{domain}.config.js`
     - Write Nginx reverse proxy config
     - Start PM2 process
     - Reload Nginx

6. **Verify Application**
   - Go to **Websites → Your Website** (detail page)
   - Check **PM2 Process Control** section
   - Status should show "running"
   - Visit your domain to test

---

### Step 2B: Auto Deployment (Recommended)

**Use this method for Git-based deployments with automatic updates.**

1. **Create Webhook**
   - Go to **Webhooks** → **Create Webhook**

2. **Fill Webhook Details**
   - **Name:** `myapp-deployment`
   - **Domain/Website Reference:** Enter your domain name as reference
   - **Repository URL:** `https://github.com/username/myapp.git`
   - **Branch:** `main` or `master`
   - **Deploy Path:** `/var/www/myapp`

3. **SSH Key Configuration** (for private repos)
   - Leave it **Auto-generate SSH Key Pair** checkbox checked

4. **Configure Pre-Deploy Script** (Optional)
   ```bash
   #!/bin/bash
   # Stop PM2 before pulling new code
   pm2 stop myapp-com
   ```

5. **Configure Post-Deploy Script** (Required)
   ```bash
   #!/bin/bash
   cd /var/www/myapp
   
   # Install/update dependencies
   npm install --production
   
   # Run database migrations (if applicable)
   npm run migrate
   
   # Build assets (if applicable)
   npm run build
   
   # Restart PM2 process
   pm2 restart myapp-com
   ```

6. **Click "Create Webhook"**
   - Webhook created with deployment details information
   
7. **Setup Git Webhook**
   - Go to your Git repository settings
   - Go to Repository -> Settings -> Deploy Keys -> Add deploy key
   - Copy the "SSH Key" from Hostiqo
   - Go to Repository -> Settings -> Webhooks -> Add webhook
   - Copy the "Webhook URL" & "Secret" from Hostiqo
   - Set trigger: Push events
   - Content type: `application/json`

8. **Create .env File** (First time only)
   ```bash
   # SSH into server
   ssh user@server
   cd /var/www/myapp
   nano .env
   ```
   
   Add environment variables and save

9. **Test Deployment**
   - Push a commit to your repository
   - Webhook will trigger automatically
   - Check **Webhooks → Deployments** for status

10. **Deploy Configuration** (First time only)
    - After first successful deployment
    - Go to **Websites → Your Website → Edit**
    - Click **"Redeploy Config"** to generate PM2 config and start process
    - Your website is now live with auto-deployment!

---

### Step 3: Process Manager (PM2 - Automatic)

**PM2 is automatically configured** when you deploy a Node.js website.

**What Hostiqo Does:**
- ✅ Generates PM2 ecosystem config at `/etc/pm2/ecosystem.{domain}.config.js`
- ✅ Starts PM2 process in cluster mode
- ✅ Enables auto-restart on crashes
- ✅ Configures logging to `/var/log/pm2/`

**PM2 Controls:**
- Go to **Websites → Your Website** (detail page)
- Scroll to **PM2 Process Control** section
- Use **Start**, **Restart**, **Stop** buttons

**Manual PM2 Commands** (via SSH):
```bash
# List all PM2 processes
pm2 list

# Restart your app
pm2 restart myapp-com

# Stop your app
pm2 stop myapp-com

# View logs
pm2 logs myapp-com

# Monitor resources
pm2 monit
```

**Customize PM2 Config** (Advanced):
1. Edit `/etc/pm2/ecosystem.{domain}.config.js`
2. Modify settings (instances, memory limit, etc.)
3. Restart PM2: `pm2 restart myapp-com`

**Example PM2 Config Customization:**
```javascript
// /etc/pm2/ecosystem.myapp-com.config.js
module.exports = {
  apps: [{
    name: 'myapp-com',
    script: 'index.js',
    cwd: '/var/www/myapp',
    
    // Load environment from .env file
    env_file: '/var/www/myapp/.env',
    
    // Cluster mode with 4 instances
    instances: 4,  // Change from 'max'
    exec_mode: 'cluster',
    
    // Memory limit
    max_memory_restart: '500M',  // Restart if exceeds 500MB
    
    // Auto-restart
    autorestart: true,
    watch: false,
    
    // Logging
    error_file: '/var/log/pm2/myapp-com-error.log',
    out_file: '/var/log/pm2/myapp-com-out.log',
  }]
};
```

---

### Additional Configuration

**Environment Variables Best Practices:**
- ⚠️ **Never commit `.env` to Git**
- ✅ Use `.env.example` as template
- ✅ Load via `require('dotenv').config()` or PM2 `env_file`
- ✅ Set file permissions: `chmod 600 .env`

**SSL Certificate:**
- Go to **Websites → Your Website**
- Click **"Enable SSL"** button
- Let's Encrypt will issue certificate automatically

**Database Setup:**
- Create database via **Hostiqo → Databases**
- Add `DATABASE_URL` to `.env`
- Run migrations via post-deploy script

**Important Notes:**
- ⚠️ Your app **must** listen on the port specified in website config
- ⚠️ PM2 runs in cluster mode by default (uses all CPU cores)
- ✅ PM2 auto-restarts on crashes
- ✅ Logs are in `/var/log/pm2/`
- ✅ Check PM2 status in website detail page

---

## Python Applications

### Project Type: `reverse-proxy` with Runtime: `Python`

### What You Need:
- ✅ Domain name
- ✅ Root path
- ✅ Application port (e.g., 8000)
- ✅ Python virtual environment
- ❌ Python version selection (uses system default)

### What Hostiqo Handles Automatically:
- ✅ Nginx reverse proxy to your app port
- ✅ SSL certificate
- ❌ Process management (you need to setup Systemd)

---

### Step 1: Create Website

1. **Navigate to Websites**
   - Go to **Websites** → **Create Website**

2. **Fill Website Details**
   - **Domain:** `myapp.com`
   - **Project Type:** Select `Reverse Proxy`
   - **Runtime:** Select `Python`
   - **Port:** `8000` (your app's port)
   - **Root Path:** `/var/www/myapp`
   - **Working Directory:** Leave empty
   - **SSL Enabled:** Check if you want SSL
   - **Redirect WWW:** Optional
   - **Redirect HTTPS:** Optional

3. **Click "Create Website"**
   - Hostiqo will generate Nginx reverse proxy config
   - Website created but not yet deployed

---

### Step 2A: Manual Deployment

**Use this method if you're uploading files manually.**

1. **Upload Your Code**
   
   **Option 1: File Manager**
   - Go to **File Manager** in Hostiqo
   - Navigate to `/var/www/myapp`
   - Upload your Python files
   - Extract if uploaded as ZIP
   
   **Option 2: SFTP**
   - Connect via SFTP client
   - Upload to: `/var/www/myapp`
   
   **Option 3: SSH/SCP**
   ```bash
   scp -r /local/path/myapp user@server:/var/www/myapp
   ```

2. **Setup Virtual Environment**
   ```bash
   # SSH into server
   ssh user@server
   cd /var/www/myapp
   
   # Create virtual environment
   python3 -m venv venv
   source venv/bin/activate
   
   # Install dependencies
   pip install -r requirements.txt
   
   # Install production WSGI server
   pip install gunicorn  # For Django/Flask
   # or
   pip install uvicorn  # For FastAPI
   ```

3. **Configure Environment Variables**
   ```bash
   # Create .env file
   nano .env
   ```
   
   Add your environment variables:
   ```bash
   PORT=8000
   DEBUG=False
   DATABASE_URL=postgresql://user:pass@localhost/db
   SECRET_KEY=your_secret_key
   PYTHONUNBUFFERED=1
   ```
   
   Set permissions:
   ```bash
   chmod 600 .env
   chown www-data:www-data .env
   ```

4. **Database Setup** (if applicable)
   - Create database via **Hostiqo → Databases → Create Database**
   - Run migrations:
   ```bash
   # Django
   source venv/bin/activate
   python manage.py migrate
   
   # Flask-Migrate
   flask db upgrade
   ```

5. **Deploy Configuration**
   - Go to **Websites → Your Website → Edit**
   - Scroll down and click **"Redeploy Config"** button
   - This will write Nginx config and reload services

---

### Step 2B: Auto Deployment (Recommended)

**Use this method for Git-based deployments with automatic updates.**

1. **Create Webhook**
   - Go to **Webhooks** → **Create Webhook**

2. **Fill Webhook Details**
   - **Name:** `myapp-deployment`
   - **Domain/Website Reference:** Enter your domain name as reference
   - **Repository URL:** `https://github.com/username/myapp.git`
   - **Branch:** `main` or `master` etc...
   - **Deploy Path:** `/var/www/myapp`

3. **SSH Key Configuration** (for private repos)
   - Leave it **Auto-generate SSH Key Pair** checkbox checked

4. **Configure Pre-Deploy Script** (Optional)
   ```bash
   #!/bin/bash
   # Stop service before pulling new code
   sudo systemctl stop myapp
   ```

5. **Configure Post-Deploy Script** (Required)
   ```bash
   #!/bin/bash
   cd /var/www/myapp
   
   # Activate virtual environment
   source venv/bin/activate
   
   # Install/update dependencies
   pip install -r requirements.txt
   
   # Run database migrations
   python manage.py migrate  # Django
   # flask db upgrade  # Flask
   
   # Collect static files (Django)
   python manage.py collectstatic --noinput
   
   # Restart systemd service
   sudo systemctl restart myapp
   ```

6. **Click "Create Webhook"**
   - Webhook created with deployment details information
   
7. **Setup Git Webhook**
   - Go to your Git repository settings
   - Go to Repository -> Settings -> Deploy Keys -> Add deploy key
   - Copy the "SSH Key" from Hostiqo
   - Go to Repository -> Settings -> Webhooks -> Add webhook
   - Copy the "Webhook URL" & "Secret" from Hostiqo
   - Set trigger: Push events
   - Content type: `application/json`

8. **Create Virtual Environment** (First time only)
   ```bash
   # SSH into server
   ssh user@server
   cd /var/www/myapp
   python3 -m venv venv
   source venv/bin/activate
   pip install -r requirements.txt
   pip install gunicorn  # or uvicorn
   ```

9. **Test Deployment**
   - Push a commit to your repository
   - Webhook will trigger automatically
   - Check **Webhooks → Deployments** for status
   - Or you can check all deployments on **Deployments** page

---

### Step 3: Process Manager (Systemd - Required)

**Python applications require Systemd service for process management.**

1. **Navigate to Process Manager**
   - Go to **Process Manager** → **System Daemon** → **Create Service**

2. **Fill Service Details**
   - **Name:** `myapp`
   - **Description:** `My Python Application`
   - **Working Directory:** `/var/www/myapp`
   - **User:** `www-data`
   - **Group:** `www-data`
   - **Type:** `simple`
   - **Restart:** `always`
   - **Restart Sec:** `10`

3. **ExecStart Command** (Choose based on framework)
   
   **Django:**
   ```bash
   /var/www/myapp/venv/bin/gunicorn myproject.wsgi:application --bind 0.0.0.0:8000 --workers 4
   ```
   
   **Flask:**
   ```bash
   /var/www/myapp/venv/bin/gunicorn app:app --bind 0.0.0.0:8000 --workers 4
   ```
   
   **FastAPI:**
   ```bash
   /var/www/myapp/venv/bin/uvicorn main:app --host 0.0.0.0 --port 8000 --workers 4
   ```

4. **Environment Variables** (Optional - or use EnvironmentFile)
   ```
   PORT=8000
   PYTHONUNBUFFERED=1
   DEBUG=False
   ```
   
   **Or use EnvironmentFile:**
   - Leave Environment Variables empty
   - Use **EnvironmentFile:** `/var/www/myapp/.env`

5. **Check "Deploy Service"**
   - This will create and start the systemd service

6. **Verify Service**
   - Go to **Process Manager** → **System Daemon**
   - Check service status (should be "running")
   - Click **"View Logs"** to check for errors

7. **Final Verification**
   - Visit your domain
   - Check logs: `journalctl -u myapp -f`

---

### Additional Configuration

**SSL Certificate:**
- Go to **Websites → Your Website**
- Click **"Enable SSL"** button on Quick Actions menu
- Let's Encrypt will issue certificate automatically

**Database Setup:**
- Create database via **Hostiqo → Databases**
- Add `DATABASE_URL` to `.env`
- Run migrations via SSH or post-deploy script

**Important Notes:**
- ⚠️ Always use virtual environment
- ⚠️ Use production WSGI server (Gunicorn, uWSGI, Uvicorn)
- ⚠️ Set `PYTHONUNBUFFERED=1` for real-time logs
- ⚠️ Never run with `DEBUG=True` in production
- ✅ Systemd auto-restarts on crashes
- ✅ Use `--workers` flag to utilize multiple CPU cores

---

## Go Applications

### Project Type: `reverse-proxy` with Runtime: `Go`

### What You Need:
- ✅ Domain name
- ✅ Root path
- ✅ Application port (e.g., 8080)
- ✅ Compiled binary
- ❌ Go version selection (compile before upload)

### What Hostiqo Handles Automatically:
- ✅ Nginx reverse proxy to your app port
- ✅ SSL certificate
- ❌ Process management (you need to setup Systemd)

---

### Step 1: Create Website

1. **Navigate to Websites**
   - Go to **Websites** → **Create Website**

2. **Fill Website Details**
   - **Domain:** `myapp.com`
   - **Project Type:** Select `Reverse Proxy`
   - **Runtime:** Select `Go`
   - **Port:** `8080` (your app's port)
   - **Root Path:** `/var/www/myapp`
   - **Working Directory:** Leave empty
   - **SSL Enabled:** Check if you want SSL
   - **Redirect WWW:** Optional
   - **Redirect HTTPS:** Optional

3. **Click "Create Website"**
   - Hostiqo will generate Nginx reverse proxy config
   - Website created but not yet deployed

---

### Step 2A: Manual Deployment

**Use this method if you're uploading compiled binary manually.**

1. **Compile Your Application**
   ```bash
   # On your local machine or CI/CD
   GOOS=linux GOARCH=amd64 go build -o myapp
   ```

2. **Upload Binary**
   
   **Option 1: File Manager**
   - Go to **File Manager** in Hostiqo
   - Navigate to `/var/www/myapp`
   - Upload compiled binary
   
   **Option 2: SFTP**
   - Connect via SFTP client
   - Upload binary to: `/var/www/myapp`
   
   **Option 3: SSH/SCP**
   ```bash
   scp myapp user@server:/var/www/myapp/
   ```

3. **Set Permissions**
   ```bash
   # SSH into server
   ssh user@server
   chmod +x /var/www/myapp/myapp
   chown www-data:www-data /var/www/myapp/myapp
   ```

4. **Configure Environment Variables** (Optional)
   ```bash
   # Create .env file
   nano /var/www/myapp/.env
   ```
   
   Add your environment variables:
   ```bash
   PORT=8080
   GIN_MODE=release
   DATABASE_URL=postgresql://user:pass@localhost/db
   ```

5. **Deploy Configuration**
   - Go to **Websites → Your Website → Edit**
   - Scroll down and click **"Redeploy Config"** button
   - This will write Nginx config and reload services

---

### Step 2B: Auto Deployment (Recommended)

**Use this method for Git-based deployments with automatic compilation.**

1. **Create Webhook**
   - Go to **Webhooks** → **Create Webhook**

2. **Fill Webhook Details**
   - **Name:** `myapp-deployment`
   - **Domain/Website Reference:** Enter your domain name as reference
   - **Repository URL:** `https://github.com/username/myapp.git`
   - **Branch:** `main` or `master` etc...
   - **Deploy Path:** `/var/www/myapp`

3. **SSH Key Configuration** (for private repos)
   - Leave it **Auto-generate SSH Key Pair** checkbox checked

4. **Configure Pre-Deploy Script** (Optional)
   ```bash
   #!/bin/bash
   # Stop service before compiling
   sudo systemctl stop myapp
   ```

5. **Configure Post-Deploy Script** (Required)
   ```bash
   #!/bin/bash
   cd /var/www/myapp
   
   # Compile for Linux
   GOOS=linux GOARCH=amd64 go build -o myapp
   
   # Set permissions
   chmod +x myapp
   chown www-data:www-data myapp
   
   # Restart systemd service
   sudo systemctl restart myapp
   ```

6. **Click "Create Webhook"**
   - Webhook created with deployment details information
   
7. **Setup Git Webhook**
   - Go to your Git repository settings
   - Go to Repository -> Settings -> Deploy Keys -> Add deploy key
   - Copy the "SSH Key" from Hostiqo
   - Go to Repository -> Settings -> Webhooks -> Add webhook
   - Copy the "Webhook URL" & "Secret" from Hostiqo
   - Set trigger: Push events
   - Content type: `application/json`

8. **Test Deployment**
   - Push a commit to your repository
   - Webhook will trigger automatically
   - Check **Webhooks → Deployments** for status
   - Or you can check all deployments on **Deployments** page

---

### Step 3: Process Manager (Systemd - Required)

**Go applications require Systemd service for process management.**

1. **Navigate to Process Manager**
   - Go to **Process Manager** → **System Daemon** → **Create Service**

2. **Fill Service Details**
   - **Name:** `myapp`
   - **Description:** `My Go Application`
   - **Working Directory:** `/var/www/myapp`
   - **User:** `www-data`
   - **Group:** `www-data`
   - **Type:** `simple`
   - **Restart:** `always`
   - **Restart Sec:** `10`

3. **ExecStart Command**
   ```bash
   /var/www/myapp/myapp
   ```

4. **Environment Variables** (Optional - or use EnvironmentFile)
   ```
   PORT=8080
   GIN_MODE=release
   ```
   
   **Or use EnvironmentFile:**
   - Leave Environment Variables empty
   - Use **EnvironmentFile:** `/var/www/myapp/.env`

5. **Check "Deploy Service"**
   - This will create and start the systemd service

6. **Verify Service**
   - Go to **Process Manager** → **System Daemon**
   - Check service status (should be "running")
   - Click **"View Logs"** to check for errors

7. **Final Verification**
   - Visit your domain
   - Check logs: `journalctl -u myapp -f`

---

### Additional Configuration

**SSL Certificate:**
- Go to **Websites → Your Website**
- Click **"Enable SSL"** button on Quick Actions menu
- Let's Encrypt will issue certificate automatically

**Database Setup:**
- Create database via **Hostiqo → Databases**
- Add `DATABASE_URL` to `.env` or systemd environment

**Important Notes:**
- ✅ Go binaries are self-contained (no dependencies)
- ✅ Very fast and efficient
- ⚠️ Must compile for Linux (GOOS=linux GOARCH=amd64)
- ⚠️ Set production mode for frameworks (e.g., `GIN_MODE=release`)
- ✅ Systemd auto-restarts on crashes
- ✅ Single binary deployment - very simple!

---

## Ruby Applications

### Project Type: `reverse-proxy` with Runtime: `Ruby`

### What You Need:
- ✅ Domain name
- ✅ Root path
- ✅ Application port (e.g., 3000)
- ✅ Ruby version (via rbenv or system)

### What Hostiqo Handles Automatically:
- ✅ Nginx reverse proxy to your app port
- ✅ SSL certificate
- ❌ Process management (you need to setup Systemd)

---

### Step 1: Create Website

1. **Navigate to Websites**
   - Go to **Websites** → **Create Website**

2. **Fill Website Details**
   - **Domain:** `myapp.com`
   - **Project Type:** Select `Reverse Proxy`
   - **Runtime:** Select `Ruby`
   - **Port:** `3000` (your app's port)
   - **Root Path:** `/var/www/myapp`
   - **Working Directory:** Leave empty
   - **SSL Enabled:** Check if you want SSL
   - **Redirect WWW:** Optional
   - **Redirect HTTPS:** Optional

3. **Click "Create Website"**
   - Hostiqo will generate Nginx reverse proxy config
   - Website created but not yet deployed

---

### Step 2A: Manual Deployment

**Use this method if you're uploading files manually.**

1. **Upload Your Code**
   
   **Option 1: File Manager**
   - Go to **File Manager** in Hostiqo
   - Navigate to `/var/www/myapp`
   - Upload your Ruby files
   - Extract if uploaded as ZIP
   
   **Option 2: SFTP**
   - Connect via SFTP client
   - Upload to: `/var/www/myapp`
   
   **Option 3: SSH/SCP**
   ```bash
   scp -r /local/path/myapp user@server:/var/www/myapp
   ```

2. **Install Dependencies**
   ```bash
   # SSH into server
   ssh user@server
   cd /var/www/myapp
   
   # Install bundler if needed
   gem install bundler
   
   # Install dependencies
   bundle install --deployment --without development test
   ```

3. **Configure Environment Variables**
   ```bash
   # Create .env file
   nano .env
   ```
   
   Add your environment variables:
   ```bash
   PORT=3000
   RACK_ENV=production
   RAILS_ENV=production
   SECRET_KEY_BASE=your_secret_key
   DATABASE_URL=postgresql://user:pass@localhost/db
   ```
   
   Set permissions:
   ```bash
   chmod 600 .env
   chown www-data:www-data .env
   ```

4. **Database Setup** (Rails)
   - Create database via **Hostiqo → Databases → Create Database**
   - Run migrations:
   ```bash
   cd /var/www/myapp
   RAILS_ENV=production bundle exec rake db:migrate
   ```

5. **Precompile Assets** (Rails)
   ```bash
   RAILS_ENV=production bundle exec rake assets:precompile
   ```

6. **Deploy Configuration**
   - Go to **Websites → Your Website → Edit**
   - Scroll down and click **"Redeploy Config"** button
   - This will write Nginx config and reload services

---

### Step 2B: Auto Deployment (Recommended)

**Use this method for Git-based deployments with automatic updates.**

1. **Create Webhook**
   - Go to **Webhooks** → **Create Webhook**

2. **Fill Webhook Details**
   - **Name:** `myapp-deployment`
   - **Domain/Website Reference:** Enter your domain name as reference
   - **Repository URL:** `https://github.com/username/myapp.git`
   - **Branch:** `main` or `master` etc...
   - **Deploy Path:** `/var/www/myapp`

3. **SSH Key Configuration** (for private repos)
   - Leave it **Auto-generate SSH Key Pair** checkbox checked

4. **Configure Pre-Deploy Script** (Optional)
   ```bash
   #!/bin/bash
   # Stop service before pulling new code
   sudo systemctl stop myapp
   ```

5. **Configure Post-Deploy Script** (Required)
   ```bash
   #!/bin/bash
   cd /var/www/myapp
   
   # Install/update dependencies
   bundle install --deployment --without development test
   
   # Run database migrations (Rails)
   RAILS_ENV=production bundle exec rake db:migrate
   
   # Precompile assets (Rails)
   RAILS_ENV=production bundle exec rake assets:precompile
   
   # Restart systemd service
   sudo systemctl restart myapp
   ```

6. **Click "Create Webhook"**
   - Webhook created with deployment details information
   
7. **Setup Git Webhook**
   - Go to your Git repository settings
   - Go to Repository -> Settings -> Deploy Keys -> Add deploy key
   - Copy the "SSH Key" from Hostiqo
   - Go to Repository -> Settings -> Webhooks -> Add webhook
   - Copy the "Webhook URL" & "Secret" from Hostiqo
   - Set trigger: Push events
   - Content type: `application/json`

8. **Test Deployment**
   - Push a commit to your repository
   - Webhook will trigger automatically
   - Check **Webhooks → Deployments** for status
   - Or you can check all deployments on **Deployments** page

---

### Step 3: Process Manager (Systemd - Required)

**Ruby applications require Systemd service for process management.**

1. **Navigate to Process Manager**
   - Go to **Process Manager** → **System Daemon** → **Create Service**

2. **Fill Service Details**
   - **Name:** `myapp`
   - **Description:** `My Ruby Application`
   - **Working Directory:** `/var/www/myapp`
   - **User:** `www-data`
   - **Group:** `www-data`
   - **Type:** `simple`
   - **Restart:** `always`
   - **Restart Sec:** `10`

3. **ExecStart Command** (Choose based on app server)
   
   **Puma (Recommended):**
   ```bash
   /usr/local/bin/bundle exec puma -C config/puma.rb
   ```
   
   **Unicorn:**
   ```bash
   /usr/local/bin/bundle exec unicorn -c config/unicorn.rb
   ```
   
   **Rails Server (Development only):**
   ```bash
   /usr/local/bin/bundle exec rails server -e production -p 3000
   ```

4. **Environment Variables** (Optional - or use EnvironmentFile)
   ```
   PORT=3000
   RACK_ENV=production
   RAILS_ENV=production
   ```
   
   **Or use EnvironmentFile:**
   - Leave Environment Variables empty
   - Use **EnvironmentFile:** `/var/www/myapp/.env`

5. **Check "Deploy Service"**
   - This will create and start the systemd service

6. **Verify Service**
   - Go to **Process Manager** → **System Daemon**
   - Check service status (should be "running")
   - Click **"View Logs"** to check for errors

7. **Final Verification**
   - Visit your domain
   - Check logs: `journalctl -u myapp -f`

---

### Additional Configuration

**SSL Certificate:**
- Go to **Websites → Your Website**
- Click **"Enable SSL"** button on Quick Actions menu
- Let's Encrypt will issue certificate automatically

**Database Setup:**
- Create database via **Hostiqo → Databases**
- Add `DATABASE_URL` to `.env`
- Run migrations via SSH or post-deploy script

**Important Notes:**
- ⚠️ Use production app server (Puma, Unicorn)
- ⚠️ Precompile assets for Rails: `RAILS_ENV=production bundle exec rake assets:precompile`
- ⚠️ Run migrations: `RAILS_ENV=production bundle exec rake db:migrate`
- ⚠️ Set `SECRET_KEY_BASE` for Rails applications
- ✅ Systemd auto-restarts on crashes
- ✅ Use Puma for multi-threaded performance

---

## Java Applications

### Project Type: `reverse-proxy` with Runtime: `Java`

### What You Need:
- ✅ Domain name
- ✅ Root path
- ✅ Application port (e.g., 8080)
- ✅ JAR file
- ✅ Java runtime (JRE/JDK)

### What Hostiqo Handles Automatically:
- ✅ Nginx reverse proxy to your app port
- ✅ SSL certificate
- ❌ Process management (you need to setup Systemd)

---

### Step 1: Create Website

1. **Navigate to Websites**
   - Go to **Websites** → **Create Website**

2. **Fill Website Details**
   - **Domain:** `myapp.com`
   - **Project Type:** Select `Reverse Proxy`
   - **Runtime:** Select `Java`
   - **Port:** `8080` (your app's port)
   - **Root Path:** `/var/www/myapp`
   - **Working Directory:** Leave empty
   - **SSL Enabled:** Check if you want SSL
   - **Redirect WWW:** Optional
   - **Redirect HTTPS:** Optional

3. **Click "Create Website"**
   - Hostiqo will generate Nginx reverse proxy config
   - Website created but not yet deployed

---

### Step 2A: Manual Deployment

**Use this method if you're uploading JAR file manually.**

1. **Build Your Application**
   ```bash
   # Maven
   mvn clean package
   
   # Gradle
   gradle build
   ```
   
   JAR file will be in:
   - Maven: `target/myapp.jar`
   - Gradle: `build/libs/myapp.jar`

2. **Upload JAR File**
   
   **Option 1: File Manager**
   - Go to **File Manager** in Hostiqo
   - Navigate to `/var/www/myapp`
   - Upload your JAR file
   - Rename to `app.jar` (or keep original name)
   
   **Option 2: SFTP**
   - Connect via SFTP client
   - Upload JAR to: `/var/www/myapp/app.jar`
   
   **Option 3: SSH/SCP**
   ```bash
   scp target/myapp.jar user@server:/var/www/myapp/app.jar
   ```

3. **Set Permissions**
   ```bash
   # SSH into server
   ssh user@server
   chmod 644 /var/www/myapp/app.jar
   chown www-data:www-data /var/www/myapp/app.jar
   ```

4. **Configure Application Properties** (Optional)
   ```bash
   # Create application.properties
   nano /var/www/myapp/application.properties
   ```
   
   Add your configuration:
   ```properties
   server.port=8080
   spring.profiles.active=production
   spring.datasource.url=jdbc:postgresql://localhost:5432/mydb
   spring.datasource.username=dbuser
   spring.datasource.password=dbpass
   ```

5. **Deploy Configuration**
   - Go to **Websites → Your Website → Edit**
   - Scroll down and click **"Redeploy Config"** button
   - This will write Nginx config and reload services

---

### Step 2B: Auto Deployment (Recommended)

**Use this method for Git-based deployments with automatic builds.**

1. **Create Webhook**
   - Go to **Webhooks** → **Create Webhook**

2. **Fill Webhook Details**
   - **Name:** `myapp-deployment`
   - **Domain/Website Reference:** Enter your domain name as reference
   - **Repository URL:** `https://github.com/username/myapp.git`
   - **Branch:** `main` or `master` etc...
   - **Deploy Path:** `/var/www/myapp`

3. **SSH Key Configuration** (for private repos)
   - Leave it **Auto-generate SSH Key Pair** checkbox checked

4. **Configure Pre-Deploy Script** (Optional)
   ```bash
   #!/bin/bash
   # Stop service before building
   sudo systemctl stop myapp
   ```

5. **Configure Post-Deploy Script** (Required)
   ```bash
   #!/bin/bash
   cd /var/www/myapp
   
   # Build with Maven
   mvn clean package -DskipTests
   
   # Or build with Gradle
   # gradle build -x test
   
   # Copy JAR to deployment location
   cp target/myapp-*.jar app.jar
   
   # Set permissions
   chmod 644 app.jar
   chown www-data:www-data app.jar
   
   # Restart systemd service
   sudo systemctl restart myapp
   ```

6. **Click "Create Webhook"**
   - Webhook created with deployment details information
   
7. **Setup Git Webhook**
   - Go to your Git repository settings
   - Go to Repository -> Settings -> Deploy Keys -> Add deploy key
   - Copy the "SSH Key" from Hostiqo
   - Go to Repository -> Settings -> Webhooks -> Add webhook
   - Copy the "Webhook URL" & "Secret" from Hostiqo
   - Set trigger: Push events
   - Content type: `application/json`

8. **Test Deployment**
   - Push a commit to your repository
   - Webhook will trigger automatically
   - Check **Webhooks → Deployments** for status
   - Or you can check all deployments on **Deployments** page

---

### Step 3: Process Manager (Systemd - Required)

**Java applications require Systemd service for process management.**

1. **Navigate to Process Manager**
   - Go to **Process Manager** → **System Daemon** → **Create Service**

2. **Fill Service Details**
   - **Name:** `myapp`
   - **Description:** `My Java Application`
   - **Working Directory:** `/var/www/myapp`
   - **User:** `www-data`
   - **Group:** `www-data`
   - **Type:** `simple`
   - **Restart:** `always`
   - **Restart Sec:** `10`

3. **ExecStart Command** (with JVM options)
   
   **Basic:**
   ```bash
   /usr/bin/java -jar /var/www/myapp/app.jar
   ```
   
   **With JVM Options (Recommended):**
   ```bash
   /usr/bin/java -Xms512m -Xmx1024m -jar /var/www/myapp/app.jar
   ```
   
   **With Spring Boot Config:**
   ```bash
   /usr/bin/java -Xms512m -Xmx1024m -jar /var/www/myapp/app.jar --spring.config.location=/var/www/myapp/application.properties
   ```

4. **Environment Variables** (Optional)
   ```
   SERVER_PORT=8080
   SPRING_PROFILES_ACTIVE=production
   ```

5. **Check "Deploy Service"**
   - This will create and start the systemd service

6. **Verify Service**
   - Go to **Process Manager** → **System Daemon**
   - Check service status (should be "running")
   - Click **"View Logs"** to check for errors

7. **Final Verification**
   - Visit your domain
   - Check logs: `journalctl -u myapp -f`

---

### Additional Configuration

**SSL Certificate:**
- Go to **Websites → Your Website**
- Click **"Enable SSL"** button on Quick Actions menu
- Let's Encrypt will issue certificate automatically

**Database Setup:**
- Create database via **Hostiqo → Databases**
- Update `application.properties` with database credentials

**Important Notes:**
- ⚠️ Set JVM heap size based on available memory (`-Xms` and `-Xmx`)
- ⚠️ Use production profile: `SPRING_PROFILES_ACTIVE=production`
- ⚠️ Ensure JAR is executable: `chmod 644 app.jar`
- ✅ Systemd auto-restarts on crashes
- ✅ Monitor memory usage via `journalctl -u myapp`
- ✅ Spring Boot actuator endpoints for health checks

---

## Static Sites

### Project Type: `static`

### What You Need:
- ✅ Domain name
- ✅ Root path
- ✅ HTML/CSS/JS files

### What Hostiqo Handles Automatically:
- ✅ Nginx static file serving
- ✅ SSL certificate
- ✅ Gzip compression
- ✅ Browser caching
- ✅ index.html fallback for SPAs

---

### Step 1: Create Website

1. **Navigate to Websites**
   - Go to **Websites** → **Create Website**

2. **Fill Website Details**
   - **Domain:** `myapp.com`
   - **Project Type:** Select `Static Site`
   - **Root Path:** `/var/www/myapp`
   - **Working Directory:** Leave empty (or set to `dist`, `build`, `public` for SPAs)
   - **SSL Enabled:** Check if you want SSL
   - **Redirect WWW:** Optional
   - **Redirect HTTPS:** Optional

3. **Click "Create Website"**
   - Hostiqo will generate Nginx static file serving config
   - Website created but not yet deployed

---

### Step 2A: Manual Deployment

**Use this method if you're uploading files manually.**

1. **Build Your Site** (if applicable)
   
   **React/Vue/Angular:**
   ```bash
   # Build production files
   npm run build
   # or
   yarn build
   ```
   
   Output will be in:
   - React (CRA): `build/`
   - Vue: `dist/`
   - Angular: `dist/`
   - Next.js: `out/` (static export)

2. **Upload Files**
   
   **Option 1: File Manager**
   - Go to **File Manager** in Hostiqo
   - Navigate to `/var/www/myapp`
   - Upload your HTML/CSS/JS/images
   - Extract if uploaded as ZIP
   - Ensure `index.html` exists in root
   
   **Option 2: SFTP**
   - Connect via SFTP client
   - Upload files to: `/var/www/myapp`
   
   **Option 3: SSH/SCP**
   ```bash
   # Upload build folder
   scp -r build/* user@server:/var/www/myapp/
   ```

3. **Set Permissions**
   ```bash
   # SSH into server
   ssh user@server
   cd /var/www/myapp
   
   # Set proper permissions
   find . -type f -exec chmod 644 {} \;
   find . -type d -exec chmod 755 {} \;
   chown -R www-data:www-data /var/www/myapp
   ```

4. **Deploy Configuration**
   - Go to **Websites → Your Website → Edit**
   - Scroll down and click **"Redeploy Config"** button
   - This will write Nginx config and reload services
   - Your static site is now live!

---

### Step 2B: Auto Deployment (Recommended)

**Use this method for Git-based deployments with automatic builds.**

1. **Create Webhook**
   - Go to **Webhooks** → **Create Webhook**

2. **Fill Webhook Details**
   - **Name:** `myapp-deployment`
   - **Domain/Website Reference:** Enter your domain name as reference
   - **Repository URL:** `https://github.com/username/myapp.git`
   - **Branch:** `main` or `master` etc...
   - **Deploy Path:** `/var/www/myapp`

3. **SSH Key Configuration** (for private repos)
   - Leave it **Auto-generate SSH Key Pair** checkbox checked

4. **Configure Pre-Deploy Script** (Optional)
   ```bash
   #!/bin/bash
   # Clean old build files
   rm -rf /var/www/myapp/*
   ```

5. **Configure Post-Deploy Script** (Required)
   
   **For SPA (React/Vue/Angular):**
   ```bash
   #!/bin/bash
   cd /var/www/myapp
   
   # Install dependencies
   npm install
   
   # Build production files
   npm run build
   
   # Copy build files to web root
   cp -r build/* .
   # or for Vue: cp -r dist/* .
   # or for Angular: cp -r dist/myapp/* .
   
   # Clean up
   rm -rf build node_modules
   
   # Set permissions
   find . -type f -exec chmod 644 {} \;
   find . -type d -exec chmod 755 {} \;
   chown -R www-data:www-data /var/www/myapp
   ```
   
   **For Plain HTML/CSS/JS:**
   ```bash
   #!/bin/bash
   cd /var/www/myapp
   
   # Set permissions
   find . -type f -exec chmod 644 {} \;
   find . -type d -exec chmod 755 {} \;
   chown -R www-data:www-data /var/www/myapp
   ```

6. **Click "Create Webhook"**
   - Webhook created with deployment details information
   
7. **Setup Git Webhook**
   - Go to your Git repository settings
   - Go to Repository -> Settings -> Deploy Keys -> Add deploy key
   - Copy the "SSH Key" from Hostiqo
   - Go to Repository -> Settings -> Webhooks -> Add webhook
   - Copy the "Webhook URL" & "Secret" from Hostiqo
   - Set trigger: Push events
   - Content type: `application/json`

8. **Test Deployment**
   - Push a commit to your repository
   - Webhook will trigger automatically
   - Check **Webhooks → Deployments** for status
   - Or you can check all deployments on **Deployments** page

9. **Deploy Configuration** (First time only)
   - After first successful deployment
   - Go to **Websites → Your Website → Edit**
   - Click **"Redeploy Config"** to activate Nginx
   - Your static site is now live with auto-deployment!

---

### Additional Configuration

**SSL Certificate:**
- Go to **Websites → Your Website**
- Click **"Enable SSL"** button on Quick Actions menu
- Let's Encrypt will issue certificate automatically

**SPA Routing (React Router, Vue Router, etc.):**
- Hostiqo automatically configures `try_files` for SPA routing
- All routes will fallback to `index.html`
- No additional configuration needed

**Custom 404 Page:**
- Create `404.html` in your root directory
- Nginx will automatically serve it for 404 errors

**Important Notes:**
- ✅ No server-side processing
- ✅ Very fast and efficient (served directly by Nginx)
- ✅ Perfect for React, Vue, Angular, Next.js (static export)
- ✅ Automatic gzip compression
- ✅ Browser caching headers configured
- ⚠️ Build your SPA before upload: `npm run build`
- ⚠️ Ensure `index.html` exists in root or working directory
- ⚠️ API calls should use absolute URLs or environment variables

---

## Environment Variables

### Security Best Practices:

1. **Never Commit Secrets to Git**
   - Add `.env` to `.gitignore`
   - Use `.env.example` as template

2. **Use .env Files**
   ```bash
   # .env
   DATABASE_URL=postgresql://user:pass@localhost/db
   API_KEY=secret_key_here
   JWT_SECRET=another_secret
   ```

3. **File Permissions**
   ```bash
   chmod 600 .env
   chown www-data:www-data .env
   ```

4. **Loading .env Files**

   **Node.js:**
   ```javascript
   require('dotenv').config();
   // or in PM2 ecosystem.config.js
   env_file: '/var/www/myapp/.env'
   ```

   **Python:**
   ```python
   from dotenv import load_dotenv
   load_dotenv()
   ```

   **Systemd:**
   ```ini
   [Service]
   EnvironmentFile=/var/www/myapp/.env
   ```

---

## Process Management

### PM2 (Node.js)

**Automatic Setup:**
- Hostiqo creates PM2 ecosystem config automatically
- Located at: `/etc/pm2/ecosystem.config.js`

**Manual Control:**
```bash
pm2 list                    # List all processes
pm2 restart myapp          # Restart app
pm2 stop myapp             # Stop app
pm2 logs myapp             # View logs
pm2 monit                  # Monitor resources
```

**Configuration:**
- Edit `/etc/pm2/ecosystem.config.js`
- Update `script`, `instances`, `env` as needed
- Restart: `pm2 restart myapp`

---

### Systemd (Python, Go, Ruby, Java)

**Manual Setup Required:**
- Create service via Hostiqo → Process Manager → System Daemon
- Or manually create service file

**Manual Control:**
```bash
sudo systemctl status myapp      # Check status
sudo systemctl start myapp       # Start service
sudo systemctl stop myapp        # Stop service
sudo systemctl restart myapp     # Restart service
sudo journalctl -u myapp -f      # View logs
```

**Service File Location:**
- `/etc/systemd/system/myapp.service`

**Reload After Changes:**
```bash
sudo systemctl daemon-reload
sudo systemctl restart myapp
```

---

## Troubleshooting

### Application Not Starting

1. **Check Logs**
   - PM2: `/var/log/pm2/myapp-error.log`
   - Systemd: `journalctl -u myapp -n 50`
   - Nginx: `/var/log/nginx/error.log`

2. **Check Port**
   - Ensure app listens on correct port
   - Check port not already in use: `netstat -tulpn | grep :3000`

3. **Check Permissions**
   - Files: `644`
   - Directories: `755`
   - Owner: `www-data:www-data`

4. **Check Service Status**
   - PM2: `pm2 status`
   - Systemd: `systemctl status myapp`

### 502 Bad Gateway

- App not running or crashed
- Wrong port in Nginx config
- Firewall blocking port

### 404 Not Found

- Wrong root path
- Missing index file
- Nginx config not deployed

---

## Summary

| Runtime | Process Manager | Auto-Setup | Manual Setup Required |
|---------|----------------|------------|----------------------|
| PHP | PHP-FPM | ✅ Yes | ❌ No |
| Node.js | PM2 | ✅ Yes | ❌ No |
| Python | Systemd | ❌ No | ✅ Yes |
| Go | Systemd | ❌ No | ✅ Yes |
| Ruby | Systemd | ❌ No | ✅ Yes |
| Java | Systemd | ❌ No | ✅ Yes |
| Static | Nginx | ✅ Yes | ❌ No |

**Key Takeaways:**
- ✅ PHP and Node.js are fully automated
- ⚠️ Python, Go, Ruby, Java require manual Systemd service creation
- 🔐 Always use `.env` files for sensitive data
- 📝 Use webhooks for automated deployments
- 🔄 Process managers auto-restart on crashes

---

**Need Help?**
- Check logs first
- Verify file permissions
- Ensure correct port configuration
- Test app manually before adding to process manager
