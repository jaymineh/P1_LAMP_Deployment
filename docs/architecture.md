# LAMP Stack Architecture

This document describes the architecture, components, and security layers of the LAMP stack deployment.

## Table of Contents
- [Architecture Overview](#architecture-overview)
- [Component Descriptions](#component-descriptions)
- [Network Flow](#network-flow)
- [Security Layers](#security-layers)
- [File Structure](#file-structure)
- [Data Flow](#data-flow)

## Architecture Overview

```
┌─────────────────────────────────────────────────────────────────┐
│                          Internet                                │
└────────────────────────────┬────────────────────────────────────┘
                             │
                    ┌────────▼────────┐
                    │   AWS Route 53   │ (Optional DNS)
                    │  yourdomain.com  │
                    └────────┬────────┘
                             │
                ┌────────────▼───────────────┐
                │  AWS Security Group         │
                │  - Port 22 (SSH)            │
                │  - Port 80 (HTTP)           │
                │  - Port 443 (HTTPS)         │
                └────────────┬───────────────┘
                             │
        ┌────────────────────▼────────────────────┐
        │         Ubuntu Server (EC2)              │
        │  ┌────────────────────────────────────┐ │
        │  │        UFW Firewall                │ │
        │  │  - Port 22, 80, 443                │ │
        │  └────────┬───────────────────────────┘ │
        │           │                              │
        │  ┌────────▼───────────────────────────┐ │
        │  │      fail2ban                       │ │
        │  │  (Intrusion Prevention)             │ │
        │  └────────┬───────────────────────────┘ │
        │           │                              │
        │  ┌────────▼───────────────────────────┐ │
        │  │   Apache Web Server 2.4.x          │ │
        │  │   - mod_ssl (HTTPS/TLS)            │ │
        │  │   - mod_php / PHP-FPM              │ │
        │  │   - mod_rewrite                    │ │
        │  │   - Virtual Hosts                  │ │
        │  └────────┬───────────────────────────┘ │
        │           │                              │
        │  ┌────────▼───────────────────────────┐ │
        │  │   PHP 8.2/8.3                      │ │
        │  │   - OPcache                        │ │
        │  │   - mysqli/PDO                     │ │
        │  │   Extensions: curl, mbstring, etc  │ │
        │  └────────┬───────────────────────────┘ │
        │           │                              │
        │  ┌────────▼───────────────────────────┐ │
        │  │   MySQL 8.0 / MariaDB 10.11+       │ │
        │  │   - InnoDB Engine                  │ │
        │  │   - User Authentication            │ │
        │  │   - Database: lamp_app             │ │
        │  └────────────────────────────────────┘ │
        │                                          │
        │  ┌────────────────────────────────────┐ │
        │  │   File System                       │ │
        │  │   /var/www/html     - Web root     │ │
        │  │   /var/backups      - DB backups   │ │
        │  │   /var/log          - Logs         │ │
        │  └────────────────────────────────────┘ │
        │                                          │
        │  ┌────────────────────────────────────┐ │
        │  │   Monitoring & Automation          │ │
        │  │   - Health check cron              │ │
        │  │   - Backup cron                    │ │
        │  │   - Log rotation                   │ │
        │  │   - SSL renewal (certbot timer)    │ │
        │  └────────────────────────────────────┘ │
        └─────────────────────────────────────────┘
                             │
                ┌────────────▼───────────────┐
                │   AWS S3 (Optional)         │
                │   Backup Storage            │
                └────────────────────────────┘
```

## Component Descriptions

### 1. **Operating System: Ubuntu 24.04 LTS**

**Purpose**: Foundation layer providing the Linux environment

**Key Features**:
- Long Term Support (5 years of updates)
- Stable and secure
- systemd for service management
- APT package management

**Why Ubuntu 24.04**:
- Latest LTS with modern kernel
- Excellent hardware support
- Large community and documentation
- Regular security updates

### 2. **Apache HTTP Server 2.4.x**

**Purpose**: Web server that handles HTTP/HTTPS requests

**Key Modules**:
- **mod_ssl**: Enables HTTPS/TLS encryption
- **mod_php** or **PHP-FPM**: Processes PHP code
- **mod_rewrite**: URL rewriting for clean URLs
- **mod_headers**: Custom HTTP headers for security
- **mod_deflate**: Compression for faster page loads
- **mod_expires**: Browser caching control

**Configuration Files**:
- `/etc/apache2/apache2.conf` - Main configuration
- `/etc/apache2/sites-available/` - Virtual host configs
- `/etc/apache2/mods-enabled/` - Enabled modules

**Why Apache**:
- Mature and battle-tested
- Excellent .htaccess support
- Rich module ecosystem
- Easy integration with PHP

### 3. **MySQL 8.0 / MariaDB 10.11+**

**Purpose**: Relational database management system

**Key Features**:
- **InnoDB Engine**: ACID-compliant transactions
- **UTF-8mb4**: Full Unicode support (including emojis)
- **Prepared Statements**: SQL injection protection
- **User Management**: Role-based access control
- **Binary Logging**: Point-in-time recovery

**Configuration**:
- `/etc/mysql/my.cnf` - Main configuration
- Optimizations for buffer pool, connections
- Character set: utf8mb4

**Why MySQL 8.0 / MariaDB**:
- Industry standard for web applications
- Excellent PHP integration
- Strong community support
- Performance and reliability

### 4. **PHP 8.2/8.3**

**Purpose**: Server-side scripting language

**Key Features**:
- **JIT Compilation**: Faster execution
- **OPcache**: Bytecode caching
- **Type System**: Better code quality
- **Security**: Modern cryptography functions

**Required Extensions**:
- **mysqli/pdo_mysql**: Database connectivity
- **mbstring**: Multi-byte string handling
- **curl**: HTTP requests
- **xml**: XML processing
- **zip**: Archive handling
- **gd**: Image manipulation

**Configuration**:
- `/etc/php/8.x/apache2/php.ini` - Apache module
- `/etc/php/8.x/fpm/php.ini` - PHP-FPM
- Custom settings in `/etc/php/8.x/conf.d/`

**Why PHP 8.2/8.3**:
- Latest features and performance
- Active support and security updates
- Excellent documentation
- Huge ecosystem of libraries

### 5. **SSL/TLS (Let's Encrypt)**

**Purpose**: Encrypt traffic between client and server

**Components**:
- **Certbot**: Automated certificate management
- **SSL Certificates**: Free from Let's Encrypt
- **Auto-renewal**: Systemd timer for renewal

**Why Let's Encrypt**:
- Free SSL certificates
- Automated renewal
- Trusted by all browsers
- Industry standard

## Network Flow

### HTTP Request Flow

```
1. Client Request
   ↓
2. DNS Resolution (yourdomain.com → Public IP)
   ↓
3. AWS Security Group (Port 80/443 check)
   ↓
4. UFW Firewall (Port 80/443 check)
   ↓
5. fail2ban (Check for banned IPs)
   ↓
6. Apache Web Server
   ├─ SSL/TLS Termination (if HTTPS)
   ├─ Virtual Host Selection
   ├─ URL Rewriting (mod_rewrite)
   └─ Static file OR PHP processing
      ↓
7. PHP Interpreter (if .php file)
   ├─ Load application code
   ├─ Database query (if needed)
   │  ↓
   │  MySQL Database
   │  ↓
   │  Return data
   └─ Generate HTML response
      ↓
8. Apache sends response
   ├─ Compression (mod_deflate)
   ├─ Security headers (mod_headers)
   └─ Caching headers (mod_expires)
      ↓
9. Client receives response
```

### Database Connection Flow

```
PHP Application
   ↓
PDO/mysqli Extension
   ↓
MySQL Client Library
   ↓
Unix Socket (/var/run/mysqld/mysqld.sock)
   or
TCP Connection (localhost:3306)
   ↓
MySQL Server
   ├─ Authentication
   ├─ Query Parsing
   ├─ Query Optimization
   ├─ InnoDB Storage Engine
   └─ Return Results
```

## Security Layers

The architecture implements defense in depth with multiple security layers:

### Layer 1: AWS Infrastructure
- **Security Groups**: Virtual firewall at instance level
- **VPC**: Network isolation
- **IAM**: Access control to AWS resources

### Layer 2: Network Security (UFW)
- **Firewall Rules**: 
  - Allow: 22 (SSH), 80 (HTTP), 443 (HTTPS)
  - Deny: All other incoming traffic
  - Allow: All outgoing traffic
- **Rate Limiting**: Prevent flood attacks

### Layer 3: Intrusion Prevention (fail2ban)
- **SSH Protection**: Ban IPs after failed login attempts
- **Apache Protection**: Ban aggressive scanners
- **Automatic Blocking**: Temporary bans for offenders
- **Email Alerts**: Notify admin of attacks

### Layer 4: SSH Hardening
- **Key-based Authentication**: No password login
- **Root Login Disabled**: Prevent direct root access
- **Non-standard Port** (optional): Change from 22
- **Limited Users**: Only authorized users

### Layer 5: Web Server Security
- **Security Headers**:
  - X-Content-Type-Options: nosniff
  - X-Frame-Options: SAMEORIGIN
  - X-XSS-Protection: 1; mode=block
  - Strict-Transport-Security (HSTS)
- **Directory Listing Disabled**: -Indexes
- **SSL/TLS**: Encrypt all traffic (HTTPS)
- **File Permissions**: Proper ownership and permissions

### Layer 6: Application Security
- **Input Validation**: All user input validated
- **Prepared Statements**: Prevent SQL injection
- **Output Escaping**: Prevent XSS attacks
- **Session Security**: Secure cookies, session timeout
- **Environment Variables**: Sensitive data not in code

### Layer 7: Database Security
- **User Privileges**: Minimal necessary permissions
- **No Remote Root**: Root only from localhost
- **Strong Passwords**: Complex database passwords
- **Encrypted Connections**: SSL/TLS for connections
- **Regular Backups**: Disaster recovery

## File Structure

```
/var/www/yourdomain/
├── public/               # Web root (DocumentRoot)
│   ├── index.php        # Main application entry
│   ├── create.php       # Create operations
│   ├── read.php         # Read operations
│   ├── update.php       # Update operations
│   ├── delete.php       # Delete operations
│   └── .htaccess        # Apache directives
├── config/
│   └── database.php     # Database configuration
├── includes/
│   └── functions.php    # Reusable functions
├── sql/
│   └── schema.sql       # Database schema
└── .env                 # Environment variables (not in Git)

/etc/apache2/
├── apache2.conf         # Main Apache config
├── sites-available/
│   └── yourdomain.conf  # Virtual host configuration
└── sites-enabled/
    └── yourdomain.conf  # Symlink to sites-available

/etc/mysql/
└── my.cnf               # MySQL configuration

/etc/php/8.x/
├── apache2/php.ini      # PHP config for Apache
└── conf.d/              # Additional PHP configs

/var/backups/mysql/      # Database backups
/var/log/
├── apache2/             # Apache logs
├── mysql/               # MySQL logs
└── php/                 # PHP logs

/etc/letsencrypt/        # SSL certificates
/etc/cron.d/             # Automated tasks
```

## Data Flow

### Task Creation Example

```
User fills form (create.php)
   ↓
Submit button clicked
   ↓
POST request to create.php
   ↓
PHP processes form data
   ├─ Validate input (title, description, etc.)
   ├─ Sanitize data
   └─ Check for errors
      ↓
   [If valid]
      ↓
   Create PDO connection
      ↓
   Prepare SQL statement
   INSERT INTO tasks (title, description, ...) VALUES (?, ?, ...)
      ↓
   Execute with bound parameters
      ↓
   MySQL stores data in InnoDB table
      ↓
   Return success
      ↓
   Set flash message
      ↓
   Redirect to index.php
      ↓
   Display success message
      ↓
   Show updated task list
```

## Performance Optimizations

1. **Apache MPM**: Event MPM for better concurrency
2. **PHP OPcache**: Bytecode caching for faster execution
3. **MySQL Query Cache**: Cache frequently-used queries
4. **Compression**: Gzip/deflate for smaller transfers
5. **Browser Caching**: Expires headers for static assets
6. **Connection Pooling**: Reuse database connections

## Monitoring and Maintenance

1. **Health Checks**: Automated service monitoring
2. **Log Rotation**: Prevent disk space issues
3. **Automated Backups**: Daily database backups
4. **SSL Renewal**: Automatic certificate renewal
5. **Security Updates**: Regular package updates
6. **Performance Metrics**: Monitor resource usage

## Scalability Considerations

For future growth, consider:

1. **Load Balancer**: Distribute traffic across multiple servers
2. **Database Replication**: Master-slave setup
3. **Caching Layer**: Redis/Memcached for session and data caching
4. **CDN**: CloudFront for static asset delivery
5. **Auto Scaling**: EC2 Auto Scaling Groups
6. **RDS**: Managed database service

## Next Steps

- Review [Prerequisites](./prerequisites.md) for setup requirements
- Follow [Main Deployment Guide](../project.md) for step-by-step installation
- Check [Troubleshooting Guide](./troubleshooting.md) if you encounter issues
