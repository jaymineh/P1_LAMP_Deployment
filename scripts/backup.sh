#!/bin/bash

###############################################################################
# MySQL Backup Script
# This script creates automated backups of MySQL databases
# Usage: ./backup.sh [database_name] or run via cron for all databases
###############################################################################

# Load environment variables if .env file exists
if [ -f "/var/www/html/.env" ]; then
    export $(cat /var/www/html/.env | grep -v '^#' | xargs)
fi

# Configuration
BACKUP_DIR="${BACKUP_DIR:-/var/backups/mysql}"
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
RETENTION_DAYS="${BACKUP_RETENTION_DAYS:-7}"
LOG_FILE="/var/log/mysql-backup.log"

# MySQL credentials
DB_USER="${DB_USER:-root}"
DB_PASSWORD="${DB_PASSWORD}"
DB_HOST="${DB_HOST:-localhost}"

# S3 Configuration (optional)
S3_BUCKET="${S3_BUCKET}"
AWS_REGION="${AWS_REGION:-us-east-1}"

# Create backup directory if it doesn't exist
mkdir -p "$BACKUP_DIR"

# Function to log messages
log_message() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1" | tee -a "$LOG_FILE"
}

# Function to backup a single database
backup_database() {
    local db_name=$1
    local backup_file="${BACKUP_DIR}/${db_name}_${TIMESTAMP}.sql"
    local compressed_file="${backup_file}.gz"
    
    log_message "Starting backup of database: $db_name"
    
    # Create backup
    if [ -n "$DB_PASSWORD" ]; then
        mysqldump -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASSWORD" \
            --single-transaction --routines --triggers --events \
            "$db_name" > "$backup_file" 2>> "$LOG_FILE"
    else
        mysqldump -h "$DB_HOST" -u "$DB_USER" \
            --single-transaction --routines --triggers --events \
            "$db_name" > "$backup_file" 2>> "$LOG_FILE"
    fi
    
    if [ $? -eq 0 ]; then
        # Compress the backup
        gzip "$backup_file"
        log_message "Backup created successfully: $compressed_file"
        
        # Upload to S3 if configured
        if [ -n "$S3_BUCKET" ] && command -v aws &> /dev/null; then
            log_message "Uploading backup to S3: s3://${S3_BUCKET}/mysql-backups/"
            aws s3 cp "$compressed_file" "s3://${S3_BUCKET}/mysql-backups/" \
                --region "$AWS_REGION" 2>> "$LOG_FILE"
            
            if [ $? -eq 0 ]; then
                log_message "Backup uploaded to S3 successfully"
            else
                log_message "ERROR: Failed to upload backup to S3"
            fi
        fi
        
        return 0
    else
        log_message "ERROR: Backup failed for database: $db_name"
        return 1
    fi
}

# Function to cleanup old backups
cleanup_old_backups() {
    log_message "Cleaning up backups older than $RETENTION_DAYS days"
    find "$BACKUP_DIR" -name "*.sql.gz" -mtime +$RETENTION_DAYS -delete
    log_message "Cleanup completed"
}

# Main execution
log_message "=== MySQL Backup Script Started ==="

# If a database name is provided, backup only that database
if [ $# -eq 1 ]; then
    backup_database "$1"
else
    # Backup all databases except system databases
    if [ -n "$DB_PASSWORD" ]; then
        databases=$(mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASSWORD" \
            -e "SHOW DATABASES;" | grep -Ev "^(Database|information_schema|performance_schema|mysql|sys)$")
    else
        databases=$(mysql -h "$DB_HOST" -u "$DB_USER" \
            -e "SHOW DATABASES;" | grep -Ev "^(Database|information_schema|performance_schema|mysql|sys)$")
    fi
    
    for db in $databases; do
        backup_database "$db"
    done
fi

# Cleanup old backups
cleanup_old_backups

log_message "=== MySQL Backup Script Completed ==="

# Send email notification if configured
if [ -n "$ALERT_EMAIL" ] && command -v mail &> /dev/null; then
    echo "MySQL backup completed at $(date)" | mail -s "MySQL Backup Report" "$ALERT_EMAIL"
fi

exit 0
