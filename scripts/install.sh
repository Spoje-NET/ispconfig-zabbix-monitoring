#!/bin/bash

###############################################################################
# ISPConfig Zabbix Monitoring - Installation Script
#
# This script installs and configures the ISPConfig monitoring ecosystem
# for Zabbix 7.4
#
# Requirements:
#   - Root or sudo access
#   - PHP 7.4+ with SOAP extension
#   - Zabbix Agent installed
#   - ISPConfig 3.2+ with SOAP API enabled
#
# Usage: sudo ./install.sh
###############################################################################

set -e  # Exit on error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Script directory
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(dirname "$SCRIPT_DIR")"

# Configuration
ZABBIX_CONF_DIR="/etc/zabbix"
ZABBIX_AGENT_CONF="${ZABBIX_CONF_DIR}/zabbix_agent2.conf"
ZABBIX_INCLUDE_DIR="${ZABBIX_CONF_DIR}/zabbix_agent2.d"
USERPARAMS_FILE="${ZABBIX_INCLUDE_DIR}/ispconfig.conf"
INSTALL_DIR="/usr/local/lib/ispconfig-zabbix-monitoring"

###############################################################################
# Helper Functions
###############################################################################

print_success() {
    echo -e "${GREEN}✓ $1${NC}"
}

print_error() {
    echo -e "${RED}✗ $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}! $1${NC}"
}

print_info() {
    echo -e "${NC}→ $1${NC}"
}

check_root() {
    if [[ $EUID -ne 0 ]]; then
        print_error "This script must be run as root or with sudo"
        exit 1
    fi
}

