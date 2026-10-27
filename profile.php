<?php
require_once __DIR__ . '/includes/connect.php';
require_once __DIR__ . '/includes/session.php';

check_login();

$logged_user = get_logged_in_user();
$uid = $logged_user['id'];

$error = '';
$success = '';

// Handle Profile Update POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    
    if (empty($name) || empty($email)) {
        $error = 'Name and email are required.';
    } else {
        try {
            // Check if email already taken by someone else
            $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
            $stmt->execute([$email, $uid]);
            if ($stmt->fetch()) {
                $error = 'Email is already in use by another account.';
            } else {
                $photo_name = $logged_user['profile_photo'];
                
                // Photo upload processing
                if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
                    $file_tmp = $_FILES['profile_photo']['tmp_name'];
                    $file_name = $_FILES['profile_photo']['name'];
                    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                    $allowed_exts = ['jpg', 'jpeg', 'png', 'gif'];
                    
                    if (in_array($file_ext, $allowed_exts)) {
                        // Delete old profile photo if not default
                        if ($logged_user['profile_photo'] !== 'default_profile.png' && file_exists(__DIR__ . '/uploads/' . $logged_user['profile_photo'])) {
                            @unlink(__DIR__ . '/uploads/' . $logged_user['profile_photo']);
                        }
                        
                        $photo_name = 'profile_' . uniqid() . '.' . $file_ext;
                        move_uploaded_file($file_tmp, __DIR__ . '/uploads/' . $photo_name);
                    } else {
                        $error = 'Invalid image format. Allowed: JPG, PNG, GIF.';
                    }
                }
                
                if (empty($error)) {
                    $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, profile_photo = ? WHERE user_id = ?");
                    if ($stmt->execute([$name, $email, $photo_name, $uid])) {
                        $_SESSION['user_name'] = $name;
                        $_SESSION['user_email'] = $email;
                        $_SESSION['profile_photo'] = $photo_name;
                        $logged_user = get_logged_in_user();
                        $success = 'Profile updated successfully!';
                    } else {
                        $error = 'Failed to update database.';
                    }
                }
            }
        } catch (PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}

// Fetch Profile Statistics
try {
    // 1. Account details
    $stmt = $pdo->prepare("SELECT created_at FROM users WHERE user_id = ?");
    $stmt->execute([$uid]);
    $created_at = $stmt->fetch()['created_at'];
    
    // 2. Count distinct artists
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT s.artist_id) as count 
        FROM memories m
        JOIN songs s ON m.song_id = s.song_id
        WHERE m.user_id = ?
    ");
    $stmt->execute([$uid]);
    $distinct_artists = $stmt->fetch()['count'];
    
    // 3. Count distinct albums
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT s.album_id) as count 
        FROM memories m
        JOIN songs s ON m.song_id = s.song_id
        WHERE m.user_id = ? AND s.album_id IS NOT NULL
    ");
    $stmt->execute([$uid]);
    $distinct_albums = $stmt->fetch()['count'];
    
    // 4. Count distinct genres
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT s.genre_id) as count 
        FROM memories m
        JOIN songs s ON m.song_id = s.song_id
        WHERE m.user_id = ? AND s.genre_id IS NOT NULL
    ");
    $stmt->execute([$uid]);
    $distinct_genres = $stmt->fetch()['count'];
    
} catch (PDOException $e) {
    // Stat fetches failed
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Settings - Music Memory Vault</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="glow-orb orb-1"></div>
    <div class="glow-orb orb-2"></div>
    
    <?php include __DIR__ . '/includes/navbar.php'; ?>

    <main class="container">
        <div class="dashboard-header">
            <div class="dashboard-title">
                <h2>Profile Settings</h2>
                <p>Manage your account preferences and view collection statistics</p>
            </div>
            <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
        </div>
        
        <div class="memory-detail-layout" style="margin-top: 2rem;">
            <!-- Settings Form Card -->
            <div class="card">
                <h3 style="margin-bottom: 1.5rem; color: #ff007f; font-weight: 700; border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem;">
                    ✏️ Update Profile Info
                </h3>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>
                
                <form action="profile.php" method="POST" enctype="multipart/form-data">
                    <div style="display: flex; flex-direction: column; align-items: center; margin-bottom: 2rem; gap: 1rem;">
                        <img src="uploads/<?php echo htmlspecialchars($logged_user['profile_photo']); ?>" alt="Profile Avatar" style="width: 120px; height: 120px; border-radius: 50%; object-fit: cover; border: 4px solid #8a2be2;" onerror="this.src='images/default_profile.png'">
                        <span style="font-size: 0.85rem; color: var(--text-muted);">Change Photo below</span>
                    </div>
                    
                    <div class="form-group">
                        <label for="name">Full Name</label>
                        <input type="text" name="name" id="name" class="form-control" value="<?php echo htmlspecialchars($logged_user['name']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" name="email" id="email" class="form-control" value="<?php echo htmlspecialchars($logged_user['email']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="profile_photo">Upload New Avatar Photo</label>
                        <input type="file" name="profile_photo" id="profile_photo" class="form-control" accept="image/*">
                    </div>
                    
                    <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 1.5rem;">Update Profile Settings</button>
                </form>
            </div>
            
            <!-- Statistics Sidebar Card -->
            <div class="card" style="height: fit-content;">
                <h3 style="margin-bottom: 1.5rem; color: #00f2fe; font-weight: 700; border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem;">
                    📊 Vault Statistics
                </h3>
                
                <div class="info-item">
                    <span class="info-label">Member Since</span>
                    <span class="info-val"><?php echo date('d M Y', strtotime($created_at)); ?></span>
                </div>
                
                <div class="info-item">
                    <span class="info-label">Distinct Artists Saved</span>
                    <span class="info-val" style="color: #ff007f; font-size: 1.1rem;"><?php echo $distinct_artists; ?></span>
                </div>
                
                <div class="info-item">
                    <span class="info-label">Albums Represented</span>
                    <span class="info-val" style="color: #00f2fe; font-size: 1.1rem;"><?php echo $distinct_albums; ?></span>
                </div>
                
                <div class="info-item">
                    <span class="info-label">Genres Tracked</span>
                    <span class="info-val" style="color: #8a2be2; font-size: 1.1rem;"><?php echo $distinct_genres; ?></span>
                </div>
                
                <div style="margin-top: 2rem; text-align: center; color: var(--text-muted); font-size: 0.9rem;">
                    💡 <em>Tip: Add more songs with diverse artist details to see your stats grow!</em>
                </div>
            </div>
        </div>
    </main>

    <footer>
        <p>&copy; <?php echo date('Y'); ?> Music Memory Vault. WAMP Mini Project.</p>
    </footer>
</body>
</html>
