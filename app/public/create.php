<?php
/**
 * Create Task Page
 * Form to create a new task
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Start session for flash messages
session_start();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo = getConnection();
        
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
            // Insert task
            $sql = "INSERT INTO tasks (title, description, status, priority, due_date) 
                    VALUES (:title, :description, :status, :priority, :due_date)";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':title' => $title,
                ':description' => $description,
                ':status' => $status,
                ':priority' => $priority,
                ':due_date' => $due_date ?: null
            ]);
            
            setFlashMessage("Task created successfully!", "success");
            redirect('index.php');
        }
        
    } catch (Exception $e) {
        if (APP_DEBUG) {
            $errors[] = "Error: " . $e->getMessage();
        } else {
            error_log("Task creation error: " . $e->getMessage());
            $errors[] = "An error occurred. Please try again.";
        }
    }
}

renderHeader('Create Task');
?>

<div class="row">
    <div class="col-md-8 offset-md-2">
        <div class="card">
            <div class="card-header">
                <h3><i class="bi bi-plus-circle"></i> Create New Task</h3>
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
                
                <form method="post" action="create.php">
                    <div class="mb-3">
                        <label for="title" class="form-label">Title <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="title" name="title" 
                               value="<?php echo escape($_POST['title'] ?? ''); ?>" 
                               required maxlength="255">
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" 
                                  rows="4"><?php echo escape($_POST['description'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="pending" <?php echo ($_POST['status'] ?? 'pending') === 'pending' ? 'selected' : ''; ?>>
                                    Pending
                                </option>
                                <option value="in_progress" <?php echo ($_POST['status'] ?? '') === 'in_progress' ? 'selected' : ''; ?>>
                                    In Progress
                                </option>
                                <option value="completed" <?php echo ($_POST['status'] ?? '') === 'completed' ? 'selected' : ''; ?>>
                                    Completed
                                </option>
                            </select>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="priority" class="form-label">Priority</label>
                            <select class="form-select" id="priority" name="priority">
                                <option value="low" <?php echo ($_POST['priority'] ?? '') === 'low' ? 'selected' : ''; ?>>
                                    Low
                                </option>
                                <option value="medium" <?php echo ($_POST['priority'] ?? 'medium') === 'medium' ? 'selected' : ''; ?>>
                                    Medium
                                </option>
                                <option value="high" <?php echo ($_POST['priority'] ?? '') === 'high' ? 'selected' : ''; ?>>
                                    High
                                </option>
                            </select>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="due_date" class="form-label">Due Date</label>
                            <input type="date" class="form-control" id="due_date" name="due_date" 
                                   value="<?php echo escape($_POST['due_date'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="index.php" class="btn btn-secondary">
                            <i class="bi bi-x-circle"></i> Cancel
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle"></i> Create Task
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php renderFooter(); ?>
