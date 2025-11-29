#!/bin/bash

#########################################################
# Git Webhook Manager - Ubuntu Setup Script
# Automates installation of all prerequisites
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
print_info "Starting Git Webhook Manager prerequisite installation..."
echo ""

# Check if running as root
check_root

# Update system
print_info "Updating system packages..."
apt-get update -y
apt-get upgrade -y
print_success "System updated"

# Install basic dependencies
print_info "Installing basic dependencies..."
apt-get install -y software-properties-common apt-transport-https ca-certificates \
    curl wget git unzip build-essential gnupg2 lsb-release
print_success "Basic dependencies installed"

# Install Nginx
print_info "Installing Nginx..."
apt-get install -y nginx
systemctl enable nginx
systemctl start nginx
print_success "Nginx installed and started"

# Add PHP repository
print_info "Adding PHP repository..."
add-apt-repository -y ppa:ondrej/php
apt-get update -y
print_success "PHP repository added"

# Install multiple PHP versions
print_info "Installing PHP versions (7.4, 8.0, 8.1, 8.2, 8.3, 8.4)..."
for version in 7.4 8.0 8.1 8.2 8.3 8.4; do
    print_info "Installing PHP $version..."
    apt-get install -y \
        php${version}-fpm \
        php${version}-cli \
        php${version}-common \
        php${version}-mysql \
        php${version}-pgsql \
        php${version}-sqlite3 \
        php${version}-zip \
        php${version}-gd \
        php${version}-mbstring \
        php${version}-curl \
        php${version}-xml \
        php${version}-bcmath \
        php${version}-intl \
        php${version}-redis
    systemctl enable php${version}-fpm
    systemctl start php${version}-fpm
    print_success "PHP $version installed"
done

# Install Composer
print_info "Installing Composer..."
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer
chmod +x /usr/local/bin/composer
print_success "Composer installed"

# Install Node.js versions using NVM
print_info "Installing NVM (Node Version Manager)..."
curl -o- https://raw.githubusercontent.com/nvm-sh/nvm/v0.39.0/install.sh | bash
export NVM_DIR="$HOME/.nvm"
[ -s "$NVM_DIR/nvm.sh" ] && \. "$NVM_DIR/nvm.sh"
print_success "NVM installed"

print_info "Installing Node.js versions (16, 18, 20, 21)..."
for version in 16 18 20 21; do
    nvm install $version
    print_success "Node.js $version installed"
done

# Set Node 20 as default
nvm alias default 20
print_success "Node.js 20 set as default"

# Install PM2 globally
print_info "Installing PM2..."
npm install -g pm2
pm2 startup
print_success "PM2 installed"

# Install Redis
print_info "Installing Redis..."
apt-get install -y redis-server
systemctl enable redis-server
systemctl start redis-server
print_success "Redis installed and started"

# Install MySQL
print_info "Installing MySQL..."
apt-get install -y mysql-server
systemctl enable mysql
systemctl start mysql
print_success "MySQL installed and started"

# Install Certbot for SSL
print_info "Installing Certbot..."
apt-get install -y certbot python3-certbot-nginx
print_success "Certbot installed"

# Create web directories
print_info "Creating web directories..."
mkdir -p /var/www
chown -R www-data:www-data /var/www
chmod -R 755 /var/www
print_success "Web directories created"

# Create PM2 config directory
print_info "Creating PM2 config directory..."
mkdir -p /etc/pm2
chmod 755 /etc/pm2
print_success "PM2 config directory created"

# Summary
echo ""
print_success "=========================================="
print_success "Prerequisites installation completed!"
print_success "=========================================="
echo ""
print_info "Installed components:"
echo "  • Nginx"
echo "  • PHP 7.4, 8.0, 8.1, 8.2, 8.3, 8.4 with FPM"
echo "  • Composer"
echo "  • Node.js 16, 18, 20, 21 (via NVM)"
echo "  • PM2"
echo "  • Redis"
echo "  • MySQL"
echo "  • Certbot"
echo ""
print_info "Next steps:"
echo "  1. Run: bash scripts/setup-sudoers.sh"
echo "  2. Configure MySQL database"
echo "  3. Clone and setup your Laravel application"
echo ""
