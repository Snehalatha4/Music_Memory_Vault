<?php
require_once __DIR__ . '/includes/connect.php';
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/db_helpers.php';

check_login();

$logged_user = get_logged_in_user();
$memory_id = intval($_GET['id'] ?? 0);

if ($memory_id) {
    try {
        // Retrieve memory photo details to clean up files and check ownership
        $stmt = $pdo->prepare("SELECT photo FROM memories WHERE memory_id = ? AND user_id = ?");
        $stmt->execute([$memory_id, $logged_user['id']]);
        $memory = $stmt->fetch();
        
        if ($memory) {
            // Delete file if exists
            if ($memory['photo'] && file_exists(__DIR__ . '/uploads/memoryPhotos/' . $memory['photo'])) {
                @unlink(__DIR__ . '/uploads/memoryPhotos/' . $memory['photo']);
            }
            
            // Delete memory record
            $deleteStmt = $pdo->prepare("DELETE FROM memories WHERE memory_id = ?");
            if ($deleteStmt->execute([$memory_id])) {
                // Re-run python analytics to update charts
                trigger_python_analytics();
            }
        }
    } catch (PDOException $e) {
        // Fail silently or handle error
    }
}

// Redirect back to dashboard or memories list
header("Location: memories.php");
exit();
?>
