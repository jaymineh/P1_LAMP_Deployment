# LAMP Stack Deployment - Production-Ready Guide

![LAMP Stack](https://img.shields.io/badge/Stack-LAMP-blue.svg)
![Ubuntu](https://img.shields.io/badge/Ubuntu-24.04_LTS-orange.svg)
![Apache](https://img.shields.io/badge/Apache-2.4.x-red.svg)
![MySQL](https://img.shields.io/badge/MySQL-8.0-blue.svg)
![PHP](https://img.shields.io/badge/PHP-8.2%20%7C%208.3-purple.svg)
![License](https://img.shields.io/badge/License-MIT-green.svg)

A comprehensive, production-ready guide for deploying a modern LAMP (Linux, Apache, MySQL, PHP) stack on Ubuntu with security best practices, automated backups, monitoring, and a fully functional sample application.

## 📋 Table of Contents

- [Overview](#overview)
- [Features](#features)
- [Prerequisites](#prerequisites)
- [Quick Start](#quick-start)
- [Project Structure](#project-structure)
- [Documentation](#documentation)
- [Sample Application](#sample-application)
- [What You'll Learn](#what-youll-learn)
- [Contributing](#contributing)
- [License](#license)

## 🎯 Overview

This project provides a hands-on, educational guide to deploying a production-ready LAMP stack on AWS EC2 (or any Ubuntu server). It goes beyond basic installation to include:

- **Modern Software**: PHP 8.2/8.3, MySQL 8.0/MariaDB 10.11+, Ubuntu 24.04 LTS
- **Security Hardening**: SSL/TLS, UFW firewall, fail2ban, SSH hardening
- **Automation**: Automated backups, health monitoring, log rotation
- **Best Practices**: Environment variables, prepared statements, proper permissions
- **Real Application**: Fully functional PHP CRUD application demonstrating the stack

This is **NOT** a containerized or automated deployment tool. It's designed for **hands-on learning** of Linux server administration, teaching you the "why" behind each configuration decision.

## ✨ Features

### 🔒 Security
- **SSL/TLS encryption** with Let's Encrypt (automated certificate renewal)
- **UFW firewall** configuration for network security
- **fail2ban** intrusion prevention system
- **SSH hardening** (key-based auth, root login disabled)
- **Security headers** (HSTS, XSS Protection, etc.)
- **Prepared SQL statements** to prevent injection attacks

### 💾 Backup & Recovery
- **Automated MySQL backups** with retention policies
- **AWS S3 integration** for offsite backup storage
- **Database restore scripts** with verification
- **Cron-based scheduling** for hands-free operation

### 📊 Monitoring & Logging
- **Health check scripts** for service monitoring
- **Log rotation** with logrotate
- **Email alerts** for critical issues
- **Performance monitoring** basics

### 🚀 Performance
- **PHP OPcache** for bytecode caching
- **Apache MPM** optimization
- **MySQL query optimization** and tuning
- **Gzip compression** for faster page loads
- **Browser caching** configuration

### 📱 Sample Application
- **Task Management System** built with PHP
- Full **CRUD operations** (Create, Read, Update, Delete)
- **Bootstrap UI** for modern, responsive design
- **Environment-based configuration** (.env file)
- **Database schema** and sample data included

## 📚 Prerequisites

Before starting, you should have:

### Required
- ✅ AWS account (or any Ubuntu server provider)
- ✅ Basic Linux command line knowledge
- ✅ SSH client installed
- ✅ Text editor familiarity (vim, nano, etc.)

### Recommended
- 💡 Domain name (for SSL setup)
- 💡 Basic understanding of web servers
- 💡 Familiarity with databases

### Server Requirements
- **OS**: Ubuntu 22.04 or 24.04 LTS
- **RAM**: 1GB minimum (2GB+ recommended)
- **Storage**: 20GB minimum
- **Network**: Public IP address

👉 **Detailed setup instructions**: [docs/prerequisites.md](docs/prerequisites.md)

## 🚀 Quick Start

### 1. Launch Ubuntu Server
```bash
# AWS EC2 t2.micro or t3.micro (free tier eligible)
# Ubuntu 24.04 LTS AMI
# Configure security groups: SSH (22), HTTP (80), HTTPS (443)
```

### 2. Connect via SSH
```bash
ssh -i your-key.pem ubuntu@your-server-ip
```

### 3. Follow Step-by-Step Guide
```bash
# Clone this repository (or download documentation)
# Follow project.md for complete walkthrough

# Or jump to specific sections:
# - Initial Server Setup
# - Install Apache, MySQL, PHP
# - Configure Security
# - Deploy Sample Application
# - Setup Backups & Monitoring
```

### 4. Deploy Sample Application
```bash
# Copy application files to /var/www/yourdomain
# Import database schema
# Configure .env file
# Visit your domain!
```

📖 **Complete walkthrough**: [project.md](project.md)

## 📁 Project Structure

```
P1_LAMP_Deployment/
├── README.md                      # This file
├── project.md                     # Main step-by-step deployment guide
├── CHANGELOG.md                   # Version history
├── LICENSE                        # MIT License
├── .gitignore                     # Git ignore rules
├── .env.example                   # Environment variables template
│
├── docs/                          # Additional documentation
│   ├── prerequisites.md           # AWS setup, security groups, SSH
│   ├── architecture.md            # System architecture & components
│   └── troubleshooting.md         # Common issues & solutions
│
├── configs/                       # Configuration templates
│   ├── apache-vhost.conf          # Apache virtual host template
│   ├── php.ini                    # PHP configuration customizations
│   └── my.cnf                     # MySQL tuning parameters
│
├── scripts/                       # Helper scripts
│   ├── backup.sh                  # Automated MySQL backup
│   ├── restore.sh                 # Database restoration
│   ├── health-check.sh            # Service monitoring
│   └── setup-ssl.sh               # SSL certificate automation
│
└── app/                           # Sample PHP application
    ├── .env.example               # App configuration template
    ├── config/
    │   └── database.php           # Database connection
    ├── includes/
    │   └── functions.php          # Helper functions
    ├── public/                    # Web root
    │   ├── index.php              # Task list (Read)
    │   ├── create.php             # Create task
    │   ├── read.php               # View task details
    │   ├── update.php             # Edit task
    │   └── delete.php             # Delete task
    └── sql/
        └── schema.sql             # Database schema
```

## 📖 Documentation

### Main Guide
- **[project.md](project.md)** - Complete deployment walkthrough (10 sections)
  - Section 1: Initial Server Setup & Security
  - Section 2: Apache Installation & Configuration
  - Section 3: MySQL Setup & Hardening
  - Section 4: PHP Installation & Optimization
  - Section 5: Virtual Host Configuration
  - Section 6: SSL/TLS with Let's Encrypt
  - Section 7: Application Deployment
  - Section 8: Backups & Monitoring
  - Section 9: Performance Tuning
  - Section 10: Testing & Verification

### Supporting Documentation
- **[docs/prerequisites.md](docs/prerequisites.md)** - AWS setup, EC2 launch, SSH access
- **[docs/architecture.md](docs/architecture.md)** - Architecture diagrams, component descriptions
- **[docs/troubleshooting.md](docs/troubleshooting.md)** - Common issues, debug commands, log locations

### Configuration Files
- **[configs/apache-vhost.conf](configs/apache-vhost.conf)** - Apache virtual host with security headers
- **[configs/php.ini](configs/php.ini)** - PHP optimizations and security settings
- **[configs/my.cnf](configs/my.cnf)** - MySQL performance tuning

## 💻 Sample Application

The included **Task Management System** demonstrates a complete LAMP stack application:

### Features
- ✅ Create, Read, Update, Delete (CRUD) operations
- ✅ Task filtering and search
- ✅ Priority levels (Low, Medium, High)
- ✅ Status tracking (Pending, In Progress, Completed)
- ✅ Due date management
- ✅ Responsive Bootstrap UI
- ✅ Secure database operations (prepared statements)
- ✅ Environment-based configuration

### Technology Stack
- **Frontend**: Bootstrap 5, Bootstrap Icons
- **Backend**: PHP 8.2+ with PDO
- **Database**: MySQL 8.0 with InnoDB
- **Security**: Input validation, output escaping, CSRF protection

### Screenshots
*Application screenshots will be here after deployment*

## 🎓 What You'll Learn

By completing this project, you'll gain hands-on experience with:

### System Administration
- Linux server setup and user management
- SSH key-based authentication
- Firewall configuration (UFW)
- Service management with systemd
- File permissions and ownership
- Log management and rotation

### Web Server Management
- Apache installation and configuration
- Virtual host setup
- SSL/TLS certificate management
- Security headers implementation
- Performance optimization

### Database Administration
- MySQL installation and security
- User and permission management
- Database backup and restoration
- Query optimization
- Performance tuning

### Application Development
- PHP 8.x features and best practices
- PDO for database connections
- MVC-style organization
- Environment variable usage
- Security best practices

### DevOps Practices
- Automated backup strategies
- Health monitoring and alerts
- Log rotation and management
- Cron job scheduling
- Disaster recovery planning

## 🤝 Contributing

Contributions are welcome! If you find issues or have improvements:

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/improvement`)
3. Commit your changes (`git commit -am 'Add new feature'`)
4. Push to the branch (`git push origin feature/improvement`)
5. Open a Pull Request

### Areas for Contribution
- Additional security configurations
- Performance optimization tips
- More sample applications
- Docker/container version (separate branch)
- Terraform/automation scripts (separate branch)
- Translations
- Video tutorials

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🙏 Acknowledgments

- Ubuntu Community for excellent documentation
- Apache Software Foundation
- MySQL and MariaDB teams
- PHP community
- Let's Encrypt for free SSL certificates
- All contributors and users of this guide

## 📞 Support

- **Issues**: [GitHub Issues](https://github.com/jaymineh/P1_LAMP_Deployment/issues)
- **Documentation**: Check [docs/](docs/) directory
- **Troubleshooting**: See [docs/troubleshooting.md](docs/troubleshooting.md)

---

**⭐ If this project helped you, please give it a star!**

**🔗 Related Projects**:
- LEMP Stack (Nginx variant)
- WordPress on LAMP
- Laravel deployment guide

**📚 Learning Resources**:
- [Apache Documentation](https://httpd.apache.org/docs/2.4/)
- [MySQL Documentation](https://dev.mysql.com/doc/)
- [PHP Documentation](https://www.php.net/docs.php)
- [Ubuntu Server Guide](https://ubuntu.com/server/docs)
