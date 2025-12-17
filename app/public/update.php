<?php
/**
 * Update Task Page
 * Form to edit an existing task
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
    
    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Validate input
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $status = $_POST['status'] ?? 'pending';
        $priority = $_POST['priority'] ?? 'medium';
        $due_date = $_POST['due_date'] ?? null;
        
        // Validation
        $errors = [];
        
        if (empty($title)) {
            $errors[] = "Title is required.";
        }
        
        if (strlen($title) > 255) {
            $errors[] = "Title must be less than 255 characters.";
        }
        
        if (!in_array($status, ['pending', 'in_progress', 'completed'])) {
            $errors[] = "Invalid status.";
        }
        
        if (!in_array($priority, ['low', 'medium', 'high'])) {
            $errors[] = "Invalid priority.";
        }
        
        if (!empty($due_date) && !strtotime($due_date)) {
            $errors[] = "Invalid due date.";
        }
        
        if (empty($errors)) {
            // Update task
            $sql = "UPDATE tasks SET 
                    title = :title,
                    description = :description,
                    status = :status,
                    priority = :priority,
                    due_date = :due_date
                    WHERE id = :id";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':title' => $title,
                ':description' => $description,
                ':status' => $status,
                ':priority' => $priority,
                ':due_date' => $due_date ?: null,
                ':id' => $id
            ]);
            
            setFlashMessage("Task updated successfully!", "success");
            redirect('read.php?id=' . $id);
        }
    }
    
    // Get task data
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
        error_log("Task update error: " . $e->getMessage());
        setFlashMessage("An error occurred. Please try again.", "danger");
        redirect('index.php');
    }
}

renderHeader('Edit Task');
?>

<div class="row">
    <div class="col-md-8 offset-md-2">
        <div class="card">
            <div class="card-header">
                <h3><i class="bi bi-pencil"></i> Edit Task</h3>
            </div>
            <div class="card-body">
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo escape($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <form method="post" action="update.php?id=<?php echo $task['id']; ?>">
                    <div class="mb-3">
                        <label for="title" class="form-label">Title <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="title" name="title" 
                               value="<?php echo escape($_POST['title'] ?? $task['title']); ?>" 
                               required maxlength="255">
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" 
                                  rows="4"><?php echo escape($_POST['description'] ?? $task['description']); ?></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <?php
                                $currentStatus = $_POST['status'] ?? $task['status'];
                                ?>
                                <option value="pending" <?php echo $currentStatus === 'pending' ? 'selected' : ''; ?>>
                                    Pending
                                </option>
                                <option value="in_progress" <?php echo $currentStatus === 'in_progress' ? 'selected' : ''; ?>>
                                    In Progress
                                </option>
                                <option value="completed" <?php echo $currentStatus === 'completed' ? 'selected' : ''; ?>>
                                    Completed
                                </option>
                            </select>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="priority" class="form-label">Priority</label>
                            <select class="form-select" id="priority" name="priority">
                                <?php
                                $currentPriority = $_POST['priority'] ?? $task['priority'];
                                ?>
                                <option value="low" <?php echo $currentPriority === 'low' ? 'selected' : ''; ?>>
                                    Low
                                </option>
                                <option value="medium" <?php echo $currentPriority === 'medium' ? 'selected' : ''; ?>>
                                    Medium
                                </option>
                                <option value="high" <?php echo $currentPriority === 'high' ? 'selected' : ''; ?>>
                                    High
                                </option>
                            </select>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="due_date" class="form-label">Due Date</label>
                            <input type="date" class="form-control" id="due_date" name="due_date" 
                                   value="<?php echo escape($_POST['due_date'] ?? $task['due_date']); ?>">
                        </div>
                    </div>
                    
                    <div class="alert alert-info">
                        <small>
                            <strong>Last updated:</strong> <?php echo formatDateTime($task['updated_at']); ?>
                        </small>
                    </div>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="read.php?id=<?php echo $task['id']; ?>" class="btn btn-secondary">
                            <i class="bi bi-x-circle"></i> Cancel
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle"></i> Update Task
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php renderFooter(); ?>
