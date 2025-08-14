#!/bin/bash

# PayPerCrawl Enterprise v6.0.0 - Deployment Script
# 
# This script automates the deployment and setup of PayPerCrawl Enterprise
# for WordPress installations. It handles plugin installation, configuration,
# and initial setup.
#
# Usage: ./deploy.sh [environment] [options]
# 
# @package PayPerCrawl_Enterprise
# @version 6.0.0

set -e  # Exit on any error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
PLUGIN_NAME="paypercrawl-enterprise"
PLUGIN_VERSION="6.0.0"
WP_CLI_MIN_VERSION="2.5.0"
PHP_MIN_VERSION="7.4.0"

# Environment settings
ENVIRONMENT=${1:-"development"}
WORDPRESS_PATH=${WORDPRESS_PATH:-"/var/www/html"}
PLUGIN_PATH="$WORDPRESS_PATH/wp-content/plugins/$PLUGIN_NAME"

# Logging
LOG_FILE="/tmp/paypercrawl-deploy.log"
exec 1> >(tee -a "$LOG_FILE")
exec 2> >(tee -a "$LOG_FILE" >&2)

# Helper functions
print_banner() {
    echo -e "${BLUE}"
    echo "╔════════════════════════════════════════════════════════════════╗"
    echo "║                PayPerCrawl Enterprise v6.0.0                  ║"
    echo "║                    Deployment Script                          ║"
    echo "╚════════════════════════════════════════════════════════════════╝"
    echo -e "${NC}"
}

