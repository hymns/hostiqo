#!/bin/bash

#########################################################
# Git Webhook Manager - Sudoers Configuration Script
# Sets up passwordless sudo for required commands
#########################################################

set -e  # Exit on error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
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

check_root() {
    if [[ $EUID -ne 0 ]]; then
        print_error "This script must be run as root or with sudo"
        exit 1
    fi
}

# Main setup
print_info "Configuring sudoers for Git Webhook Manager..."
echo ""

# Check if running as root
check_root

# Get web server user
WEB_USER=${1:-www-data}
print_info "Using web server user: $WEB_USER"

# Create sudoers file
SUDOERS_FILE="/etc/sudoers.d/git-webhook-manager"

print_info "Creating sudoers configuration..."

cat > "$SUDOERS_FILE" << EOF
# Git Webhook Manager - Automated Management Permissions
# Web server user: $WEB_USER

# Nginx Management
$WEB_USER ALL=(ALL) NOPASSWD: /usr/sbin/nginx -t
$WEB_USER ALL=(ALL) NOPASSWD: /bin/systemctl reload nginx
$WEB_USER ALL=(ALL) NOPASSWD: /bin/systemctl restart nginx

# Certbot - SSL Certificate Management
$WEB_USER ALL=(ALL) NOPASSWD: /usr/bin/certbot --nginx -d * --non-interactive --agree-tos --email *
$WEB_USER ALL=(ALL) NOPASSWD: /usr/bin/certbot renew *
$WEB_USER ALL=(ALL) NOPASSWD: /usr/bin/certbot certificates

# PHP-FPM Pool Management
$WEB_USER ALL=(ALL) NOPASSWD: /usr/sbin/php-fpm* -t
$WEB_USER ALL=(ALL) NOPASSWD: /bin/systemctl reload php*-fpm
$WEB_USER ALL=(ALL) NOPASSWD: /bin/systemctl restart php*-fpm
$WEB_USER ALL=(ALL) NOPASSWD: /bin/mkdir -p /var/log/php*-fpm*
$WEB_USER ALL=(ALL) NOPASSWD: /bin/chown $WEB_USER:$WEB_USER /var/log/php*-fpm*
$WEB_USER ALL=(ALL) NOPASSWD: /bin/cp /tmp/* /etc/php/*/fpm/pool.d/*
$WEB_USER ALL=(ALL) NOPASSWD: /bin/chmod 644 /etc/php/*/fpm/pool.d/*
$WEB_USER ALL=(ALL) NOPASSWD: /bin/rm -f /etc/php/*/fpm/pool.d/*

# File Management - Nginx Config Files
$WEB_USER ALL=(ALL) NOPASSWD: /bin/cp /tmp/* /etc/nginx/sites-available/*
$WEB_USER ALL=(ALL) NOPASSWD: /bin/chmod 644 /etc/nginx/sites-available/*
$WEB_USER ALL=(ALL) NOPASSWD: /bin/ln -sf /etc/nginx/sites-available/* /etc/nginx/sites-enabled/*
$WEB_USER ALL=(ALL) NOPASSWD: /bin/rm -f /etc/nginx/sites-available/*
$WEB_USER ALL=(ALL) NOPASSWD: /bin/rm -f /etc/nginx/sites-enabled/*

# Webroot Directory Management
$WEB_USER ALL=(ALL) NOPASSWD: /bin/mkdir -p /var/www/*
$WEB_USER ALL=(ALL) NOPASSWD: /bin/chown -R $WEB_USER:$WEB_USER /var/www/*
$WEB_USER ALL=(ALL) NOPASSWD: /bin/chmod -R 755 /var/www/*

# PM2 Configuration Management (Node.js)
$WEB_USER ALL=(ALL) NOPASSWD: /bin/mkdir -p /etc/pm2
$WEB_USER ALL=(ALL) NOPASSWD: /bin/chmod 755 /etc/pm2
$WEB_USER ALL=(ALL) NOPASSWD: /bin/cp /tmp/* /etc/pm2/*
$WEB_USER ALL=(ALL) NOPASSWD: /bin/chmod 644 /etc/pm2/*
$WEB_USER ALL=(ALL) NOPASSWD: /bin/rm -f /etc/pm2/*

# PM2 Process Control (Node.js)
$WEB_USER ALL=(ALL) NOPASSWD: /usr/bin/pm2 start *
$WEB_USER ALL=(ALL) NOPASSWD: /usr/bin/pm2 stop *
$WEB_USER ALL=(ALL) NOPASSWD: /usr/bin/pm2 restart *
$WEB_USER ALL=(ALL) NOPASSWD: /usr/bin/pm2 save
$WEB_USER ALL=(ALL) NOPASSWD: /usr/bin/pm2 jlist

# Git Webhook Deployments
$WEB_USER ALL=(ALL) NOPASSWD: /usr/bin/git
$WEB_USER ALL=(ALL) NOPASSWD: /bin/bash
EOF

# Set proper permissions
chmod 0440 "$SUDOERS_FILE"
print_success "Sudoers file created: $SUDOERS_FILE"

# Validate sudoers syntax
print_info "Validating sudoers syntax..."
if visudo -c -f "$SUDOERS_FILE"; then
    print_success "Sudoers configuration is valid"
else
    print_error "Sudoers configuration has errors!"
    rm -f "$SUDOERS_FILE"
    exit 1
fi

# Test sudo access
print_info "Testing sudo access for $WEB_USER..."
if sudo -u $WEB_USER sudo -n nginx -t &>/dev/null; then
    print_success "Sudo access test passed"
else
    print_error "Sudo access test failed - nginx test command"
fi

echo ""
print_success "=========================================="
print_success "Sudoers configuration completed!"
print_success "=========================================="
echo ""
print_info "Configured permissions for: $WEB_USER"
echo ""
print_info "Allowed commands:"
echo "  • Nginx management (reload, restart, test)"
echo "  • SSL certificate management (certbot)"
echo "  • PHP-FPM pool management"
echo "  • Nginx configuration file management"
echo "  • PM2 process management (Node.js)"
echo "  • Git deployments"
echo ""
print_info "Security file: $SUDOERS_FILE"
print_info "Permissions: 0440 (read-only)"
echo ""
