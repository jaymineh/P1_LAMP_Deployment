<?php
/**
 * Helper Functions
 * Reusable functions for the application
 */

/**
 * Sanitize output for HTML display
 */
function escape($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

/**
 * Redirect to a URL
 */
function redirect($url) {
    header("Location: $url");
    exit;
}

/**
 * Get status badge class
 */
function getStatusClass($status) {
    switch ($status) {
        case 'completed':
            return 'success';
        case 'in_progress':
            return 'warning';
        case 'pending':
        default:
            return 'secondary';
    }
}

/**
 * Get priority badge class
 */
function getPriorityClass($priority) {
    switch ($priority) {
        case 'high':
            return 'danger';
        case 'medium':
            return 'warning';
        case 'low':
        default:
            return 'info';
    }
}

/**
 * Format date for display
 */
function formatDate($date) {
    if (empty($date)) {
        return 'N/A';
    }
    return date('M d, Y', strtotime($date));
}

/**
 * Format datetime for display
 */
function formatDateTime($datetime) {
    if (empty($datetime)) {
        return 'N/A';
    }
    return date('M d, Y g:i A', strtotime($datetime));
}

/**
 * Get flash message and clear it
 */
function getFlashMessage() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $message;
    }
    
    return null;
}

/**
 * Set flash message
 */
function setFlashMessage($message, $type = 'success') {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    $_SESSION['flash_message'] = [
        'message' => $message,
        'type' => $type
    ];
}

/**
 * Render page header
 */
function renderHeader($title = 'Task Manager') {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo escape($title . ' - ' . APP_NAME); ?></title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
        <style>
            body { padding-top: 20px; }
            .task-card { margin-bottom: 1rem; }
            .flash-message { position: fixed; top: 80px; right: 20px; z-index: 1050; min-width: 300px; }
        </style>
    </head>
    <body>
        <nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
            <div class="container">
                <a class="navbar-brand" href="index.php">
                    <i class="bi bi-list-check"></i> <?php echo escape(APP_NAME); ?>
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item">
                            <a class="nav-link" href="index.php">All Tasks</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="create.php">
                                <i class="bi bi-plus-circle"></i> New Task
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
        
        <div class="container">
            <?php
            $flash = getFlashMessage();
            if ($flash): ?>
                <div class="alert alert-<?php echo escape($flash['type']); ?> alert-dismissible fade show flash-message" role="alert">
                    <?php echo escape($flash['message']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
    <?php
}

/**
 * Render page footer
 */
function renderFooter() {
    ?>
        </div>
        <footer class="mt-5 py-3 bg-light">
            <div class="container text-center text-muted">
                <p>&copy; <?php echo date('Y'); ?> <?php echo escape(APP_NAME); ?>. LAMP Stack Demonstration Project.</p>
            </div>
        </footer>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    </body>
    </html>
    <?php
}
