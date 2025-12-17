# Troubleshooting Guide

This guide helps you diagnose and fix common issues with your LAMP stack deployment.

## Table of Contents
- [Apache Issues](#apache-issues)
- [MySQL Issues](#mysql-issues)
- [PHP Issues](#php-issues)
- [SSL/HTTPS Issues](#sslhttps-issues)
- [Application Issues](#application-issues)
- [Performance Issues](#performance-issues)
- [Security Issues](#security-issues)
- [Connectivity Issues](#connectivity-issues)
- [Common Error Messages](#common-error-messages)
- [Debug Commands](#debug-commands)
- [Log Locations](#log-locations)

## Apache Issues

### Apache Won't Start

**Symptoms**: `systemctl start apache2` fails or Apache crashes immediately

**Diagnosis**:
```bash
# Check Apache status
sudo systemctl status apache2

# Test configuration
sudo apache2ctl configtest

# Check error logs
sudo tail -f /var/log/apache2/error.log
```

**Common Causes & Solutions**:

1. **Configuration Syntax Error**
   ```bash
   sudo apache2ctl configtest
   # Fix the file mentioned in the error
   sudo systemctl restart apache2
   ```

2. **Port Already in Use**
   ```bash
   sudo netstat -tlnp | grep :80
   # If another process is using port 80, stop it or change Apache port
   ```

3. **Insufficient Permissions**
   ```bash
   sudo chown -R www-data:www-data /var/www/yourdomain
   sudo chmod -R 755 /var/www/yourdomain
   ```

### 403 Forbidden Error

**Causes**:
- Incorrect file permissions
- Missing index file
- Directory listing disabled

**Solutions**:
```bash
# Check permissions
ls -la /var/www/yourdomain

# Set correct permissions
sudo chown -R www-data:www-data /var/www/yourdomain
sudo find /var/www/yourdomain -type d -exec chmod 755 {} \;
sudo find /var/www/yourdomain -type f -exec chmod 644 {} \;

# Ensure index file exists
ls /var/www/yourdomain/public/index.php

# Check Apache virtual host configuration
sudo cat /etc/apache2/sites-available/yourdomain.conf
```

### 500 Internal Server Error

**Diagnosis**:
```bash
# Check Apache error log
sudo tail -50 /var/log/apache2/error.log

# Check PHP error log
sudo tail -50 /var/log/php/error.log

# Enable PHP error display temporarily
# Edit php.ini
display_errors = On
sudo systemctl restart apache2
```

**Common Causes**:
- PHP syntax error
- Missing PHP modules
- .htaccess errors
- Memory limit exceeded
- File permission issues

### Virtual Host Not Working

**Check**:
```bash
# List enabled sites
ls -la /etc/apache2/sites-enabled/

# Enable your site
sudo a2ensite yourdomain.conf

# Disable default site
sudo a2dissite 000-default.conf

# Restart Apache
sudo systemctl restart apache2
```

## MySQL Issues

### Can't Connect to MySQL

**Symptoms**: "Can't connect to MySQL server" or "Access denied"

**Diagnosis**:
```bash
# Check if MySQL is running
sudo systemctl status mysql

# Try connecting
sudo mysql

# Check MySQL error log
sudo tail -50 /var/log/mysql/error.log
```

**Solutions**:

1. **MySQL Not Running**
   ```bash
   sudo systemctl start mysql
   sudo systemctl enable mysql
   ```

2. **Wrong Credentials**
   ```bash
   # Reset password if needed
   sudo mysql
   ALTER USER 'lamp_user'@'localhost' IDENTIFIED BY 'new_password';
   FLUSH PRIVILEGES;
   ```

3. **User Doesn't Have Permissions**
   ```bash
   sudo mysql
   GRANT ALL PRIVILEGES ON lamp_app.* TO 'lamp_user'@'localhost';
   FLUSH PRIVILEGES;
   ```

### MySQL Performance Issues

**Diagnosis**:
```bash
# Check slow queries
sudo tail /var/log/mysql/mysql-slow.log

# Check MySQL status
sudo mysql -e "SHOW PROCESSLIST;"
sudo mysql -e "SHOW STATUS;"
```

**Solutions**:
```bash
# Optimize tables
sudo mysql lamp_app -e "OPTIMIZE TABLE tasks;"

# Check and repair tables
sudo mysql lamp_app -e "CHECK TABLE tasks;"
sudo mysql lamp_app -e "REPAIR TABLE tasks;"

# Review and optimize my.cnf settings
sudo nano /etc/mysql/my.cnf
```

### Database Connection Error in PHP

**Check**:
```bash
# Verify .env file exists and is readable
ls -la /var/www/yourdomain/.env

# Test database connection
php -r "new PDO('mysql:host=localhost;dbname=lamp_app', 'lamp_user', 'password');"

# Check PHP MySQL extensions
php -m | grep -i mysql
```

## PHP Issues

### PHP Not Processing (Showing Code)

**Symptoms**: Browser displays PHP code instead of executing it

**Solutions**:
```bash
# Check if PHP module is enabled
sudo a2enmod php8.3  # or php8.2

# Restart Apache
sudo systemctl restart apache2

# Verify PHP is installed
php -v

# Check Apache configuration
grep -r "php" /etc/apache2/
```

### PHP Memory Limit Exceeded

**Error**: "Allowed memory size of X bytes exhausted"

**Solution**:
```bash
# Edit php.ini
sudo nano /etc/php/8.3/apache2/php.ini

# Find and increase
memory_limit = 256M

# Restart Apache
sudo systemctl restart apache2
```

### PHP Module Missing

**Diagnosis**:
```bash
# List installed modules
php -m

# Check for specific module
php -m | grep mysqli
```

**Solution**:
```bash
# Install missing module
sudo apt install php8.3-mysqli php8.3-mbstring php8.3-curl

# Restart Apache
sudo systemctl restart apache2
```

### PHP Session Issues

**Symptoms**: Sessions not persisting, "Permission denied" errors

**Solution**:
```bash
# Check session directory
ls -la /var/lib/php/sessions

# Fix permissions
sudo chown -R www-data:www-data /var/lib/php/sessions
sudo chmod 1733 /var/lib/php/sessions
```

## SSL/HTTPS Issues

### Certificate Not Valid

**Diagnosis**:
```bash
# Check certificate
sudo certbot certificates

# Test certificate
sudo openssl x509 -in /etc/letsencrypt/live/yourdomain.com/fullchain.pem -text -noout

# Check SSL configuration
sudo apache2ctl -t -D DUMP_VHOSTS
```

**Solution**:
```bash
# Renew certificate manually
sudo certbot renew --dry-run
sudo certbot renew

# Force renewal
sudo certbot renew --force-renewal

# Restart Apache
sudo systemctl restart apache2
```

### SSL Redirect Loop

**Check virtual host configuration**:
```bash
# Ensure you don't have conflicting redirects
sudo nano /etc/apache2/sites-available/yourdomain.conf

# Make sure HTTP virtualhost redirects to HTTPS
# And HTTPS virtualhost doesn't redirect
```

### Mixed Content Warnings

**Cause**: Loading HTTP resources on HTTPS page

**Solution**:
- Update all resource URLs to use HTTPS or relative URLs
- Check database for hardcoded HTTP URLs
- Use protocol-relative URLs: `//example.com/image.jpg`

## Application Issues

### Page Not Found (404)

**Check**:
```bash
# Verify file exists
ls -la /var/www/yourdomain/public/index.php

# Check Apache DocumentRoot
grep DocumentRoot /etc/apache2/sites-enabled/yourdomain.conf

# Check .htaccess if using URL rewriting
cat /var/www/yourdomain/public/.htaccess

# Enable rewrite module
sudo a2enmod rewrite
sudo systemctl restart apache2
```

### Database Connection Failed

**Diagnosis**:
```bash
# Check .env file
cat /var/www/yourdomain/.env

# Verify database exists
sudo mysql -e "SHOW DATABASES;"

# Verify user exists
sudo mysql -e "SELECT User, Host FROM mysql.user;"

# Test connection
php -r "try { \$pdo = new PDO('mysql:host=localhost;dbname=lamp_app', 'lamp_user', 'your_password'); echo 'Connected!'; } catch(Exception \$e) { echo \$e->getMessage(); }"
```

### Session Errors

**Solutions**:
```bash
# Create session directory if missing
sudo mkdir -p /var/lib/php/sessions
sudo chown www-data:www-data /var/lib/php/sessions
sudo chmod 1733 /var/lib/php/sessions

# Check session settings in php.ini
grep session.save_path /etc/php/8.3/apache2/php.ini
```

## Performance Issues

### Slow Page Loading

**Diagnosis**:
```bash
# Check server resources
htop
free -h
df -h

# Check Apache status
sudo apache2ctl status

# Check slow query log
sudo tail /var/log/mysql/mysql-slow.log

# Enable PHP slow log
# In php.ini:
# slowlog = /var/log/php-fpm-slow.log
# request_slowlog_timeout = 5s
```

**Solutions**:

1. **Enable OPcache**
   ```bash
   # Check if enabled
   php -i | grep opcache
   
   # Enable in php.ini
   opcache.enable=1
   opcache.memory_consumption=128
   opcache.max_accelerated_files=10000
   ```

2. **Optimize MySQL**
   ```bash
   # Add indexes to frequently queried columns
   sudo mysql lamp_app -e "CREATE INDEX idx_status ON tasks(status);"
   ```

3. **Enable Compression**
   ```bash
   sudo a2enmod deflate
   sudo systemctl restart apache2
   ```

### High CPU Usage

**Diagnosis**:
```bash
# Check processes
top
htop

# Check Apache processes
ps aux | grep apache2

# Check MySQL processes
mysqladmin processlist
```

**Solutions**:
```bash
# Restart services
sudo systemctl restart apache2
sudo systemctl restart mysql

# Kill hung processes (use with caution)
sudo killall -9 apache2
sudo systemctl start apache2
```

### Out of Disk Space

**Diagnosis**:
```bash
# Check disk usage
df -h

# Find large files
sudo du -ah /var | sort -rh | head -20

# Check log sizes
sudo du -sh /var/log/*
```

**Solutions**:
```bash
# Clean old logs
sudo journalctl --vacuum-time=3d
sudo find /var/log -name "*.log" -type f -mtime +30 -delete

# Clean package cache
sudo apt clean
sudo apt autoremove

# Rotate logs immediately
sudo logrotate -f /etc/logrotate.conf

# Clean old backups
sudo find /var/backups/mysql -name "*.sql.gz" -mtime +7 -delete
```

## Security Issues

### Failed Login Attempts

**Check fail2ban**:
```bash
# Check fail2ban status
sudo fail2ban-client status

# Check SSH jail
sudo fail2ban-client status sshd

# Unban IP if needed
sudo fail2ban-client set sshd unbanip YOUR_IP
```

### Firewall Blocking Access

**Check UFW**:
```bash
# Check firewall status
sudo ufw status verbose

# Check specific port
sudo ufw status | grep 80

# Allow port if needed
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
```

### Suspicious Activity

**Check logs**:
```bash
# Check authentication log
sudo tail -100 /var/log/auth.log

# Check Apache access log for unusual patterns
sudo tail -100 /var/log/apache2/access.log

# Check for failed login attempts
sudo grep "Failed password" /var/log/auth.log

# Check for successful logins
sudo grep "Accepted" /var/log/auth.log
```

## Connectivity Issues

### Can't Access Website

**Checklist**:
```bash
# 1. Check if Apache is running
sudo systemctl status apache2

# 2. Check if listening on correct port
sudo netstat -tlnp | grep :80

# 3. Check firewall
sudo ufw status

# 4. Test locally
curl http://localhost
curl http://127.0.0.1

# 5. Test from server IP
curl http://YOUR_PUBLIC_IP

# 6. Check DNS
nslookup yourdomain.com

# 7. Check AWS Security Group (from AWS Console)
# Ensure port 80 and 443 are open
```

### SSH Connection Issues

**Solutions**:
```bash
# Check SSH service
sudo systemctl status ssh

# Check SSH port
sudo netstat -tlnp | grep ssh

# Check firewall
sudo ufw status | grep 22

# Check fail2ban
sudo fail2ban-client status sshd
```

## Common Error Messages

### "No input file specified"

**Cause**: PHP can't find the requested file

**Solution**:
```bash
# Check DocumentRoot
grep DocumentRoot /etc/apache2/sites-enabled/yourdomain.conf

# Ensure files exist
ls -la /var/www/yourdomain/public/
```

### "Unable to connect to database"

**Solution**:
```bash
# Check MySQL is running
sudo systemctl status mysql

# Verify credentials in .env
cat /var/www/yourdomain/.env

# Test connection
sudo mysql -u lamp_user -p lamp_app
```

### "Permission denied"

**Solution**:
```bash
# Fix file ownership
sudo chown -R www-data:www-data /var/www/yourdomain

# Fix file permissions
sudo find /var/www/yourdomain -type d -exec chmod 755 {} \;
sudo find /var/www/yourdomain -type f -exec chmod 644 {} \;
```

## Debug Commands

### System Information
```bash
# OS version
lsb_release -a

# Kernel version
uname -a

# Disk usage
df -h

# Memory usage
free -h

# CPU info
lscpu
```

### Service Status
```bash
# All LAMP services
sudo systemctl status apache2 mysql

# Detailed service info
sudo systemctl status apache2 -l

# Service logs
sudo journalctl -u apache2 -n 50
sudo journalctl -u mysql -n 50
```

### Network Diagnostics
```bash
# Open ports
sudo netstat -tlnp

# Active connections
sudo netstat -an | grep :80

# DNS lookup
nslookup yourdomain.com
dig yourdomain.com

# Test connectivity
ping -c 4 google.com
curl -I https://yourdomain.com
```

### PHP Diagnostics
```bash
# PHP version
php -v

# PHP modules
php -m

# PHP info (create phpinfo.php)
echo "<?php phpinfo();" | sudo tee /var/www/html/info.php
# Visit: http://your-ip/info.php
# Delete after: sudo rm /var/www/html/info.php

# PHP configuration
php -i | grep -i "configuration file"
```

### MySQL Diagnostics
```bash
# MySQL version
mysql --version

# Check databases
sudo mysql -e "SHOW DATABASES;"

# Check users
sudo mysql -e "SELECT User, Host FROM mysql.user;"

# Check grants
sudo mysql -e "SHOW GRANTS FOR 'lamp_user'@'localhost';"

# Database size
sudo mysql -e "SELECT table_schema AS 'Database', ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS 'Size (MB)' FROM information_schema.TABLES GROUP BY table_schema;"
```

## Log Locations

### Apache Logs
```bash
/var/log/apache2/error.log          # General errors
/var/log/apache2/access.log         # All requests
/var/log/apache2/yourdomain-error.log    # VirtualHost specific
/var/log/apache2/yourdomain-access.log   # VirtualHost specific
```

### MySQL Logs
```bash
/var/log/mysql/error.log            # MySQL errors
/var/log/mysql/mysql-slow.log       # Slow queries
```

### PHP Logs
```bash
/var/log/php/error.log              # PHP errors (if configured)
/var/log/apache2/error.log          # PHP errors via Apache
```

### System Logs
```bash
/var/log/syslog                     # System messages
/var/log/auth.log                   # Authentication attempts
/var/log/kern.log                   # Kernel messages
```

### Security Logs
```bash
/var/log/fail2ban.log               # fail2ban activity
/var/log/ufw.log                    # Firewall logs (if enabled)
```

### View Logs
```bash
# Real-time monitoring
sudo tail -f /var/log/apache2/error.log

# Last 50 lines
sudo tail -50 /var/log/apache2/error.log

# Search in logs
sudo grep "error" /var/log/apache2/error.log

# View with less (pagination)
sudo less /var/log/apache2/error.log
```

## Getting Help

If you're still stuck after trying these solutions:

1. **Check official documentation**:
   - [Apache Documentation](https://httpd.apache.org/docs/2.4/)
   - [MySQL Documentation](https://dev.mysql.com/doc/)
   - [PHP Documentation](https://www.php.net/docs.php)

2. **Search for error messages**: Copy exact error message and search online

3. **Check server logs**: Most issues are explained in log files

4. **Community forums**:
   - Stack Overflow
   - ServerFault
   - Ubuntu Forums

5. **Run health check**:
   ```bash
   sudo /path/to/scripts/health-check.sh
   ```

## Emergency Recovery

### Restore from Backup
```bash
# List available backups
ls -lh /var/backups/mysql/

# Restore database
sudo /path/to/scripts/restore.sh /var/backups/mysql/lamp_app_YYYYMMDD_HHMMSS.sql.gz
```

### Restart All Services
```bash
sudo systemctl restart apache2
sudo systemctl restart mysql
```

### Complete Reset (Last Resort)
```bash
# Stop services
sudo systemctl stop apache2 mysql

# Backup current state
sudo cp -r /var/www/yourdomain /var/www/yourdomain.backup
sudo mysqldump -u root -p --all-databases > /tmp/all-databases-backup.sql

# Start services
sudo systemctl start apache2 mysql

# If needed, restore from backup or redeploy
```