print_step() {
    echo -e "${BLUE}[STEP]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Check prerequisites
check_requirements() {
    print_step "Checking system requirements..."
    
    # Check if running as appropriate user
    if [[ $EUID -eq 0 ]]; then
        print_warning "Running as root. Consider using www-data or appropriate web user."
    fi
    
    # Check PHP version
    if command -v php >/dev/null 2>&1; then
        PHP_VERSION=$(php -r "echo PHP_VERSION;")
        if php -r "exit(version_compare(PHP_VERSION, '$PHP_MIN_VERSION', '<') ? 1 : 0);"; then
            print_error "PHP $PHP_MIN_VERSION or higher required. Found: $PHP_VERSION"
            exit 1
        fi
        print_success "PHP version: $PHP_VERSION ✓"
    else
        print_error "PHP not found in PATH"
        exit 1
    fi
    
    # Check WP-CLI
    if command -v wp >/dev/null 2>&1; then
        WP_CLI_VERSION=$(wp --version | grep -oP 'WP-CLI \K[0-9.]+')
        print_success "WP-CLI version: $WP_CLI_VERSION ✓"
    else
        print_error "WP-CLI not found. Please install WP-CLI first."
        exit 1
    fi
    
    # Check WordPress installation
    if [[ ! -f "$WORDPRESS_PATH/wp-config.php" ]]; then
        print_error "WordPress not found at $WORDPRESS_PATH"
        print_error "Set WORDPRESS_PATH environment variable or install WordPress"
        exit 1
    fi
    print_success "WordPress installation found ✓"
    
    # Check database connectivity
    if wp --path="$WORDPRESS_PATH" db check >/dev/null 2>&1; then
        print_success "Database connection ✓"
    else
        print_error "Cannot connect to WordPress database"
        exit 1
    fi
}

# Backup existing installation
backup_existing() {
    print_step "Creating backup..."
    
    if [[ -d "$PLUGIN_PATH" ]]; then
        BACKUP_DIR="/tmp/paypercrawl-backup-$(date +%Y%m%d-%H%M%S)"
        mkdir -p "$BACKUP_DIR"
        
        # Backup plugin files
        cp -r "$PLUGIN_PATH" "$BACKUP_DIR/"
        print_success "Plugin files backed up to: $BACKUP_DIR"
        
        # Backup database tables
        wp --path="$WORDPRESS_PATH" db export "$BACKUP_DIR/database-backup.sql" \
            --tables=$(wp --path="$WORDPRESS_PATH" db tables --format=csv | grep paypercrawl | tr '\n' ',')
        print_success "Database tables backed up"
        
        echo "BACKUP_DIR=$BACKUP_DIR" >> "$LOG_FILE"
    else
        print_success "No existing installation found, skipping backup"
    fi
}

# Install plugin files
install_plugin() {
    print_step "Installing plugin files..."
    
    # Create plugin directory
    mkdir -p "$PLUGIN_PATH"
    
    # Copy plugin files
    SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
    
    if [[ -f "$SCRIPT_DIR/pay-per-crawl-enterprise.php" ]]; then
        # Copy from current directory (development)
        cp -r "$SCRIPT_DIR"/* "$PLUGIN_PATH/"
    else
        # Download from repository (production)
        print_step "Downloading plugin from repository..."
        TEMP_DIR=$(mktemp -d)
        curl -L "https://github.com/paypercrawl/enterprise/archive/v$PLUGIN_VERSION.tar.gz" | \
            tar -xz -C "$TEMP_DIR" --strip-components=1
        cp -r "$TEMP_DIR"/* "$PLUGIN_PATH/"
        rm -rf "$TEMP_DIR"
    fi
    
    # Set proper permissions
    chown -R www-data:www-data "$PLUGIN_PATH" 2>/dev/null || true
    chmod -R 755 "$PLUGIN_PATH"
    chmod 644 "$PLUGIN_PATH"/*.php "$PLUGIN_PATH"/includes/*.php 2>/dev/null || true
    
    print_success "Plugin files installed"
}

# Install composer dependencies
install_dependencies() {
    print_step "Installing dependencies..."
    
    if [[ -f "$PLUGIN_PATH/composer.json" ]]; then
        cd "$PLUGIN_PATH"
        
        if command -v composer >/dev/null 2>&1; then
            composer install --no-dev --optimize-autoloader
            print_success "Composer dependencies installed"
        else
            print_warning "Composer not found, skipping PHP dependencies"
        fi
    fi
    
    # Check for npm dependencies (development)
    if [[ -f "$PLUGIN_PATH/package.json" && "$ENVIRONMENT" == "development" ]]; then
        if command -v npm >/dev/null 2>&1; then
            cd "$PLUGIN_PATH"
            npm install --production
            npm run build 2>/dev/null || true
            print_success "NPM dependencies installed"
        else
            print_warning "NPM not found, skipping JS dependencies"
        fi
    fi
}

# Activate plugin and setup database
setup_plugin() {
    print_step "Setting up plugin..."
    
    # Activate plugin
    wp --path="$WORDPRESS_PATH" plugin activate "$PLUGIN_NAME"
    print_success "Plugin activated"
    
    # Create database tables
    wp --path="$WORDPRESS_PATH" eval "
        if (class_exists('PayPerCrawl_Enterprise')) {
            \$plugin = PayPerCrawl_Enterprise::get_instance();
            \$plugin->create_tables();
            echo 'Database tables created\n';
        }
    "
    
    # Set default options
    wp --path="$WORDPRESS_PATH" option add paypercrawl_version "$PLUGIN_VERSION"
    wp --path="$WORDPRESS_PATH" option add paypercrawl_installed_date "$(date -u +%Y-%m-%d\ %H:%M:%S)"
    
    # Create default configuration
    wp --path="$WORDPRESS_PATH" eval "
        \$default_config = [
            'detection_enabled' => true,
            'pricing_premium' => 0.10,
            'pricing_standard' => 0.05,
            'pricing_emerging' => 0.02,
            'confidence_threshold' => 70,
            'auto_refresh_interval' => 30,
            'cache_enabled' => true,
            'logging_enabled' => true
        ];
        
        foreach (\$default_config as \$key => \$value) {
            add_option('paypercrawl_' . \$key, \$value);
        }
        echo 'Default configuration applied\n';
    "
    
    print_success "Plugin setup completed"
}

# Configure Cloudflare (if credentials provided)
setup_cloudflare() {
    if [[ -n "$CLOUDFLARE_API_TOKEN" && -n "$CLOUDFLARE_ZONE_ID" ]]; then
        print_step "Configuring Cloudflare integration..."
        
        wp --path="$WORDPRESS_PATH" option update paypercrawl_cloudflare_api_token "$CLOUDFLARE_API_TOKEN"
        wp --path="$WORDPRESS_PATH" option update paypercrawl_cloudflare_zone_id "$CLOUDFLARE_ZONE_ID"
        wp --path="$WORDPRESS_PATH" option update paypercrawl_cloudflare_enabled true
        
        # Test Cloudflare connection
        wp --path="$WORDPRESS_PATH" eval "
            if (class_exists('PayPerCrawl_Cloudflare_Integration')) {
                \$cf = new PayPerCrawl_Cloudflare_Integration();
                if (\$cf->test_connection()) {
                    echo 'Cloudflare connection successful\n';
                } else {
                    echo 'Cloudflare connection failed\n';
                }
            }
        "
        
        print_success "Cloudflare integration configured"
    else
        print_warning "Cloudflare credentials not provided, skipping integration"
    fi
}

# Setup API configuration
setup_api() {
    if [[ -n "$PAYPERCRAWL_API_KEY" && -n "$PAYPERCRAWL_SECRET_KEY" ]]; then
        print_step "Configuring API credentials..."
        
        wp --path="$WORDPRESS_PATH" option update paypercrawl_api_key "$PAYPERCRAWL_API_KEY"
        wp --path="$WORDPRESS_PATH" option update paypercrawl_secret_key "$PAYPERCRAWL_SECRET_KEY"
        wp --path="$WORDPRESS_PATH" option update paypercrawl_api_enabled true
        
        # Test API connection
        wp --path="$WORDPRESS_PATH" eval "
            if (class_exists('PayPerCrawl_API_Client')) {
                \$api = new PayPerCrawl_API_Client();
                if (\$api->test_connection()) {
                    echo 'API connection successful\n';
                } else {
                    echo 'API connection failed\n';
                }
            }
        "
        
        print_success "API credentials configured"
    else
        print_warning "API credentials not provided, manual configuration required"
    fi
}

# Performance optimization
optimize_performance() {
    print_step "Optimizing performance..."
    
    # Enable object caching if available
    if wp --path="$WORDPRESS_PATH" cache status >/dev/null 2>&1; then
        wp --path="$WORDPRESS_PATH" cache flush
        print_success "Cache flushed"
    fi
    
    # Create .htaccess rules for static assets
    cat >> "$PLUGIN_PATH/.htaccess" << EOF
# PayPerCrawl Enterprise - Static Asset Optimization
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType text/css "access plus 1 month"
    ExpiresByType application/javascript "access plus 1 month"
    ExpiresByType image/png "access plus 1 month"
    ExpiresByType image/jpg "access plus 1 month"
    ExpiresByType image/jpeg "access plus 1 month"
    ExpiresByType image/gif "access plus 1 month"
    ExpiresByType image/svg+xml "access plus 1 month"
</IfModule>

<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/css
    AddOutputFilterByType DEFLATE application/javascript
    AddOutputFilterByType DEFLATE application/json
</IfModule>
EOF
    
    print_success "Performance optimizations applied"
}

# Run health checks
health_check() {
    print_step "Running health checks..."
    
    # Check plugin status
    if wp --path="$WORDPRESS_PATH" plugin is-active "$PLUGIN_NAME"; then
        print_success "Plugin is active ✓"
    else
        print_error "Plugin is not active ✗"
        return 1
    fi
    
    # Check database tables
    TABLES=$(wp --path="$WORDPRESS_PATH" db tables --format=csv | grep paypercrawl | wc -l)
    if [[ $TABLES -ge 4 ]]; then
        print_success "Database tables created ✓"
    else
        print_error "Database tables missing ✗"
        return 1
    fi
    
    # Check file permissions
    if [[ -r "$PLUGIN_PATH/pay-per-crawl-enterprise.php" ]]; then
        print_success "File permissions correct ✓"
    else
        print_error "File permission issues ✗"
        return 1
    fi
    
    # Check PHP requirements
    wp --path="$WORDPRESS_PATH" eval "
        \$required_extensions = ['json', 'curl', 'openssl'];
        \$missing = [];
        
        foreach (\$required_extensions as \$ext) {
            if (!extension_loaded(\$ext)) {
                \$missing[] = \$ext;
            }
        }
        
        if (empty(\$missing)) {
            echo 'PHP extensions: OK\n';
        } else {
            echo 'Missing PHP extensions: ' . implode(', ', \$missing) . '\n';
            exit(1);
        }
    "
    
    print_success "All health checks passed ✓"
}

# Generate deployment report
generate_report() {
    print_step "Generating deployment report..."
    
    REPORT_FILE="/tmp/paypercrawl-deployment-report-$(date +%Y%m%d-%H%M%S).txt"
    
    cat > "$REPORT_FILE" << EOF
PayPerCrawl Enterprise v$PLUGIN_VERSION - Deployment Report
Generated: $(date)
Environment: $ENVIRONMENT

SYSTEM INFORMATION:
- WordPress Path: $WORDPRESS_PATH
- Plugin Path: $PLUGIN_PATH
- PHP Version: $(php -r "echo PHP_VERSION;")
- WordPress Version: $(wp --path="$WORDPRESS_PATH" core version)
- Database: $(wp --path="$WORDPRESS_PATH" db version)

PLUGIN STATUS:
- Version: $PLUGIN_VERSION
- Status: $(wp --path="$WORDPRESS_PATH" plugin status "$PLUGIN_NAME" | head -1)
- Database Tables: $(wp --path="$WORDPRESS_PATH" db tables --format=csv | grep paypercrawl | wc -l)

CONFIGURATION:
- API Enabled: $(wp --path="$WORDPRESS_PATH" option get paypercrawl_api_enabled 2>/dev/null || echo "false")
- Cloudflare Enabled: $(wp --path="$WORDPRESS_PATH" option get paypercrawl_cloudflare_enabled 2>/dev/null || echo "false")
- Detection Enabled: $(wp --path="$WORDPRESS_PATH" option get paypercrawl_detection_enabled 2>/dev/null || echo "true")
- Cache Enabled: $(wp --path="$WORDPRESS_PATH" option get paypercrawl_cache_enabled 2>/dev/null || echo "true")

NEXT STEPS:
1. Access admin dashboard: $(wp --path="$WORDPRESS_PATH" option get siteurl)/wp-admin/admin.php?page=paypercrawl-dashboard
2. Configure API credentials in Settings
3. Test bot detection functionality
4. Monitor logs for any issues

For support, visit: https://support.paypercrawl.com
Documentation: https://docs.paypercrawl.com
EOF
    
    print_success "Deployment report generated: $REPORT_FILE"
    echo -e "${BLUE}Report content:${NC}"
    cat "$REPORT_FILE"
}

# Cleanup function
cleanup() {
    print_step "Cleaning up temporary files..."
    # Clean any temp files if needed
    print_success "Cleanup completed"
}

# Main deployment function
main() {
    print_banner
    
    echo "Starting PayPerCrawl Enterprise v$PLUGIN_VERSION deployment..."
    echo "Environment: $ENVIRONMENT"
    echo "WordPress Path: $WORDPRESS_PATH"
    echo "Log File: $LOG_FILE"
    echo ""
    
    # Set trap for cleanup
    trap cleanup EXIT
    
    # Run deployment steps
    check_requirements
    backup_existing
    install_plugin
    install_dependencies
    setup_plugin
    setup_cloudflare
    setup_api
    optimize_performance
    health_check
    generate_report
    
    print_success "PayPerCrawl Enterprise v$PLUGIN_VERSION deployed successfully!"
    echo ""
    echo -e "${GREEN}🎉 Deployment Complete!${NC}"
    echo ""
    echo "Access your dashboard at: $(wp --path="$WORDPRESS_PATH" option get siteurl)/wp-admin/admin.php?page=paypercrawl-dashboard"
    echo ""
    echo "Next steps:"
    echo "1. Configure API credentials in Settings"
    echo "2. Test bot detection functionality"
    echo "3. Monitor the dashboard for detections"
    echo ""
    echo "For support: enterprise@paypercrawl.com"
}

# Run main function
main "$@"
