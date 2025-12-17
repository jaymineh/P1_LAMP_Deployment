# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.0.0] - 2025-12-17

### Added
- Complete sample PHP CRUD application (task management system)
- SSL/TLS setup with Let's Encrypt documentation
- UFW firewall configuration instructions
- fail2ban setup for intrusion prevention
- SSH hardening steps (disable root login, key-based auth)
- MySQL backup automation script with cron job setup
- AWS S3 backup integration using AWS CLI
- Database restore procedures and script
- Log rotation configuration with logrotate
- Custom health monitoring script for service checks
- Apache configuration files (virtual host templates)
- PHP configuration customizations (php.ini)
- MySQL configuration tuning (my.cnf)
- Comprehensive troubleshooting guide
- Architecture documentation with component descriptions
- Prerequisites documentation (AWS setup, security groups)
- .gitignore for sensitive files and build artifacts
- .env.example template for environment variables
- Helper scripts directory with automated tools

### Changed
- Updated from PHP 7.4 to PHP 8.2/8.3
- Updated MySQL installation to MySQL 8.0 / MariaDB 10.11+
- Updated to Ubuntu 22.04/24.04 LTS
- Reorganized project.md into 10 clear sections
- Enhanced README.md with better structure and quick start guide
- Improved security practices throughout all documentation
- Enhanced MySQL secure installation with modern practices
- Updated Apache configuration for version 2.4.x best practices

### Security
- Added SSL/TLS encryption setup
- Implemented proper file permissions and ownership
- Added fail2ban for intrusion prevention
- Enhanced SSH security configuration
- Implemented environment variables for sensitive data
- Added prepared statements in sample application
- Enhanced MySQL security configuration

## [1.0.0] - 2022-03-16

### Added
- Initial LAMP stack deployment guide
- Basic Apache installation
- MySQL server setup
- PHP 7.4 installation
- Virtual host configuration
- Basic documentation