check_php() {
    print_info "Checking PHP installation..."
    
    if ! command -v php &> /dev/null; then
        print_error "PHP is not installed"
        print_info "Please install PHP 7.4 or later with the following extensions:"
        print_info "  - php-soap"
        print_info "  - php-json"
        print_info "  - php-mbstring"
        print_info "  - php-curl"
        exit 1
    fi
    
    PHP_VERSION=$(php -r 'echo PHP_VERSION;')
    print_success "PHP ${PHP_VERSION} found"
    
    # Check required extensions
    REQUIRED_EXTS=("soap" "json" "mbstring" "curl")
    MISSING_EXTS=()
    
    for ext in "${REQUIRED_EXTS[@]}"; do
        if ! php -m | grep -qi "^${ext}$"; then
            MISSING_EXTS+=("$ext")
        fi
    done
    
    if [ ${#MISSING_EXTS[@]} -ne 0 ]; then
        print_error "Missing PHP extensions: ${MISSING_EXTS[*]}"
        print_info "Install them with: apt-get install $(printf 'php-%s ' "${MISSING_EXTS[@]}")"
        exit 1
    fi
    
    print_success "All required PHP extensions are installed"
}

check_composer() {
    print_info "Checking Composer installation..."
    
    if ! command -v composer &> /dev/null; then
        print_warning "Composer not found, installing..."
        
        cd /tmp
        php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
        php composer-setup.php --quiet
        mv composer.phar /usr/local/bin/composer
        rm composer-setup.php
        
        print_success "Composer installed"
    else
        print_success "Composer found"
    fi
}

check_zabbix_agent() {
    print_info "Checking Zabbix Agent installation..."
    
    if [ ! -f "$ZABBIX_AGENT_CONF" ]; then
        print_error "Zabbix Agent configuration not found: $ZABBIX_AGENT_CONF"
        print_info "Please install Zabbix Agent 2"
        exit 1
    fi
    
    print_success "Zabbix Agent configuration found"
}

install_dependencies() {
    print_info "Installing Composer dependencies..."
    
    cd "$PROJECT_DIR"
    
    if [ ! -f "composer.json" ]; then
        print_error "composer.json not found in $PROJECT_DIR"
        exit 1
    fi
    
    composer install --no-dev --optimize-autoloader
    
    print_success "Dependencies installed"
}

create_directories() {
    print_info "Creating directories..."
    
    # Create installation directory
    mkdir -p "$INSTALL_DIR"
    
    # Create logs directory
    mkdir -p "${PROJECT_DIR}/logs"
    chmod 755 "${PROJECT_DIR}/logs"
    
    # Create Zabbix include directory if it doesn't exist
    mkdir -p "$ZABBIX_INCLUDE_DIR"
    
    print_success "Directories created"
}

install_files() {
    print_info "Installing files to $INSTALL_DIR..."
    
    # Copy source files
    cp -r "${PROJECT_DIR}/src" "$INSTALL_DIR/"
    cp -r "${PROJECT_DIR}/vendor" "$INSTALL_DIR/"
    
    # Copy configuration template if config doesn't exist
    if [ ! -f "${INSTALL_DIR}/config/config.php" ]; then
        mkdir -p "${INSTALL_DIR}/config"
        cp "${PROJECT_DIR}/config/config.example.php" "${INSTALL_DIR}/config/"
        print_warning "Configuration template copied to ${INSTALL_DIR}/config/"
        print_warning "Please edit ${INSTALL_DIR}/config/config.php with your ISPConfig credentials"
    fi
    
    # Set ownership
    chown -R zabbix:zabbix "$INSTALL_DIR"
    chmod -R 755 "$INSTALL_DIR"
    
    # Secure config directory
    chmod 750 "${INSTALL_DIR}/config"
    
    print_success "Files installed"
}

configure_zabbix_userparameters() {
    print_info "Configuring Zabbix UserParameters..."
    
    cat > "$USERPARAMS_FILE" << 'EOF'
###############################################################################
# ISPConfig Zabbix Monitoring - UserParameter Configuration
###############################################################################

# Website Discovery
UserParameter=ispconfig.websites.discovery,/usr/bin/php /usr/local/lib/ispconfig-zabbix-monitoring/src/autodiscovery/websites.php

# Website Metrics
UserParameter=ispconfig.website.active[*],/usr/bin/php /usr/local/lib/ispconfig-zabbix-monitoring/src/keys/websites.php $1 active
UserParameter=ispconfig.website.domain[*],/usr/bin/php /usr/local/lib/ispconfig-zabbix-monitoring/src/keys/websites.php $1 domain
UserParameter=ispconfig.website.server_id[*],/usr/bin/php /usr/local/lib/ispconfig-zabbix-monitoring/src/keys/websites.php $1 server_id
UserParameter=ispconfig.website.document_root[*],/usr/bin/php /usr/local/lib/ispconfig-zabbix-monitoring/src/keys/websites.php $1 document_root
UserParameter=ispconfig.website.php_version[*],/usr/bin/php /usr/local/lib/ispconfig-zabbix-monitoring/src/keys/websites.php $1 php_version
UserParameter=ispconfig.website.ssl_enabled[*],/usr/bin/php /usr/local/lib/ispconfig-zabbix-monitoring/src/keys/websites.php $1 ssl_enabled
UserParameter=ispconfig.website.disk_usage[*],/usr/bin/php /usr/local/lib/ispconfig-zabbix-monitoring/src/keys/websites.php $1 disk_usage
UserParameter=ispconfig.website.hd_quota[*],/usr/bin/php /usr/local/lib/ispconfig-zabbix-monitoring/src/keys/websites.php $1 hd_quota
EOF

    chmod 644 "$USERPARAMS_FILE"
    print_success "Zabbix UserParameters configured"
}

restart_zabbix_agent() {
    print_info "Restarting Zabbix Agent..."
    
    if systemctl is-active --quiet zabbix-agent2; then
        systemctl restart zabbix-agent2
        print_success "Zabbix Agent 2 restarted"
    elif systemctl is-active --quiet zabbix-agent; then
        systemctl restart zabbix-agent
        print_success "Zabbix Agent restarted"
    else
        print_warning "Could not detect running Zabbix Agent service"
        print_info "Please restart Zabbix Agent manually"
    fi
}

test_installation() {
    print_info "Testing installation..."
    
    # Test autodiscovery script
    print_info "Testing website discovery..."
    if sudo -u zabbix php "${INSTALL_DIR}/src/autodiscovery/websites.php" > /dev/null 2>&1; then
        print_success "Website discovery script works"
    else
        print_warning "Website discovery test failed - check configuration"
    fi
}

###############################################################################
# Main Installation
###############################################################################

echo ""
echo "=========================================="
echo "ISPConfig Zabbix Monitoring Installation"
echo "=========================================="
echo ""

check_root
check_php
check_composer
check_zabbix_agent

echo ""
print_info "Starting installation..."
echo ""

install_dependencies
create_directories
install_files
configure_zabbix_userparameters
restart_zabbix_agent

echo ""
print_success "Installation completed successfully!"
echo ""

print_info "Next steps:"
echo "  1. Copy configuration file:"
echo "     cp ${INSTALL_DIR}/config/config.example.php ${INSTALL_DIR}/config/config.php"
echo ""
echo "  2. Edit configuration with your ISPConfig credentials:"
echo "     nano ${INSTALL_DIR}/config/config.php"
echo ""
echo "  3. Test the scripts:"
echo "     sudo -u zabbix php ${INSTALL_DIR}/src/autodiscovery/websites.php"
echo ""
echo "  4. Import Zabbix template:"
echo "     ${PROJECT_DIR}/templates/websites/template_ispconfig_websites.yaml"
echo ""
echo "  5. Assign template to your host in Zabbix"
echo ""

test_installation

echo ""
print_success "Setup complete!"
echo ""