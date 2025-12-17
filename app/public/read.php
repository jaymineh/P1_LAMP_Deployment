<?php
/**
 * Read Task Page
 * View details of a single task
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Start session for flash messages
session_start();

// Get task ID
$id = $_GET['id'] ?? 0;

if (!$id || !is_numeric($id)) {
    setFlashMessage("Invalid task ID.", "danger");
    redirect('index.php');
}

try {
    $pdo = getConnection();
    
    $stmt = $pdo->prepare("SELECT * FROM tasks WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $task = $stmt->fetch();
    
    if (!$task) {
        setFlashMessage("Task not found.", "danger");
        redirect('index.php');
    }
    
} catch (Exception $e) {
    if (APP_DEBUG) {
        die("Error: " . $e->getMessage());
    } else {
        error_log("Task read error: " . $e->getMessage());
        setFlashMessage("An error occurred. Please try again.", "danger");
        redirect('index.php');
    }
}

renderHeader('View Task');
?>

<div class="row">
    <div class="col-md-8 offset-md-2">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3><i class="bi bi-eye"></i> Task Details</h3>
                <div>
                    <a href="update.php?id=<?php echo $task['id']; ?>" class="btn btn-sm btn-warning">
                        <i class="bi bi-pencil"></i> Edit
                    </a>
                    <a href="delete.php?id=<?php echo $task['id']; ?>" class="btn btn-sm btn-danger" 
                       onclick="return confirm('Are you sure you want to delete this task?');">
                        <i class="bi bi-trash"></i> Delete
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div class="mb-4">
                    <h4><?php echo escape($task['title']); ?></h4>
                    <div class="mb-3">
                        <span class="badge bg-<?php echo getStatusClass($task['status']); ?> me-2">
                            <?php echo ucfirst(str_replace('_', ' ', escape($task['status']))); ?>
                        </span>
                        <span class="badge bg-<?php echo getPriorityClass($task['priority']); ?>">
                            <?php echo ucfirst(escape($task['priority'])) . ' Priority'; ?>
                        </span>
                    </div>
                </div>
                
                <div class="mb-3">
                    <h5>Description</h5>
                    <p class="text-muted">
                        <?php echo $task['description'] ? nl2br(escape($task['description'])) : '<em>No description provided</em>'; ?>
                    </p>
                </div>
                
                <hr>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <strong><i class="bi bi-calendar"></i> Due Date:</strong><br>
                        <?php echo $task['due_date'] ? formatDate($task['due_date']) : '<em>Not set</em>'; ?>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <strong><i class="bi bi-clock"></i> Created:</strong><br>
                        <?php echo formatDateTime($task['created_at']); ?>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <strong><i class="bi bi-clock-history"></i> Last Updated:</strong><br>
                        <?php echo formatDateTime($task['updated_at']); ?>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <strong><i class="bi bi-hash"></i> Task ID:</strong><br>
                        #<?php echo $task['id']; ?>
                    </div>
                </div>
                
                <hr>
                
                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                    <a href="index.php" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Back to List
                    </a>
                    <a href="update.php?id=<?php echo $task['id']; ?>" class="btn btn-warning">
                        <i class="bi bi-pencil"></i> Edit Task
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php renderFooter(); ?>
