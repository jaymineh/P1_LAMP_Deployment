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
