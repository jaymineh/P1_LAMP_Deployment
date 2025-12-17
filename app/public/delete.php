<?php
/**
 * Delete Task Page
 * Handles task deletion
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
    
    // Check if task exists
    $stmt = $pdo->prepare("SELECT id, title FROM tasks WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $task = $stmt->fetch();
    
    if (!$task) {
        setFlashMessage("Task not found.", "danger");
        redirect('index.php');
    }
    
    // Delete the task
    $stmt = $pdo->prepare("DELETE FROM tasks WHERE id = :id");
    $stmt->execute([':id' => $id]);
    
    setFlashMessage("Task '" . $task['title'] . "' has been deleted successfully.", "success");
    redirect('index.php');
    
} catch (Exception $e) {
    if (APP_DEBUG) {
        die("Error: " . $e->getMessage());
    } else {
        error_log("Task deletion error: " . $e->getMessage());
        setFlashMessage("An error occurred while deleting the task. Please try again.", "danger");
        redirect('index.php');
    }
}
