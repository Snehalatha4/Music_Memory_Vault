<?php
$host = 'localhost';
$db_user = 'root';
$db_pass = ''; // Default for WAMP stack
$db_name = 'musicvault';

try {
    // Attempt standard connection
    $pdo = new PDO("mysql:host=$host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // Auto-migration: ensure columns for new features exist
    $columns_to_add = [
        'lock_until_date' => 'DATE DEFAULT NULL',
        'latitude' => 'DECIMAL(10, 8) DEFAULT NULL',
        'longitude' => 'DECIMAL(11, 8) DEFAULT NULL',
        'spotify_track_id' => 'VARCHAR(50) DEFAULT NULL'
    ];
    foreach ($columns_to_add as $col => $definition) {
        try {
            $pdo->exec("ALTER TABLE memories ADD COLUMN $col $definition");
        } catch (PDOException $e) {
            // Ignore if column already exists (e.g., error Code 1060: Duplicate column name)
        }
    }
} catch (PDOException $e) {
    // If the database does not exist, try to create it
    try {
        $temp_pdo = new PDO("mysql:host=$host;charset=utf8mb4", $db_user, $db_pass);
        $temp_pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Create database
        $temp_pdo->exec("CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
        
        // Reconnect to the newly created database
        $pdo = new PDO("mysql:host=$host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        
        // Import SQL schema
        $sqlPath = __DIR__ . '/../database/musicvault.sql';
        if (file_exists($sqlPath)) {
            $sql = file_get_contents($sqlPath);
            $pdo->exec($sql);
        }
    } catch (PDOException $e2) {
        die("Database connection failed and auto-setup failed: " . $e2->getMessage() . 
            "<br><br><strong>Tip:</strong> Please ensure MySQL is running in WAMP and verify credentials in <code>includes/connect.php</code>.");
    }
}
?>
