#!/bin/bash

###############################################################################
# Database Restore Script
# Restores MySQL database from backup file
# Usage: ./restore.sh <backup_file.sql.gz> [database_name]
###############################################################################

# Load environment variables if .env file exists
if [ -f "/var/www/html/.env" ]; then
    export $(cat /var/www/html/.env | grep -v '^#' | xargs)
fi

# Configuration
LOG_FILE="/var/log/mysql-restore.log"
DB_USER="${DB_USER:-root}"
DB_PASSWORD="${DB_PASSWORD}"
DB_HOST="${DB_HOST:-localhost}"

# Function to log messages
log_message() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1" | tee -a "$LOG_FILE"
}

# Check if backup file is provided
if [ $# -lt 1 ]; then
    echo "Usage: $0 <backup_file.sql.gz> [database_name]"
    echo ""
    echo "Examples:"
    echo "  $0 /var/backups/mysql/mydb_20231217_120000.sql.gz"
    echo "  $0 /var/backups/mysql/mydb_20231217_120000.sql.gz mydb"
    echo ""
    echo "Available backups:"
    find /var/backups/mysql -name "*.sql.gz" -type f -printf "  %p (%TY-%Tm-%Td %TH:%TM)\n" 2>/dev/null | sort -r | head -10
    exit 1
fi

BACKUP_FILE=$1
DATABASE_NAME=$2

# Check if backup file exists
if [ ! -f "$BACKUP_FILE" ]; then
    echo "ERROR: Backup file not found: $BACKUP_FILE"
    exit 1
fi

# Extract database name from filename if not provided
if [ -z "$DATABASE_NAME" ]; then
    DATABASE_NAME=$(basename "$BACKUP_FILE" | sed 's/_[0-9]*\.sql\.gz$//')
    echo "Database name not provided. Extracted from filename: $DATABASE_NAME"
fi

log_message "=== Database Restore Started ==="
log_message "Backup file: $BACKUP_FILE"
log_message "Database: $DATABASE_NAME"

# Confirmation prompt
echo ""
echo "========================================="
echo "  Database Restore"
echo "========================================="
echo ""
echo "This will restore the following:"
echo "  Backup file: $BACKUP_FILE"
echo "  To database: $DATABASE_NAME"
echo ""
echo "WARNING: This will overwrite the current database!"
echo ""
read -p "Are you sure you want to continue? (yes/no): " -r
echo ""

if [[ ! $REPLY =~ ^[Yy][Ee][Ss]$ ]]; then
    echo "Restore cancelled."
    log_message "Restore cancelled by user"
    exit 0
fi

# Create database if it doesn't exist
log_message "Checking if database exists..."

if [ -n "$DB_PASSWORD" ]; then
    DB_EXISTS=$(mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASSWORD" \
        -e "SHOW DATABASES LIKE '$DATABASE_NAME';" | grep "$DATABASE_NAME")
else
    DB_EXISTS=$(mysql -h "$DB_HOST" -u "$DB_USER" \
        -e "SHOW DATABASES LIKE '$DATABASE_NAME';" | grep "$DATABASE_NAME")
fi

if [ -z "$DB_EXISTS" ]; then
    log_message "Database does not exist. Creating database: $DATABASE_NAME"
    
    if [ -n "$DB_PASSWORD" ]; then
        mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASSWORD" \
            -e "CREATE DATABASE $DATABASE_NAME CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
    else
        mysql -h "$DB_HOST" -u "$DB_USER" \
            -e "CREATE DATABASE $DATABASE_NAME CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
    fi
    
    if [ $? -ne 0 ]; then
        log_message "ERROR: Failed to create database"
        exit 1
    fi
else
    log_message "Database exists: $DATABASE_NAME"
fi

# Restore the database
log_message "Starting database restore..."
echo "Restoring database... This may take a few minutes..."

if [ -n "$DB_PASSWORD" ]; then
    gunzip < "$BACKUP_FILE" | mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASSWORD" "$DATABASE_NAME" 2>> "$LOG_FILE"
else
    gunzip < "$BACKUP_FILE" | mysql -h "$DB_HOST" -u "$DB_USER" "$DATABASE_NAME" 2>> "$LOG_FILE"
fi

if [ $? -eq 0 ]; then
    echo ""
    echo "========================================="
    echo "  Database Restore Completed!"
    echo "========================================="
    echo ""
    log_message "Database restored successfully: $DATABASE_NAME"
    
    # Get table count
    if [ -n "$DB_PASSWORD" ]; then
        TABLE_COUNT=$(mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASSWORD" \
            -e "USE $DATABASE_NAME; SHOW TABLES;" | wc -l)
    else
        TABLE_COUNT=$(mysql -h "$DB_HOST" -u "$DB_USER" \
            -e "USE $DATABASE_NAME; SHOW TABLES;" | wc -l)
    fi
    
    echo "✓ Database: $DATABASE_NAME"
    echo "✓ Tables restored: $((TABLE_COUNT - 1))"
    echo "✓ Backup file: $BACKUP_FILE"
    echo ""
    log_message "=== Database Restore Completed Successfully ==="
else
    echo ""
    echo "========================================="
    echo "  Database Restore Failed!"
    echo "========================================="
    echo ""
    echo "ERROR: Failed to restore database. Check log file: $LOG_FILE"
    log_message "ERROR: Database restore failed"
    log_message "=== Database Restore Failed ==="
    exit 1
fi

exit 0
