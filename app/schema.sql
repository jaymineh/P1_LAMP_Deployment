-- Task Manager Database Schema
-- LAMP Stack Demo Application

-- Create tasks table
CREATE TABLE IF NOT EXISTS tasks (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    status ENUM('pending', 'in_progress', 'completed') DEFAULT 'pending',
    priority ENUM('low', 'medium', 'high') DEFAULT 'medium',
    due_date DATE NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_priority (priority),
    INDEX idx_due_date (due_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert sample data
INSERT INTO tasks (title, description, status, priority, due_date) VALUES
('Complete LAMP Stack Setup', 'Follow the project.md guide to set up a complete LAMP stack with SSL, monitoring, and backups.', 'in_progress', 'high', DATE_ADD(CURDATE(), INTERVAL 7 DAY)),
('Configure Database Backups', 'Set up automated MySQL backups using the backup.sh script and configure retention policy.', 'pending', 'high', DATE_ADD(CURDATE(), INTERVAL 5 DAY)),
('Implement Security Hardening', 'Review and apply all security best practices including SSL, firewall rules, and fail2ban configuration.', 'pending', 'high', DATE_ADD(CURDATE(), INTERVAL 10 DAY)),
('Set Up Monitoring', 'Configure health-check script and set up monitoring for Apache, MySQL, and PHP-FPM.', 'pending', 'medium', DATE_ADD(CURDATE(), INTERVAL 14 DAY)),
('Performance Optimization', 'Tune Apache MPM, PHP-FPM, and MySQL settings for optimal performance.', 'pending', 'medium', DATE_ADD(CURDATE(), INTERVAL 21 DAY)),
('Write Documentation', 'Document the deployment process and create troubleshooting guides.', 'completed', 'low', DATE_SUB(CURDATE(), INTERVAL 2 DAY)),
('Test Application Features', 'Test all CRUD operations and ensure database connectivity is working.', 'in_progress', 'medium', DATE_ADD(CURDATE(), INTERVAL 3 DAY)),
('Deploy to Production', 'Final deployment to production server with all configurations applied.', 'pending', 'high', DATE_ADD(CURDATE(), INTERVAL 30 DAY));
