<?php
/**
 * Task List Page
 * Displays all tasks with filtering options
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Start session for flash messages
session_start();

try {
    $pdo = getConnection();
    
    // Get filter parameters
    $status = $_GET['status'] ?? '';
    $priority = $_GET['priority'] ?? '';
    $search = $_GET['search'] ?? '';
    
    // Build query
    $sql = "SELECT * FROM tasks WHERE 1=1";
    $params = [];
    
    if ($status) {
        $sql .= " AND status = :status";
        $params[':status'] = $status;
    }
    
    if ($priority) {
        $sql .= " AND priority = :priority";
        $params[':priority'] = $priority;
    }
    
    if ($search) {
        $sql .= " AND (title LIKE :search OR description LIKE :search)";
        $params[':search'] = "%$search%";
    }
    
    $sql .= " ORDER BY 
        CASE priority
            WHEN 'high' THEN 1
            WHEN 'medium' THEN 2
            WHEN 'low' THEN 3
        END,
        due_date ASC,
        created_at DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $tasks = $stmt->fetchAll();
    
} catch (Exception $e) {
    if (APP_DEBUG) {
        die("Error: " . $e->getMessage());
    } else {
        error_log("Task list error: " . $e->getMessage());
        die("An error occurred. Please try again later.");
    }
}

renderHeader('Task Manager');
?>

<div class="row mb-4">
    <div class="col-md-12">
        <h1 class="mb-3">
            <i class="bi bi-list-check"></i> Task Manager
        </h1>
        
        <!-- Filter Form -->
        <form method="get" class="row g-3 mb-4">
            <div class="col-md-3">
                <input type="text" name="search" class="form-control" placeholder="Search tasks..." value="<?php echo escape($search); ?>">
            </div>
            <div class="col-md-2">
                <select name="status" class="form-select">
                    <option value="">All Status</option>
                    <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="in_progress" <?php echo $status === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                    <option value="completed" <?php echo $status === 'completed' ? 'selected' : ''; ?>>Completed</option>
                </select>
            </div>
            <div class="col-md-2">
                <select name="priority" class="form-select">
                    <option value="">All Priority</option>
                    <option value="high" <?php echo $priority === 'high' ? 'selected' : ''; ?>>High</option>
                    <option value="medium" <?php echo $priority === 'medium' ? 'selected' : ''; ?>>Medium</option>
                    <option value="low" <?php echo $priority === 'low' ? 'selected' : ''; ?>>Low</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-filter"></i> Filter
                </button>
            </div>
            <div class="col-md-1">
                <a href="index.php" class="btn btn-secondary w-100">
                    <i class="bi bi-x-circle"></i> Clear
                </a>
            </div>
            <div class="col-md-2 text-end">
                <a href="create.php" class="btn btn-success w-100">
                    <i class="bi bi-plus-circle"></i> New Task
                </a>
            </div>
        </form>
        
        <!-- Task Statistics -->
        <div class="row mb-3">
            <div class="col-md-12">
                <?php
                $total = count($tasks);
                $pending = count(array_filter($tasks, fn($t) => $t['status'] === 'pending'));
                $inProgress = count(array_filter($tasks, fn($t) => $t['status'] === 'in_progress'));
                $completed = count(array_filter($tasks, fn($t) => $t['status'] === 'completed'));
                ?>
                <div class="alert alert-info">
                    <strong>Total Tasks:</strong> <?php echo $total; ?> |
                    <strong>Pending:</strong> <?php echo $pending; ?> |
                    <strong>In Progress:</strong> <?php echo $inProgress; ?> |
                    <strong>Completed:</strong> <?php echo $completed; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <?php if (empty($tasks)): ?>
        <div class="col-md-12">
            <div class="alert alert-warning">
                <i class="bi bi-info-circle"></i> No tasks found. <a href="create.php">Create your first task</a>!
            </div>
        </div>
    <?php else: ?>
        <?php foreach ($tasks as $task): ?>
            <div class="col-md-6 col-lg-4">
                <div class="card task-card">
                    <div class="card-body">
                        <h5 class="card-title">
                            <?php echo escape($task['title']); ?>
                        </h5>
                        <p class="card-text">
                            <?php 
                            $description = $task['description'];
                            echo escape(strlen($description) > 100 ? substr($description, 0, 100) . '...' : $description); 
                            ?>
                        </p>
                        <div class="mb-2">
                            <span class="badge bg-<?php echo getStatusClass($task['status']); ?>">
                                <?php echo ucfirst(str_replace('_', ' ', escape($task['status']))); ?>
                            </span>
                            <span class="badge bg-<?php echo getPriorityClass($task['priority']); ?>">
                                <?php echo ucfirst(escape($task['priority'])); ?>
                            </span>
                        </div>
                        <?php if ($task['due_date']): ?>
                            <p class="card-text">
                                <small class="text-muted">
                                    <i class="bi bi-calendar"></i> Due: <?php echo formatDate($task['due_date']); ?>
                                </small>
                            </p>
                        <?php endif; ?>
                        <div class="btn-group w-100" role="group">
                            <a href="read.php?id=<?php echo $task['id']; ?>" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-eye"></i> View
                            </a>
                            <a href="update.php?id=<?php echo $task['id']; ?>" class="btn btn-sm btn-outline-warning">
                                <i class="bi bi-pencil"></i> Edit
                            </a>
                            <a href="delete.php?id=<?php echo $task['id']; ?>" class="btn btn-sm btn-outline-danger" 
                               onclick="return confirm('Are you sure you want to delete this task?');">
                                <i class="bi bi-trash"></i> Delete
                            </a>
                        </div>
                    </div>
                    <div class="card-footer text-muted">
                        <small>Created: <?php echo formatDateTime($task['created_at']); ?></small>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php renderFooter(); ?>
