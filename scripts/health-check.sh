#!/bin/bash

###############################################################################
# Service Health Check Script
# Monitors critical LAMP stack services and sends alerts if issues detected
# Usage: ./health-check.sh or run via cron every 5-15 minutes
###############################################################################

# Load environment variables if .env file exists
if [ -f "/var/www/html/.env" ]; then
    export $(cat /var/www/html/.env | grep -v '^#' | xargs)
fi

# Configuration
LOG_FILE="/var/log/health-check.log"
ALERT_EMAIL="${ALERT_EMAIL}"
SERVICES=("apache2" "mysql")

# Color codes for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Function to log messages
log_message() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1" | tee -a "$LOG_FILE"
}

# Function to send alert
send_alert() {
    local message=$1
    log_message "ALERT: $message"
    
    if [ -n "$ALERT_EMAIL" ] && command -v mail &> /dev/null; then
        echo "$message" | mail -s "LAMP Stack Alert - $(hostname)" "$ALERT_EMAIL"
    fi
}

# Check if a service is running
check_service() {
    local service=$1
    
    if systemctl is-active --quiet "$service"; then
        echo -e "${GREEN}✓${NC} $service is running"
        log_message "$service is running"
        return 0
    else
        echo -e "${RED}✗${NC} $service is NOT running"
        send_alert "$service is not running on $(hostname)"
        
        # Attempt to restart the service
        log_message "Attempting to restart $service"
        systemctl restart "$service"
        
        sleep 5
        
        if systemctl is-active --quiet "$service"; then
            log_message "$service restarted successfully"
            send_alert "$service was down but has been restarted successfully"
        else
            log_message "ERROR: Failed to restart $service"
            send_alert "CRITICAL: Failed to restart $service on $(hostname)"
        fi
        
        return 1
    fi
}

# Check Apache configuration
check_apache_config() {
    if apache2ctl configtest &> /dev/null; then
        echo -e "${GREEN}✓${NC} Apache configuration is valid"
        log_message "Apache configuration is valid"
        return 0
    else
        echo -e "${RED}✗${NC} Apache configuration has errors"
        log_message "ERROR: Apache configuration has errors"
        send_alert "Apache configuration errors detected on $(hostname)"
        return 1
    fi
}

# Check MySQL connectivity
check_mysql_connectivity() {
    if [ -n "$DB_PASSWORD" ]; then
        mysql -h "${DB_HOST:-localhost}" -u "${DB_USER:-root}" -p"$DB_PASSWORD" \
            -e "SELECT 1;" &> /dev/null
    else
        mysql -h "${DB_HOST:-localhost}" -u "${DB_USER:-root}" \
            -e "SELECT 1;" &> /dev/null
    fi
    
    if [ $? -eq 0 ]; then
        echo -e "${GREEN}✓${NC} MySQL is accessible"
        log_message "MySQL is accessible"
        return 0
    else
        echo -e "${RED}✗${NC} MySQL is NOT accessible"
        log_message "ERROR: MySQL is not accessible"
        send_alert "MySQL connection failed on $(hostname)"
        return 1
    fi
}

# Check disk space
check_disk_space() {
    local threshold=80
    local usage=$(df -h / | awk 'NR==2 {print $5}' | sed 's/%//')
    
    if [ "$usage" -lt "$threshold" ]; then
        echo -e "${GREEN}✓${NC} Disk space is healthy (${usage}% used)"
        log_message "Disk space: ${usage}% used"
        return 0
    else
        echo -e "${YELLOW}⚠${NC} Disk space is high (${usage}% used)"
        log_message "WARNING: Disk space at ${usage}%"
        
        if [ "$usage" -gt 90 ]; then
            send_alert "CRITICAL: Disk space at ${usage}% on $(hostname)"
        else
            send_alert "WARNING: Disk space at ${usage}% on $(hostname)"
        fi
        
        return 1
    fi
}

# Check memory usage
check_memory() {
    local threshold=80
    local usage=$(free | grep Mem | awk '{print int($3/$2 * 100)}')
    
    if [ "$usage" -lt "$threshold" ]; then
        echo -e "${GREEN}✓${NC} Memory usage is healthy (${usage}% used)"
        log_message "Memory usage: ${usage}%"
        return 0
    else
        echo -e "${YELLOW}⚠${NC} Memory usage is high (${usage}% used)"
        log_message "WARNING: Memory usage at ${usage}%"
        
        if [ "$usage" -gt 90 ]; then
            send_alert "CRITICAL: Memory usage at ${usage}% on $(hostname)"
        fi
        
        return 1
    fi
}

# Check if web server is responding
check_web_response() {
    local url="${APP_URL:-http://localhost}"
    
    if curl -s -o /dev/null -w "%{http_code}" "$url" | grep -q "200\|301\|302"; then
        echo -e "${GREEN}✓${NC} Web server is responding"
        log_message "Web server responding at $url"
        return 0
    else
        echo -e "${RED}✗${NC} Web server is NOT responding"
        log_message "ERROR: Web server not responding at $url"
        send_alert "Web server not responding at $url on $(hostname)"
        return 1
    fi
}

# Main execution
echo "========================================="
echo "  LAMP Stack Health Check"
echo "  $(date '+%Y-%m-%d %H:%M:%S')"
echo "========================================="
echo ""

log_message "=== Health Check Started ==="

# Track if any checks failed
failed_checks=0

# Check all services
for service in "${SERVICES[@]}"; do
    check_service "$service" || ((failed_checks++))
done

echo ""

# Additional checks
check_apache_config || ((failed_checks++))
check_mysql_connectivity || ((failed_checks++))
check_disk_space || ((failed_checks++))
check_memory || ((failed_checks++))
check_web_response || ((failed_checks++))

echo ""
echo "========================================="

if [ $failed_checks -eq 0 ]; then
    echo -e "${GREEN}All checks passed!${NC}"
    log_message "=== Health Check Completed: All checks passed ==="
else
    echo -e "${RED}$failed_checks check(s) failed!${NC}"
    log_message "=== Health Check Completed: $failed_checks check(s) failed ==="
fi

echo "========================================="

exit $failed_checks
