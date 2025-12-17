# LAMP Stack Deployment - Complete Guide

**Production-Ready LAMP Stack on Ubuntu 24.04 LTS**

This guide walks you through deploying a modern, secure, and optimized LAMP (Linux, Apache, MySQL, PHP) stack with SSL encryption, automated backups, monitoring, and a fully functional sample application.

---

## 📚 Table of Contents

1. [Initial Server Setup](#section-1-initial-server-setup)
2. [Installing Apache Web Server](#section-2-installing-apache-web-server)
3. [Installing MySQL Database](#section-3-installing-mysql-database)
4. [Installing PHP](#section-4-installing-php)
5. [Configuring Virtual Hosts](#section-5-configuring-virtual-hosts)
6. [Setting up SSL/TLS](#section-6-setting-up-ssltls-with-lets-encrypt)
7. [Deploying the Sample Application](#section-7-deploying-the-sample-application)
8. [Setting up Backups and Monitoring](#section-8-setting-up-backups-and-monitoring)
9. [Performance Tuning](#section-9-performance-tuning)
10. [Testing and Verification](#section-10-testing-and-verification)

---

## Prerequisites

Before starting, ensure you have completed:

✅ [Prerequisites Guide](docs/prerequisites.md) - AWS account, EC2 instance, SSH access  
✅ Ubuntu 24.04 LTS server running  
✅ SSH access to your server  
✅ Basic Linux command line knowledge  

**Server Info Used in Examples**:
- **Public IP**: Replace `YOUR_SERVER_IP` with your actual IP
- **Domain**: Replace `yourdomain.com` with your actual domain (optional for Sections 1-5)

---

## Section 1: Initial Server Setup

**Objective**: Secure your server with proper user management, SSH hardening, and firewall configuration.

### Why This Matters

Default server configurations are not secure for production use. This section establishes a strong security foundation before installing any services.

### Step 1.1: Update the System

Always start with the latest security patches:

```bash
# Update package lists
sudo apt update

# Upgrade installed packages
sudo apt upgrade -y

# Check Ubuntu version
lsb_release -a
```

**Expected output**: Should show Ubuntu 24.04 LTS

**Why**: Security updates patch vulnerabilities. Always keep your system updated.

### Step 1.2: Create a Non-Root User

**Security Best Practice**: Never use root for daily operations.

```bash
# Create new user
sudo adduser lampuser

# Add user to sudo group
sudo usermod -aG sudo lampuser

# Verify user creation
id lampuser
```

**Why**: Using root for everything is dangerous. If an attacker compromises root, they have full system control.

### Step 1.3: Setup SSH Key Authentication

**Security Best Practice**: Disable password authentication, use SSH keys only.

On your **local machine**:

```bash
# Generate SSH key pair (if you don't have one)
ssh-keygen -t rsa -b 4096 -C "your_email@example.com"

# Copy public key to server
ssh-copy-id lampuser@YOUR_SERVER_IP
```

On the **server**, test the new connection:

```bash
# From local machine, test SSH with new user
ssh lampuser@YOUR_SERVER_IP

# If successful, you should be logged in without password
```

### Step 1.4: Harden SSH Configuration

**Critical Security Step**: Prevent password-based attacks and root login.

```bash
# Edit SSH configuration
sudo nano /etc/ssh/sshd_config
```

Make these changes:

```bash
# Find and modify these lines (remove # if commented)

PermitRootLogin no                    # Disable root login
PasswordAuthentication no             # Disable password auth
PubkeyAuthentication yes              # Enable key-based auth
ChallengeResponseAuthentication no    # Disable challenge-response
UsePAM yes                            # Keep PAM enabled
X11Forwarding no                      # Disable X11 (not needed)
MaxAuthTries 3                        # Limit auth attempts
ClientAliveInterval 300               # Timeout idle sessions (5 min)
ClientAliveCountMax 2                 # Disconnect after 2 failed keepalives

# Optional: Change SSH port (extra security)
# Port 2222                           # Uncomment and change if desired
```

**Apply changes**:

```bash
# Test configuration
sudo sshd -t

# Restart SSH service
sudo systemctl restart sshd

# Verify SSH is running
sudo systemctl status sshd
```

**⚠️ Important**: Keep your current SSH session open and test the new connection in a new terminal before logging out!

**Why Each Setting**:
- `PermitRootLogin no`: Prevents direct root login (attackers often target root)
- `PasswordAuthentication no`: Keys are much more secure than passwords
- `MaxAuthTries 3`: Limits brute-force attempts
- `ClientAliveInterval`: Automatically closes idle sessions

### Step 1.5: Configure UFW Firewall

**Defense Layer**: UFW (Uncomplicated Firewall) adds a local firewall on top of AWS Security Groups.

```bash
# Check UFW status
sudo ufw status

# Allow SSH (very important - do this first!)
sudo ufw allow 22/tcp
# Or if you changed SSH port: sudo ufw allow 2222/tcp

# Allow HTTP and HTTPS
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp

# Review rules before enabling
sudo ufw show added

# Enable firewall
sudo ufw enable

# Verify status
sudo ufw status verbose
```

**Expected output**:
```
Status: active

To                         Action      From
--                         ------      ----
22/tcp                     ALLOW       Anywhere
80/tcp                     ALLOW       Anywhere
443/tcp                    ALLOW       Anywhere
```

**Why UFW + AWS Security Groups**: Defense in depth - multiple security layers protect better than one.

**⚠️ Critical**: Always allow SSH port BEFORE enabling UFW, or you'll lock yourself out!

### Step 1.6: Install and Configure fail2ban

**Intrusion Prevention**: Automatically ban IPs after failed login attempts.

```bash
# Install fail2ban
sudo apt install fail2ban -y

# Create local configuration
sudo cp /etc/fail2ban/jail.conf /etc/fail2ban/jail.local

# Edit configuration
sudo nano /etc/fail2ban/jail.local
```

Find and modify these settings:

```ini
[DEFAULT]
# Ban hosts for 10 minutes after 5 failed attempts within 10 minutes
bantime = 600
findtime = 600
maxretry = 5

# Email notifications (optional)
destemail = your-email@example.com
sendername = Fail2Ban
action = %(action_mwl)s

[sshd]
enabled = true
port = ssh  # or 2222 if you changed SSH port
logpath = %(sshd_log)s
backend = %(sshd_backend)s
```

**Start fail2ban**:

```bash
# Enable and start fail2ban
sudo systemctl enable fail2ban
sudo systemctl start fail2ban

# Check status
sudo systemctl status fail2ban

# View current bans
sudo fail2ban-client status
sudo fail2ban-client status sshd
```

**Test fail2ban** (optional):

```bash
# From another machine, try failed SSH logins
# After 5 attempts, your IP should be banned

# Check ban list
sudo fail2ban-client status sshd

# Unban if needed
sudo fail2ban-client set sshd unbanip YOUR_IP
```

**Why fail2ban**: Protects against brute-force attacks automatically. An IP making 5 failed SSH attempts gets banned.

### Step 1.7: Configure Automatic Security Updates

**Keep Secure**: Automatically install security updates.

```bash
# Install unattended-upgrades
sudo apt install unattended-upgrades -y

# Enable automatic updates
sudo dpkg-reconfigure -plow unattended-upgrades
# Select "Yes"

# Configure update settings
sudo nano /etc/apt/apt.conf.d/50unattended-upgrades
```

Ensure these lines are uncommented:

```
Unattended-Upgrade::Allowed-Origins {
    "${distro_id}:${distro_codename}-security";
};

Unattended-Upgrade::AutoFixInterruptedDpkg "true";
Unattended-Upgrade::Automatic-Reboot "false";  // Set true for auto-reboot
```

**Why**: Security patches are critical. Automate their installation to stay protected.

### ✅ Section 1 Verification

```bash
# Check firewall status
sudo ufw status verbose

# Verify fail2ban is running
sudo systemctl status fail2ban

# Check SSH configuration
sudo sshd -t

# Verify no root login (should fail)
ssh root@localhost

# Test lampuser SSH key login (from local machine)
ssh lampuser@YOUR_SERVER_IP
```

**Security Checklist**:
- ✅ Non-root user created with sudo access
- ✅ SSH key authentication working
- ✅ Password authentication disabled
- ✅ Root login disabled
- ✅ UFW firewall enabled (ports 22, 80, 443)
- ✅ fail2ban running and monitoring SSH
- ✅ Automatic security updates configured

**You now have a hardened, secure server!** 🛡️

---

## Section 2: Installing Apache Web Server

**Objective**: Install and configure Apache 2.4.x with modern security settings and performance optimizations.

### Why Apache?

Apache is the most popular web server, known for its reliability, flexibility, and extensive module ecosystem. It's perfect for PHP applications and has excellent documentation.

### Step 2.1: Install Apache

```bash
# Install Apache
sudo apt install apache2 -y

# Check version
apache2 -v
```

**Expected output**: Apache/2.4.x (Ubuntu)

**Why**: We want Apache 2.4.x for modern features, security, and HTTP/2 support.

### Step 2.2: Verify Apache is Running

```bash
# Check Apache status
sudo systemctl status apache2

# Enable Apache to start on boot
sudo systemctl enable apache2

# Check which port Apache is listening on
sudo netstat -tlnp | grep apache2
```

**Should show**: Listening on port 80 (HTTP)

### Step 2.3: Test Apache Locally

```bash
# Test from the server itself
curl http://localhost

# Or
curl http://127.0.0.1
```

**Expected**: HTML output with "Apache2 Ubuntu Default Page"

### Step 2.4: Test Apache from Browser

Open your web browser and navigate to:
```
http://YOUR_SERVER_IP
```

**You should see**: Apache2 Ubuntu Default Page

![Apache Default Page](https://via.placeholder.com/800x400.png?text=Apache2+Ubuntu+Default+Page)

**Why test both ways**: Local test verifies Apache works; browser test verifies firewall rules allow HTTP traffic.

### Step 2.5: Configure Apache Security

**Enable security modules**:

```bash
# Enable security modules
sudo a2enmod headers
sudo a2enmod ssl
sudo a2enmod rewrite

# Restart Apache
sudo systemctl restart apache2
```

**Edit main Apache configuration**:

```bash
sudo nano /etc/apache2/conf-available/security.conf
```

Modify these settings for better security:

```apache
# Hide Apache version
ServerTokens Prod
ServerSignature Off

# Prevent clickjacking
Header always set X-Frame-Options "SAMEORIGIN"

# Prevent MIME sniffing
Header always set X-Content-Type-Options "nosniff"

# Enable XSS protection
Header always set X-XSS-Protection "1; mode=block"

# Referrer policy
Header always set Referrer-Policy "strict-origin-when-cross-origin"
```

**Apply configuration**:

```bash
# Enable security configuration
sudo a2enconf security

# Test configuration
sudo apache2ctl configtest

# Should output: Syntax OK

# Restart Apache
sudo systemctl restart apache2
```

### Step 2.6: Configure Apache MPM (Performance)

**MPM (Multi-Processing Module)** determines how Apache handles concurrent connections.

**Check current MPM**:

```bash
apache2ctl -V | grep MPM
```

**For modern servers, Event MPM is recommended**:

```bash
# Disable prefork (default for PHP module)
sudo a2dismod mpm_prefork

# Enable event MPM
sudo a2enmod mpm_event

# Configure event MPM
sudo nano /etc/apache2/mods-available/mpm_event.conf
```

**Recommended settings for 2GB RAM server**:

```apache
<IfModule mpm_event_module>
    StartServers             2
    MinSpareThreads         25
    MaxSpareThreads         75
    ThreadLimit             64
    ThreadsPerChild         25
    MaxRequestWorkers      150
    MaxConnectionsPerChild   0
</IfModule>
```

**Note**: If using mod_php (covered in Section 4), you'll need mpm_prefork instead. For better performance, use PHP-FPM with mpm_event.

### Step 2.7: Enable Useful Apache Modules

```bash
# Compression (faster page loads)
sudo a2enmod deflate

# Caching headers
sudo a2enmod expires

# Environment variables
sudo a2enmod env

# MIME type handling
sudo a2enmod mime

# Directory indexing control
sudo a2enmod dir

# Verify enabled modules
apache2ctl -M
```

### Step 2.8: Configure Compression

Create compression configuration:

```bash
sudo nano /etc/apache2/conf-available/compression.conf
```

Add:

```apache
<IfModule mod_deflate.c>
    # Compress HTML, CSS, JavaScript, Text, XML and fonts
    AddOutputFilterByType DEFLATE application/javascript
    AddOutputFilterByType DEFLATE application/rss+xml
    AddOutputFilterByType DEFLATE application/vnd.ms-fontobject
    AddOutputFilterByType DEFLATE application/x-font
    AddOutputFilterByType DEFLATE application/x-font-opentype
    AddOutputFilterByType DEFLATE application/x-font-otf
    AddOutputFilterByType DEFLATE application/x-font-truetype
    AddOutputFilterByType DEFLATE application/x-font-ttf
    AddOutputFilterByType DEFLATE application/x-javascript
    AddOutputFilterByType DEFLATE application/xhtml+xml
    AddOutputFilterByType DEFLATE application/xml
    AddOutputFilterByType DEFLATE font/opentype
    AddOutputFilterByType DEFLATE font/otf
    AddOutputFilterByType DEFLATE font/ttf
    AddOutputFilterByType DEFLATE image/svg+xml
    AddOutputFilterByType DEFLATE image/x-icon
    AddOutputFilterByType DEFLATE text/css
    AddOutputFilterByType DEFLATE text/html
    AddOutputFilterByType DEFLATE text/javascript
    AddOutputFilterByType DEFLATE text/plain
    AddOutputFilterByType DEFLATE text/xml
    
    # Remove browser bugs (old browsers)
    BrowserMatch ^Mozilla/4 gzip-only-text/html
    BrowserMatch ^Mozilla/4\.0[678] no-gzip
    BrowserMatch \bMSIE !no-gzip !gzip-only-text/html
    Header append Vary User-Agent
</IfModule>
```

**Enable compression**:

```bash
sudo a2enconf compression
sudo systemctl restart apache2
```

**Why compression**: Reduces bandwidth usage by 50-70%, making pages load faster.

### ✅ Section 2 Verification

```bash
# Check Apache status
sudo systemctl status apache2

# Verify configuration
sudo apache2ctl configtest

# Check enabled modules
apache2ctl -M | grep -E "headers|ssl|rewrite|deflate|expires"

# Test compression
curl -H "Accept-Encoding: gzip" -I http://localhost

# Check for security headers
curl -I http://YOUR_SERVER_IP | grep -E "X-Frame-Options|X-Content-Type-Options"
```

**Apache Checklist**:
- ✅ Apache 2.4.x installed and running
- ✅ Starts automatically on boot
- ✅ Security modules enabled (headers, ssl, rewrite)
- ✅ Security headers configured
- ✅ Compression enabled
- ✅ Server version hidden (ServerTokens Prod)
- ✅ Accessible from browser

**Apache is now installed and secured!** 🌐

---

## Section 3: Installing MySQL Database

**Objective**: Install MySQL 8.0 with secure configuration, create application database and user, and optimize basic settings.

### Why MySQL?

MySQL is the world's most popular open-source relational database. MySQL 8.0 brings improved performance, better security with caching_sha2_password, and features like JSON support and window functions.

### Step 3.1: Install MySQL Server

```bash
# Update package list
sudo apt update

# Install MySQL 8.0
sudo apt install mysql-server -y

# Check MySQL version
mysql --version
```

**Expected output**: mysql Ver 8.0.x for Linux

**Why MySQL 8.0**: Modern features, better performance, improved security defaults, and active LTS support.

### Step 3.2: Verify MySQL is Running

```bash
# Check MySQL status
sudo systemctl status mysql

# Enable MySQL to start on boot
sudo systemctl enable mysql

# Verify MySQL is listening
sudo netstat -tlnp | grep mysql
```

**Should show**: MySQL listening on port 3306

### Step 3.3: Secure MySQL Installation

**Critical Security Step**: Run the security installation script to remove defaults.

```bash
# Run MySQL secure installation
sudo mysql_secure_installation
```

**Interactive prompts and recommended answers**:

1. **Validate Password Plugin?**
   - Answer: `Y` (Yes)
   - Why: Enforces strong password policies

2. **Password Validation Level?**
   - Choose: `2` (STRONG) - requires 8+ chars, mixed case, numbers, special chars
   - Why: Prevents weak passwords that are easy to crack

3. **Set root password?**
   - Answer: `Y` (Yes)
   - Enter a strong password (save it securely!)
   - Why: Default installation has no root password

4. **Remove anonymous users?**
   - Answer: `Y` (Yes)
   - Why: Anonymous users are a security risk

5. **Disallow root login remotely?**
   - Answer: `Y` (Yes)
   - Why: Root should only connect from localhost

6. **Remove test database?**
   - Answer: `Y` (Yes)
   - Why: Test database is accessible to anyone

7. **Reload privilege tables?**
   - Answer: `Y` (Yes)
   - Why: Apply changes immediately

**What this does**:
- Removes insecure defaults
- Forces strong passwords
- Limits root access to local only
- Removes unnecessary test data

### Step 3.4: Access MySQL and Create Database

**Login to MySQL**:

```bash
# Login as root (will prompt for password you just set)
sudo mysql -u root -p

# Or use sudo authentication (works without password on Ubuntu)
sudo mysql
```

**Create database and user**:

```sql
-- Create database for the LAMP application
CREATE DATABASE lampdb;

-- Create dedicated user for the application
CREATE USER 'lampuser'@'localhost' IDENTIFIED BY 'YourStrongPassword123!';

-- Grant all privileges on lampdb to lampuser
GRANT ALL PRIVILEGES ON lampdb.* TO 'lampuser'@'localhost';

-- Apply privilege changes
FLUSH PRIVILEGES;

-- Verify database creation
SHOW DATABASES;

-- Verify user creation
SELECT user, host FROM mysql.user WHERE user='lampuser';

-- Exit MySQL
EXIT;
```

**Security Best Practice**: 
- Never use root for applications
- Create dedicated users with minimal required privileges
- Use strong passwords (save in password manager)
- Grant only necessary permissions

**Why separate user**: If the application is compromised, the attacker only has access to `lampdb`, not all databases.

### Step 3.5: Test Database Connection

```bash
# Test connection with the new user
mysql -u lampuser -p

# Enter password when prompted
# You should see: mysql>
```

**Inside MySQL, verify access**:

```sql
-- Show current user
SELECT USER();

-- Show accessible databases
SHOW DATABASES;

-- Use the lampdb database
USE lampdb;

-- Show tables (should be empty for now)
SHOW TABLES;

-- Exit
EXIT;
```

**Expected**: You should only see `lampdb` and `information_schema` databases.

### Step 3.6: Configure MySQL for Basic Optimization

**Reference configuration**: The repository includes an optimized MySQL configuration at `configs/my.cnf`.

```bash
# Backup original configuration
sudo cp /etc/mysql/mysql.conf.d/mysqld.cnf /etc/mysql/mysql.conf.d/mysqld.cnf.backup

# View the example configuration
cat /home/runner/work/P1_LAMP_Deployment/P1_LAMP_Deployment/configs/my.cnf
```

**Create custom configuration**:

```bash
# Create custom configuration file
sudo nano /etc/mysql/mysql.conf.d/custom.cnf
```

**Add these optimizations** (adjust based on your server RAM):

```ini
[mysqld]
# Character Set (UTF-8 support for international characters)
character-set-server = utf8mb4
collation-server = utf8mb4_unicode_ci

# InnoDB Settings (for 2GB RAM server)
innodb_buffer_pool_size = 512M           # 25-50% of RAM
innodb_log_file_size = 128M              # Transaction log size
innodb_flush_log_at_trx_commit = 2       # Better performance, slight risk
innodb_flush_method = O_DIRECT           # Avoid double buffering

# Connection Settings
max_connections = 150                     # Concurrent connections allowed
max_allowed_packet = 16M                  # Max packet size
connect_timeout = 10                      # Connection timeout
wait_timeout = 600                        # Idle connection timeout (10 min)

# Query Performance
tmp_table_size = 32M                      # Temp table size in memory
max_heap_table_size = 32M                 # Max MEMORY table size
table_open_cache = 400                    # Cache open table definitions

# Logging (for troubleshooting and optimization)
slow_query_log = 1                        # Enable slow query log
slow_query_log_file = /var/log/mysql/mysql-slow.log
long_query_time = 2                       # Log queries taking > 2 seconds
log_error = /var/log/mysql/error.log      # Error log location

# Thread Settings
thread_cache_size = 8                     # Cache threads for reuse
thread_stack = 256K                       # Stack size per thread

[mysql]
# Client default character set
default-character-set = utf8mb4

[client]
# Client configuration
port = 3306
socket = /var/run/mysqld/mysqld.sock
default-character-set = utf8mb4
```

**Why each setting matters**:

- **utf8mb4**: Full Unicode support (emojis, international chars)
- **innodb_buffer_pool_size**: Most important setting - caches data and indexes
- **innodb_flush_log_at_trx_commit = 2**: Better performance with minimal risk (flushes logs every second)
- **slow_query_log**: Identifies slow queries that need optimization
- **max_connections**: Prevents server overload from too many connections

**Apply configuration**:

```bash
# Test configuration syntax
sudo mysqld --validate-config

# If no errors, restart MySQL
sudo systemctl restart mysql

# Verify MySQL started successfully
sudo systemctl status mysql

# Check error log for issues
sudo tail -f /var/log/mysql/error.log
# Press Ctrl+C to exit
```

### Step 3.7: Verify MySQL Settings

```bash
# Login to MySQL
sudo mysql -u root -p

# Check current settings
```

```sql
-- Check character set
SHOW VARIABLES LIKE 'character_set%';

-- Check InnoDB buffer pool size
SHOW VARIABLES LIKE 'innodb_buffer_pool_size';

-- Check max connections
SHOW VARIABLES LIKE 'max_connections';

-- Check slow query log status
SHOW VARIABLES LIKE 'slow_query_log%';

-- Check database engines
SHOW ENGINES;

-- Exit
EXIT;
```

**Expected**: Variables should match your custom.cnf settings.

### Step 3.8: Create MySQL Log Directory

```bash
# Ensure log directory exists with proper permissions
sudo mkdir -p /var/log/mysql
sudo chown mysql:mysql /var/log/mysql
sudo chmod 750 /var/log/mysql

# Verify
ls -la /var/log/ | grep mysql
```

### ✅ Section 3 Verification

```bash
# Check MySQL is running
sudo systemctl status mysql

# Verify MySQL version
mysql --version

# Test root login
sudo mysql -u root -p

# Test application user login
mysql -u lampuser -p

# Check if database exists
mysql -u lampuser -p -e "SHOW DATABASES;"

# Check MySQL is listening on port 3306
sudo netstat -tlnp | grep 3306

# Review MySQL error log
sudo tail -20 /var/log/mysql/error.log
```

**MySQL Security & Configuration Checklist**:
- ✅ MySQL 8.0.x installed and running
- ✅ mysql_secure_installation completed
- ✅ Root password set and secure
- ✅ Anonymous users removed
- ✅ Remote root login disabled
- ✅ Test database removed
- ✅ Application database `lampdb` created
- ✅ Application user `lampuser` created with limited privileges
- ✅ Database connection tested successfully
- ✅ Custom configuration applied
- ✅ Character set set to utf8mb4
- ✅ Slow query log enabled
- ✅ Log directory created with proper permissions

**⚠️ Important**: Save your MySQL root password and lampuser password securely! You'll need the lampuser credentials for the application in Section 7.

**MySQL is now installed, secured, and optimized!** 🗄️

---

## Section 4: Installing PHP

**Objective**: Install PHP 8.3 with PHP-FPM for better performance, configure essential extensions, optimize settings, and integrate with Apache.

### Why PHP 8.3 with PHP-FPM?

**PHP 8.3** is the latest stable version with:
- JIT (Just-In-Time) compiler for better performance
- Improved type system and error handling
- Better security features
- Modern syntax improvements

**PHP-FPM** (FastCGI Process Manager):
- Better performance than mod_php
- Works with Apache Event MPM (configured in Section 2)
- Better resource management
- Process isolation for security

### Step 4.1: Add PHP Repository

Ubuntu 24.04 might not have PHP 8.3 in default repos, so we'll add Ondřej Surý's PPA:

```bash
# Install software-properties-common (for add-apt-repository)
sudo apt install software-properties-common -y

# Add PHP repository
sudo add-apt-repository ppa:ondrej/php -y

# Update package list
sudo apt update
```

**Why PPA**: Gets us the latest stable PHP 8.3 version with all security updates.

### Step 4.2: Install PHP 8.3 and Essential Extensions

```bash
# Install PHP 8.3 with PHP-FPM and essential extensions
sudo apt install php8.3 php8.3-fpm php8.3-mysql php8.3-curl php8.3-gd \
  php8.3-mbstring php8.3-xml php8.3-zip php8.3-bcmath php8.3-intl \
  php8.3-readline php8.3-opcache -y

# Check PHP version
php -v

# Check PHP-FPM version
php-fpm8.3 -v
```

**Expected output**: PHP 8.3.x (cli) and PHP 8.3.x-fpm

**Extensions explained**:
- **php8.3-fpm**: FastCGI Process Manager
- **php8.3-mysql**: MySQL/MariaDB database connectivity
- **php8.3-curl**: HTTP requests (APIs, webhooks)
- **php8.3-gd**: Image processing (thumbnails, watermarks)
- **php8.3-mbstring**: Multi-byte string support (international text)
- **php8.3-xml**: XML parsing and generation
- **php8.3-zip**: ZIP file handling
- **php8.3-bcmath**: Arbitrary precision mathematics
- **php8.3-intl**: Internationalization functions
- **php8.3-opcache**: Opcode caching for better performance

### Step 4.3: Verify PHP-FPM is Running

```bash
# Check PHP-FPM status
sudo systemctl status php8.3-fpm

# Enable PHP-FPM to start on boot
sudo systemctl enable php8.3-fpm

# Check which socket PHP-FPM is using
sudo netstat -pl | grep php-fpm
```

**Should show**: PHP-FPM listening on Unix socket `/run/php/php8.3-fpm.sock`

**Why socket**: Unix sockets are faster than TCP for local communication.

### Step 4.4: Configure PHP Settings

**Reference configuration**: The repository includes optimized PHP settings at `configs/php.ini`.

```bash
# View example PHP configuration
cat /home/runner/work/P1_LAMP_Deployment/P1_LAMP_Deployment/configs/php.ini

# Edit PHP-FPM configuration
sudo nano /etc/php/8.3/fpm/php.ini
```

**Key settings to modify** (search for these in the file):

```ini
; Error Handling (Production Settings)
error_reporting = E_ALL & ~E_DEPRECATED & ~E_STRICT
display_errors = Off                    ; Don't show errors to users
display_startup_errors = Off
log_errors = On                         ; Log errors instead
error_log = /var/log/php/error.log     ; Error log location

; Resource Limits
max_execution_time = 30                 ; Max script execution time (seconds)
max_input_time = 60                     ; Max input parsing time
memory_limit = 256M                     ; Max memory per script
post_max_size = 20M                     ; Max POST data size
upload_max_filesize = 20M               ; Max file upload size

; File Uploads
file_uploads = On
max_file_uploads = 20                   ; Max simultaneous file uploads

; Session Configuration (Security)
session.use_strict_mode = 1             ; Reject uninitialized session IDs
session.cookie_httponly = 1             ; Prevent JavaScript access to cookies
session.cookie_secure = 1               ; Only send cookies over HTTPS
session.cookie_samesite = Strict        ; CSRF protection
session.gc_maxlifetime = 3600           ; Session timeout (1 hour)

; Security Settings
expose_php = Off                        ; Don't advertise PHP version
allow_url_fopen = On                    ; Allow URL file access
allow_url_include = Off                 ; Prevent remote file inclusion

; Date Settings
date.timezone = UTC                     ; Set your timezone (e.g., America/New_York)

; OPcache (Performance)
opcache.enable = 1                      ; Enable opcode caching
opcache.enable_cli = 1                  ; Enable for CLI scripts too
opcache.memory_consumption = 128        ; OPcache memory (MB)
opcache.interned_strings_buffer = 8     ; String interning buffer
opcache.max_accelerated_files = 10000   ; Max cached scripts
opcache.revalidate_freq = 2             ; Check for updates every 2 seconds
opcache.fast_shutdown = 1               ; Faster shutdown
```

**Why each setting**:
- **expose_php = Off**: Don't tell attackers what PHP version you're running
- **display_errors = Off**: Security risk - shows code paths to attackers
- **session.cookie_httponly**: Prevents XSS attacks from stealing session cookies
- **session.cookie_secure**: Ensures cookies only sent over HTTPS
- **opcache.enable**: Caches compiled PHP code - huge performance boost

**Create PHP log directory**:

```bash
# Create log directory
sudo mkdir -p /var/log/php

# Set permissions
sudo chown www-data:www-data /var/log/php
sudo chmod 755 /var/log/php
```

**Apply configuration**:

```bash
# Restart PHP-FPM
sudo systemctl restart php8.3-fpm

# Verify it started successfully
sudo systemctl status php8.3-fpm
```

### Step 4.5: Configure PHP-FPM Pool Settings

**PHP-FPM uses "pools"** - separate worker processes for different applications.

```bash
# Edit default pool configuration
sudo nano /etc/php/8.3/fpm/pool.d/www.conf
```

**Key settings to review/modify**:

```ini
; Pool name
[www]

; Run as www-data user (Apache user)
user = www-data
group = www-data

; Listen on Unix socket
listen = /run/php/php8.3-fpm.sock

; Socket permissions
listen.owner = www-data
listen.group = www-data
listen.mode = 0660

; Process Manager Settings (Dynamic)
pm = dynamic                            ; Dynamic process management
pm.max_children = 20                    ; Max worker processes (adjust based on RAM)
pm.start_servers = 2                    ; Processes started on boot
pm.min_spare_servers = 1                ; Min idle processes
pm.max_spare_servers = 3                ; Max idle processes
pm.max_requests = 500                   ; Recycle worker after 500 requests

; Resource Limits
pm.process_idle_timeout = 10s           ; Kill idle process after 10s
request_terminate_timeout = 30s         ; Max request time (matches php.ini)

; Logging
php_admin_value[error_log] = /var/log/php-fpm/www-error.log
php_admin_flag[log_errors] = on
```

**Process Manager explained**:
- **pm = dynamic**: Spawns workers based on demand (saves memory)
- **pm.max_children**: Total max workers (each uses ~20-50MB RAM)
  - Formula: `(Available RAM * 0.8) / 50MB` 
  - For 2GB RAM: `(1600MB / 50MB) ≈ 20-30`
- **pm.start_servers**: Initial workers on startup
- **pm.min/max_spare_servers**: Keeps some idle workers ready

**Create PHP-FPM log directory**:

```bash
sudo mkdir -p /var/log/php-fpm
sudo chown www-data:www-data /var/log/php-fpm
sudo chmod 755 /var/log/php-fpm
```

**Apply pool configuration**:

```bash
# Test PHP-FPM configuration
sudo php-fpm8.3 -t

# Should output: configuration file /etc/php/8.3/fpm/php-fpm.conf test is successful

# Restart PHP-FPM
sudo systemctl restart php8.3-fpm

# Check status
sudo systemctl status php8.3-fpm
```

### Step 4.6: Enable PHP-FPM with Apache

**Enable required Apache modules**:

```bash
# Enable proxy modules for PHP-FPM
sudo a2enmod proxy_fcgi setenvif

# Enable PHP 8.3 FPM configuration
sudo a2enconf php8.3-fpm

# Verify configuration
sudo apache2ctl configtest

# Restart Apache
sudo systemctl restart apache2

# Verify both services are running
sudo systemctl status apache2
sudo systemctl status php8.3-fpm
```

**What this does**:
- **proxy_fcgi**: Allows Apache to communicate with PHP-FPM via FastCGI
- **setenvif**: Sets environment variables based on request
- **php8.3-fpm.conf**: Tells Apache to use PHP-FPM for .php files

### Step 4.7: Test PHP is Working

**Create a test PHP file**:

```bash
# Create PHP info file
echo "<?php phpinfo(); ?>" | sudo tee /var/www/html/info.php

# Set proper permissions
sudo chown www-data:www-data /var/www/html/info.php
sudo chmod 644 /var/www/html/info.php
```

**Test from browser**:

Open your browser and navigate to:
```
http://YOUR_SERVER_IP/info.php
```

**You should see**: 
- PHP Version 8.3.x
- Server API: FPM/FastCGI
- All installed extensions listed

**Test from command line**:

```bash
# Test locally
curl http://localhost/info.php | grep "PHP Version"
```

**⚠️ Security**: Delete the info.php file after testing (it exposes configuration):

```bash
sudo rm /var/www/html/info.php
```

### Step 4.8: Create a Better PHP Test

**Create a more secure test file**:

```bash
sudo nano /var/www/html/test.php
```

**Add**:

```php
<?php
// PHP Configuration Test
echo "<h1>PHP Test Page</h1>";
echo "<p><strong>PHP Version:</strong> " . phpversion() . "</p>";
echo "<p><strong>Server API:</strong> " . php_sapi_name() . "</p>";

// Test MySQL connection
echo "<h2>MySQL Connection Test</h2>";
try {
    $pdo = new PDO('mysql:host=localhost;dbname=lampdb', 'lampuser', 'YourStrongPassword123!');
    echo "<p style='color: green;'>✅ MySQL connection successful!</p>";
    echo "<p><strong>MySQL Version:</strong> " . $pdo->getAttribute(PDO::ATTR_SERVER_VERSION) . "</p>";
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ MySQL connection failed: " . $e->getMessage() . "</p>";
}

// Test important extensions
echo "<h2>PHP Extensions</h2>";
$extensions = ['mysqli', 'pdo_mysql', 'curl', 'gd', 'mbstring', 'xml', 'zip', 'opcache'];
echo "<ul>";
foreach ($extensions as $ext) {
    $loaded = extension_loaded($ext) ? '✅' : '❌';
    echo "<li>$loaded $ext</li>";
}
echo "</ul>";
?>
```

**Replace** `YourStrongPassword123!` with your actual lampuser password.

**Set permissions**:

```bash
sudo chown www-data:www-data /var/www/html/test.php
sudo chmod 644 /var/www/html/test.php
```

**Test in browser**: `http://YOUR_SERVER_IP/test.php`

**Expected**:
- ✅ PHP Version 8.3.x
- ✅ Server API: FPM/FastCGI
- ✅ MySQL connection successful
- ✅ All extensions loaded

**Clean up after testing**:

```bash
sudo rm /var/www/html/test.php
```

### Step 4.9: Configure PHP Security Headers

**Additional security via Apache** (add to security.conf if not already present):

```bash
sudo nano /etc/apache2/conf-available/security.conf
```

**Add these directives**:

```apache
# PHP Security Headers
<FilesMatch \.php$>
    # Prevent execution of PHP in upload directories
    <If "%{REQUEST_URI} =~ m#/uploads/#">
        Require all denied
    </If>
    
    # Additional security headers for PHP
    Header set X-Content-Type-Options "nosniff"
    Header set X-Frame-Options "SAMEORIGIN"
</FilesMatch>
```

**Restart Apache**:

```bash
sudo systemctl restart apache2
```

### ✅ Section 4 Verification

```bash
# Check PHP version
php -v

# Check PHP-FPM status
sudo systemctl status php8.3-fpm

# List loaded PHP modules
php -m

# Check PHP-FPM configuration
sudo php-fpm8.3 -t

# Verify Apache is using PHP-FPM
sudo apache2ctl -M | grep proxy_fcgi

# Check PHP-FPM processes
ps aux | grep php-fpm

# Check PHP-FPM socket
ls -la /run/php/php8.3-fpm.sock

# Review PHP error log
sudo tail -20 /var/log/php/error.log

# Test PHP processing
echo "<?php echo 'PHP is working!'; ?>" | sudo tee /var/www/html/quick-test.php
curl http://localhost/quick-test.php
sudo rm /var/www/html/quick-test.php
```

**PHP Installation Checklist**:
- ✅ PHP 8.3.x installed
- ✅ PHP-FPM installed and running
- ✅ Essential extensions installed (mysql, curl, gd, mbstring, xml, zip)
- ✅ PHP configuration optimized (php.ini)
- ✅ PHP-FPM pool configured
- ✅ OPcache enabled for performance
- ✅ Apache configured to use PHP-FPM (proxy_fcgi)
- ✅ PHP-FPM socket exists and working
- ✅ PHP processing tested successfully
- ✅ MySQL connectivity from PHP works
- ✅ Security settings configured (expose_php off, etc.)
- ✅ Error logging configured
- ✅ Session security enabled

**PHP 8.3 with PHP-FPM is now installed and configured!** 🐘

---

## Section 5: Configuring Virtual Hosts

**Objective**: Create a proper directory structure for the web application and configure Apache virtual host to serve it.

### Why Virtual Hosts?

Virtual hosts allow Apache to serve multiple websites from one server. Even with one application, using a virtual host provides:
- Clean separation from default Apache config
- Custom logging per application
- Easy SSL configuration later
- Professional directory structure
- Better security and maintenance

### Step 5.1: Create Directory Structure

```bash
# Create web application directory
sudo mkdir -p /var/www/lampapp

# Create subdirectories for organization
sudo mkdir -p /var/www/lampapp/public
sudo mkdir -p /var/www/lampapp/logs

# Verify structure
tree /var/www/lampapp -L 2
# Or if tree is not installed:
ls -la /var/www/lampapp/
```

**Directory purpose**:
- `/var/www/lampapp/` - Application root
- `/var/www/lampapp/public/` - Web-accessible files (DocumentRoot)
- `/var/www/lampapp/logs/` - Application-specific logs

**Why separate public directory**: Only the `public/` folder should be web-accessible. Config files, includes, and sensitive data stay outside the web root.

### Step 5.2: Set Proper Ownership and Permissions

```bash
# Set ownership to Apache user (www-data)
sudo chown -R www-data:www-data /var/www/lampapp

# Set directory permissions (755 = rwxr-xr-x)
sudo find /var/www/lampapp -type d -exec chmod 755 {} \;

# Set file permissions (644 = rw-r--r--)
sudo find /var/www/lampapp -type f -exec chmod 644 {} \;

# Verify permissions
ls -la /var/www/lampapp/
```

**Permission breakdown**:
- **755 for directories**: Owner can read/write/execute, others can read/execute
- **644 for files**: Owner can read/write, others can only read
- **www-data:www-data**: Apache user owns the files

**Why these permissions**: Secure defaults that allow Apache to read files but prevent unauthorized modifications.

### Step 5.3: Create Test Index Page

```bash
# Create a test index.html
sudo nano /var/www/lampapp/public/index.html
```

**Add this content**:

```html
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LAMP Stack - Virtual Host Test</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .container {
            background: rgba(255, 255, 255, 0.1);
            padding: 30px;
            border-radius: 10px;
            backdrop-filter: blur(10px);
        }
        h1 { color: #fff; margin-bottom: 10px; }
        .status { 
            background: #10b981; 
            padding: 15px; 
            border-radius: 5px; 
            margin: 20px 0;
            font-weight: bold;
        }
        .info { 
            background: rgba(255, 255, 255, 0.2); 
            padding: 15px; 
            border-radius: 5px; 
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚀 LAMP Stack Virtual Host</h1>
        <p><strong>Congratulations!</strong> Your virtual host is configured correctly.</p>
        
        <div class="status">
            ✅ Apache Virtual Host: WORKING
        </div>
        
        <div class="info">
            <strong>Server:</strong> Apache 2.4.x<br>
            <strong>Application:</strong> lampapp<br>
            <strong>Document Root:</strong> /var/www/lampapp/public
        </div>
        
        <p><em>Next: Configure SSL in Section 6</em></p>
    </div>
</body>
</html>
```

**Set permissions**:

```bash
sudo chown www-data:www-data /var/www/lampapp/public/index.html
sudo chmod 644 /var/www/lampapp/public/index.html
```

### Step 5.4: Create Apache Virtual Host Configuration

**Reference configuration**: The repository includes a template at `configs/apache-vhost.conf`.

```bash
# View the example configuration
cat /home/runner/work/P1_LAMP_Deployment/P1_LAMP_Deployment/configs/apache-vhost.conf

# Create virtual host configuration
sudo nano /etc/apache2/sites-available/lampapp.conf
```

**Add this configuration**:

```apache
<VirtualHost *:80>
    # Server identification
    ServerName lampapp.local
    ServerAdmin webmaster@localhost
    
    # Document root (web-accessible directory)
    DocumentRoot /var/www/lampapp/public
    
    # Directory permissions and options
    <Directory /var/www/lampapp/public>
        Options -Indexes +FollowSymLinks -MultiViews
        AllowOverride All
        Require all granted
        
        # PHP-FPM configuration
        <FilesMatch \.php$>
            SetHandler "proxy:unix:/run/php/php8.3-fpm.sock|fcgi://localhost"
        </FilesMatch>
    </Directory>
    
    # Deny access to sensitive files
    <FilesMatch "^\.">
        Require all denied
    </FilesMatch>
    
    <FilesMatch "(composer\.json|composer\.lock|package\.json|\.env)$">
        Require all denied
    </FilesMatch>
    
    # Logging
    ErrorLog /var/www/lampapp/logs/error.log
    CustomLog /var/www/lampapp/logs/access.log combined
    
    # Security headers (requires mod_headers)
    <IfModule mod_headers.c>
        Header always set X-Content-Type-Options "nosniff"
        Header always set X-Frame-Options "SAMEORIGIN"
        Header always set X-XSS-Protection "1; mode=block"
        Header always set Referrer-Policy "strict-origin-when-cross-origin"
    </IfModule>
    
    # Compression (requires mod_deflate)
    <IfModule mod_deflate.c>
        AddOutputFilterByType DEFLATE text/html text/plain text/xml text/css
        AddOutputFilterByType DEFLATE text/javascript application/javascript application/json
    </IfModule>
    
    # Browser caching (requires mod_expires)
    <IfModule mod_expires.c>
        ExpiresActive On
        ExpiresByType image/jpg "access plus 1 year"
        ExpiresByType image/jpeg "access plus 1 year"
        ExpiresByType image/gif "access plus 1 year"
        ExpiresByType image/png "access plus 1 year"
        ExpiresByType image/webp "access plus 1 year"
        ExpiresByType image/svg+xml "access plus 1 year"
        ExpiresByType text/css "access plus 1 month"
        ExpiresByType application/javascript "access plus 1 month"
        ExpiresByType application/pdf "access plus 1 month"
    </IfModule>
</VirtualHost>
```

**Configuration explained**:

- **ServerName**: Domain name (use IP or domain)
- **DocumentRoot**: Only `public/` is web-accessible
- **Options -Indexes**: Prevents directory listing (security)
- **AllowOverride All**: Allows .htaccess files (for URL rewriting)
- **FilesMatch \.php$**: Routes PHP files through PHP-FPM
- **Deny .env, composer.json**: Protects sensitive configuration files
- **Custom logging**: Separate logs per application
- **Security headers**: Protection against common attacks
- **Browser caching**: Faster page loads for repeat visitors

### Step 5.5: Enable the Virtual Host

```bash
# Test configuration syntax
sudo apache2ctl configtest

# Should output: Syntax OK

# Enable the new site
sudo a2ensite lampapp.conf

# Disable the default Apache site (optional but recommended)
sudo a2dissite 000-default.conf

# Restart Apache to apply changes
sudo systemctl restart apache2

# Verify Apache is running
sudo systemctl status apache2
```

**What this does**:
- `a2ensite` creates a symlink in `sites-enabled/`
- `a2dissite` removes the default site
- Restart applies the changes

### Step 5.6: Test Virtual Host Configuration

**Test from server**:

```bash
# Test the virtual host
curl http://localhost

# Or
curl http://127.0.0.1
```

**Expected**: HTML output with "LAMP Stack Virtual Host"

**Test from browser**:

```
http://YOUR_SERVER_IP
```

**You should see**: The styled "LAMP Stack Virtual Host" page

**Troubleshooting**:

If you see the default Apache page instead:
```bash
# Verify lampapp is enabled
ls -la /etc/apache2/sites-enabled/

# Should show: lampapp.conf

# Make sure default is disabled
sudo a2dissite 000-default.conf

# Restart Apache
sudo systemctl restart apache2
```

### Step 5.7: Create PHP Test Page

**Verify PHP works with the virtual host**:

```bash
sudo nano /var/www/lampapp/public/phpinfo.php
```

**Add**:

```php
<?php
echo "<h1>PHP Test</h1>";
echo "<p><strong>PHP Version:</strong> " . phpversion() . "</p>";
echo "<p><strong>Document Root:</strong> " . $_SERVER['DOCUMENT_ROOT'] . "</p>";
echo "<p><strong>Server Software:</strong> " . $_SERVER['SERVER_SOFTWARE'] . "</p>";
echo "<p>✅ PHP is working with the virtual host!</p>";
?>
```

**Set permissions**:

```bash
sudo chown www-data:www-data /var/www/lampapp/public/phpinfo.php
sudo chmod 644 /var/www/lampapp/public/phpinfo.php
```

**Test in browser**: `http://YOUR_SERVER_IP/phpinfo.php`

**Expected**:
- PHP Version 8.3.x
- Document Root: /var/www/lampapp/public
- Server Software: Apache

**Clean up**:

```bash
sudo rm /var/www/lampapp/public/phpinfo.php
```

### Step 5.8: Configure Log Rotation

**Prevent logs from filling up disk space**:

```bash
sudo nano /etc/logrotate.d/lampapp
```

**Add**:

```
/var/www/lampapp/logs/*.log {
    daily
    missingok
    rotate 14
    compress
    delaycompress
    notifempty
    create 0640 www-data www-data
    sharedscripts
    postrotate
        if [ -f /var/run/apache2/apache2.pid ]; then
            /etc/init.d/apache2 reload > /dev/null
        fi
    endscript
}
```

**What this does**:
- Rotates logs daily
- Keeps 14 days of logs
- Compresses old logs
- Reloads Apache after rotation

**Test log rotation**:

```bash
# Test the configuration
sudo logrotate -d /etc/logrotate.d/lampapp

# Force a rotation (testing only)
sudo logrotate -f /etc/logrotate.d/lampapp
```

### Step 5.9: Set up .htaccess for URL Rewriting (Optional)

**For clean URLs** (e.g., /about instead of /about.php):

```bash
sudo nano /var/www/lampapp/public/.htaccess
```

**Add**:

```apache
# Enable Rewrite Engine
RewriteEngine On

# Redirect to HTTPS (uncomment after SSL is configured in Section 6)
# RewriteCond %{HTTPS} off
# RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

# Remove .php extension from URLs
RewriteCond %{REQUEST_FILENAME} !-d
RewriteCond %{REQUEST_FILENAME}\.php -f
RewriteRule ^(.*)$ $1.php [L]

# Deny access to sensitive files
<FilesMatch "(^\..*|composer\.json|composer\.lock|package\.json|\.env)$">
    Require all denied
</FilesMatch>
```

**Set permissions**:

```bash
sudo chown www-data:www-data /var/www/lampapp/public/.htaccess
sudo chmod 644 /var/www/lampapp/public/.htaccess
```

**Why .htaccess**: Allows per-directory Apache configuration without editing main config.

### ✅ Section 5 Verification

```bash
# Check Apache virtual host configuration
sudo apache2ctl -S

# Should list: lampapp.conf

# Test configuration syntax
sudo apache2ctl configtest

# Verify directory structure
tree /var/www/lampapp -L 2
# Or: ls -la /var/www/lampapp/

# Check ownership and permissions
ls -la /var/www/lampapp/public/

# Verify enabled sites
ls -la /etc/apache2/sites-enabled/

# Check Apache error log for issues
sudo tail -20 /var/www/lampapp/logs/error.log

# Test HTTP access
curl -I http://localhost

# Check response headers
curl -I http://localhost | grep -E "X-Frame-Options|X-Content-Type-Options"
```

**Virtual Host Configuration Checklist**:
- ✅ Directory structure created (/var/www/lampapp/public)
- ✅ Proper ownership set (www-data:www-data)
- ✅ Secure file permissions (755 dirs, 644 files)
- ✅ Virtual host configuration created (lampapp.conf)
- ✅ Virtual host enabled
- ✅ Default site disabled
- ✅ Test index.html created and accessible
- ✅ PHP processing works with virtual host
- ✅ Security headers configured
- ✅ Compression enabled
- ✅ Browser caching configured
- ✅ Custom logging configured
- ✅ Log rotation set up
- ✅ .htaccess created (optional)
- ✅ Sensitive files protected

**Virtual host is configured and working!** 🌍

---
