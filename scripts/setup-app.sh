#!/bin/bash

#########################################################
# Git Webhook Manager - Application Setup Script
# Sets up Laravel application and services
#########################################################

set -e  # Exit on error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Functions
print_success() {
    echo -e "${GREEN}✓ $1${NC}"
}

print_error() {
    echo -e "${RED}✗ $1${NC}"
}

print_info() {
    echo -e "${YELLOW}→ $1${NC}"
}

print_header() {
    echo -e "${BLUE}=========================================="
    echo -e "$1"
    echo -e "==========================================${NC}"
}

# Get application directory
APP_DIR=$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)

print_header "Git Webhook Manager - Application Setup"
echo ""
print_info "Application directory: $APP_DIR"
echo ""

# Check if .env exists
if [ ! -f "$APP_DIR/.env" ]; then
    print_info "Creating .env file from example..."
    cp "$APP_DIR/.env.example" "$APP_DIR/.env"
    print_success ".env file created"
else
    print_info ".env file already exists"
fi

# Install Composer dependencies
print_info "Installing Composer dependencies..."
cd "$APP_DIR"
composer install --no-interaction --prefer-dist --optimize-autoloader
print_success "Composer dependencies installed"

# Generate application key if not set
if ! grep -q "APP_KEY=base64:" "$APP_DIR/.env"; then
    print_info "Generating application key..."
    php artisan key:generate --force
    print_success "Application key generated"
else
    print_info "Application key already set"
fi

# Create required directories
print_info "Creating required directories..."
mkdir -p "$APP_DIR/storage/app/public"
mkdir -p "$APP_DIR/storage/app/ssh-keys"
mkdir -p "$APP_DIR/storage/framework/cache"
mkdir -p "$APP_DIR/storage/framework/sessions"
mkdir -p "$APP_DIR/storage/framework/views"
mkdir -p "$APP_DIR/storage/logs"
mkdir -p "$APP_DIR/storage/server/nginx"
mkdir -p "$APP_DIR/storage/server/php-fpm"
mkdir -p "$APP_DIR/storage/server/pm2"
mkdir -p "$APP_DIR/bootstrap/cache"
print_success "Directories created"

# Set permissions
print_info "Setting directory permissions..."
chmod -R 755 "$APP_DIR/storage"
chmod -R 755 "$APP_DIR/bootstrap/cache"
print_success "Permissions set"

# Run database migrations
print_info "Running database migrations..."
read -p "Run migrations now? (y/n): " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]; then
    php artisan migrate --force
    print_success "Migrations completed"
else
    print_info "Skipped migrations - run manually with: php artisan migrate"
fi

# Build frontend assets
if [ -f "$APP_DIR/package.json" ]; then
    print_info "Installing npm dependencies..."
    npm install
    print_success "NPM dependencies installed"
    
    print_info "Building frontend assets..."
    npm run build
    print_success "Frontend assets built"
fi

# Create symbolic link for storage
print_info "Creating storage symbolic link..."
php artisan storage:link
print_success "Storage link created"

# Clear and cache config
print_info "Optimizing application..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
print_success "Application optimized"

# Setup Supervisor configs
print_header "Supervisor Configuration"
echo ""
print_info "Creating Supervisor configurations..."

# Queue worker config
cat > /tmp/git-webhook-queue.conf << 'EOF'
[program:git-webhook-queue]
process_name=%(program_name)s_%(process_num)02d
command=php APPDIR/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=APPDIR/storage/logs/queue-worker.log
stopwaitsecs=3600
EOF

# Scheduler config
cat > /tmp/git-webhook-scheduler.conf << 'EOF'
[program:git-webhook-scheduler]
process_name=%(program_name)s
command=php APPDIR/artisan schedule:work
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
redirect_stderr=true
stdout_logfile=APPDIR/storage/logs/scheduler.log
EOF

# Replace APPDIR placeholder
sed -i "s|APPDIR|$APP_DIR|g" /tmp/git-webhook-queue.conf
sed -i "s|APPDIR|$APP_DIR|g" /tmp/git-webhook-scheduler.conf

# Install supervisor configs if supervisor is installed
if command -v supervisorctl &> /dev/null; then
    print_info "Installing Supervisor configs..."
    sudo cp /tmp/git-webhook-queue.conf /etc/supervisor/conf.d/
    sudo cp /tmp/git-webhook-scheduler.conf /etc/supervisor/conf.d/
    sudo supervisorctl reread
    sudo supervisorctl update
    sudo supervisorctl start git-webhook-queue:*
    sudo supervisorctl start git-webhook-scheduler:*
    print_success "Supervisor configs installed and started"
else
    print_info "Supervisor not installed - configs saved to /tmp/"
    print_info "Install supervisor: sudo apt-get install supervisor"
    print_info "Then copy configs manually from /tmp/"
fi

# Summary
print_header "Setup Complete!"
echo ""
print_success "Application setup completed successfully!"
echo ""
print_info "What's been configured:"
echo "  ✓ Composer dependencies installed"
echo "  ✓ Application key generated"
echo "  ✓ Directory structure created"
echo "  ✓ Permissions set correctly"
echo "  ✓ Database migrations ready"
echo "  ✓ Frontend assets built"
echo "  ✓ Application optimized"
echo "  ✓ Supervisor configs created"
echo ""
print_info "Next steps:"
echo "  1. Configure your .env file (database, mail, etc.)"
echo "  2. Run: php artisan migrate (if not done already)"
echo "  3. Create admin user"
echo "  4. Configure your web server (Nginx/Apache)"
echo ""
print_info "Development server:"
echo "  php artisan serve"
echo ""
print_info "Queue worker (dev):"
echo "  php artisan queue:work"
echo ""
print_info "Scheduler (dev):"
echo "  php artisan schedule:work"
echo ""
