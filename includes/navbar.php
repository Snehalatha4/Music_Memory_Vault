<?php
require_once __DIR__ . '/session.php';
$current_page = basename($_SERVER['PHP_SELF']);
$logged_user = get_logged_in_user();
?>
<nav class="navbar">
    <div class="nav-container">
        <a href="index.php" class="logo">
            <span>🎵</span> Music Memory Vault
        </a>
        
        <ul class="nav-links">
            <?php if ($logged_user): ?>
                <li>
                    <a href="dashboard.php" class="<?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">
                        Dashboard
                    </a>
                </li>
                <li>
                    <a href="addMemory.php" class="<?php echo $current_page == 'addMemory.php' ? 'active' : ''; ?>">
                        ➕ Add Memory
                    </a>
                </li>
                <li>
                    <a href="memories.php" class="<?php echo $current_page == 'memories.php' ? 'active' : ''; ?>">
                        📸 My Memories
                    </a>
                </li>
                <li>
                    <a href="map.php" class="<?php echo $current_page == 'map.php' ? 'active' : ''; ?>">
                        🗺️ Memory Map
                    </a>
                </li>
                <li>
                    <a href="milestones.php" class="<?php echo $current_page == 'milestones.php' ? 'active' : ''; ?>">
                        🏆 Milestones
                    </a>
                </li>
                <li>
                    <a href="timeline.php" class="<?php echo $current_page == 'timeline.php' ? 'active' : ''; ?>">
                        📅 Timeline
                    </a>
                </li>
                <li>
                    <a href="analytics.php" class="<?php echo $current_page == 'analytics.php' ? 'active' : ''; ?>">
                        📊 Analytics
                    </a>
                </li>
                
                <div class="nav-profile">
                    <a href="profile.php" style="padding: 0; display: flex; align-items: center; gap: 0.5rem;" class="<?php echo $current_page == 'profile.php' ? 'active' : ''; ?>">
                        <img src="uploads/<?php echo htmlspecialchars($logged_user['profile_photo']); ?>" alt="Profile" class="nav-avatar" onerror="this.src='images/default_profile.png'">
                        <span><?php echo htmlspecialchars($logged_user['name']); ?></span>
                    </a>
                    <a href="logout.php" class="btn btn-sm logout-btn">Logout</a>
                </div>
            <?php else: ?>
                <li>
                    <a href="index.php" class="<?php echo $current_page == 'index.php' ? 'active' : ''; ?>">Home</a>
                </li>
                <li>
                    <a href="login.php" class="btn btn-sm btn-secondary">Login</a>
                </li>
                <li>
                    <a href="register.php" class="btn btn-sm btn-primary">Register</a>
                </li>
            <?php endif; ?>
        </ul>
    </div>
</nav>
