#!/bin/bash

###############################################################################
# SSL Certificate Setup Script
# Automates SSL certificate installation using Let's Encrypt (Certbot)
# Usage: sudo ./setup-ssl.sh yourdomain.com [www.yourdomain.com]
###############################################################################

# Check if running as root
if [ "$EUID" -ne 0 ]; then 
    echo "Please run this script as root or with sudo"
    exit 1
fi

# Check if domain is provided
if [ $# -lt 1 ]; then
    echo "Usage: $0 yourdomain.com [www.yourdomain.com]"
    echo "Example: $0 example.com www.example.com"
    exit 1
fi

DOMAIN=$1
WWW_DOMAIN=${2:-www.$1}
EMAIL="${ALERT_EMAIL:-webmaster@$DOMAIN}"

echo "========================================="
echo "  SSL/TLS Setup with Let's Encrypt"
echo "========================================="
echo ""
echo "Domain: $DOMAIN"
echo "Additional domain: $WWW_DOMAIN"
echo "Email: $EMAIL"
echo ""

# Install Certbot if not already installed
if ! command -v certbot &> /dev/null; then
    echo "Installing Certbot..."
    apt-get update
    apt-get install -y certbot python3-certbot-apache
    
    if [ $? -ne 0 ]; then
        echo "ERROR: Failed to install Certbot"
        exit 1
    fi
    echo "Certbot installed successfully"
else
    echo "Certbot is already installed"
fi

echo ""

# Verify Apache is running
if ! systemctl is-active --quiet apache2; then
    echo "ERROR: Apache is not running. Please start Apache first."
    exit 1
fi

echo "Verifying Apache configuration..."
if ! apache2ctl configtest &> /dev/null; then
    echo "ERROR: Apache configuration has errors. Please fix them first."
    apache2ctl configtest
    exit 1
fi

echo "Apache configuration is valid"
echo ""

# Enable required Apache modules
echo "Enabling required Apache modules..."
a2enmod ssl
a2enmod rewrite
a2enmod headers
systemctl reload apache2

echo ""

# Obtain SSL certificate
echo "Obtaining SSL certificate from Let's Encrypt..."
echo "This may take a few moments..."
echo ""

certbot --apache \
    -d "$DOMAIN" \
    -d "$WWW_DOMAIN" \
    --non-interactive \
    --agree-tos \
    --email "$EMAIL" \
    --redirect

if [ $? -eq 0 ]; then
    echo ""
    echo "========================================="
    echo "  SSL Certificate Setup Completed!"
    echo "========================================="
    echo ""
    echo "✓ SSL certificate obtained successfully"
    echo "✓ Apache has been configured for HTTPS"
    echo "✓ HTTP to HTTPS redirect enabled"
    echo ""
    echo "Your site is now accessible via:"
    echo "  https://$DOMAIN"
    echo "  https://$WWW_DOMAIN"
    echo ""
    echo "Certificate will auto-renew before expiration."
    echo "Renewal happens automatically via systemd timer."
    echo ""
    echo "To manually renew: sudo certbot renew"
    echo "To test renewal: sudo certbot renew --dry-run"
    echo ""
else
    echo ""
    echo "========================================="
    echo "  SSL Certificate Setup Failed!"
    echo "========================================="
    echo ""
    echo "Common issues:"
    echo "1. Domain DNS not pointing to this server"
    echo "2. Port 80/443 not accessible from internet"
    echo "3. Firewall blocking connections"
    echo "4. Virtual host configuration issues"
    echo ""
    echo "Please check the error messages above and:"
    echo "- Verify DNS A record points to this server's IP"
    echo "- Check firewall rules (UFW and AWS Security Groups)"
    echo "- Verify Apache virtual host is configured correctly"
    echo ""
    exit 1
fi

# Setup automatic renewal (verify it's enabled)
if systemctl list-timers | grep -q certbot; then
    echo "Automatic renewal timer is active"
else
    echo "Setting up automatic renewal..."
    systemctl enable certbot.timer
    systemctl start certbot.timer
fi

echo ""
echo "SSL/TLS setup complete!"
