<?php
require_once __DIR__ . '/includes/connect.php';
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/db_helpers.php';

check_login();

$logged_user = get_logged_in_user();
$uid = $logged_user['id'];

// Dynamically run the python script to regenerate charts for this user
// This satisfies: "Using Python to connect to MySQL and search for elements / process data"
try {
    $pythonPath = __DIR__ . '/python/analytics.py';
    if (file_exists($pythonPath)) {
        // Execute the script
        if (substr(php_uname(), 0, 7) == "Windows") {
            exec("python " . escapeshellarg($pythonPath) . " 2>&1", $output, $return_var);
        } else {
            exec("python3 " . escapeshellarg($pythonPath) . " 2>&1", $output, $return_var);
        }
    }
} catch (Exception $e) {
    // Fail silently, fall back to cached or default images
}

// Calculate text statistics in PHP as backup and side information
$fav_genre = 'None';
$most_visited = 'None';
$avg_songs_month = 0;
$top_year = 'None';
$vibe_sentiment = 'Neutral';

try {
    // 1. Favorite Genre
    $stmt = $pdo->prepare("
        SELECT g.genre_name, COUNT(*) as count 
        FROM memories m
        JOIN songs s ON m.song_id = s.song_id
        JOIN genres g ON s.genre_id = g.genre_id
        WHERE m.user_id = ?
        GROUP BY g.genre_id
        ORDER BY count DESC LIMIT 1
    ");
    $stmt->execute([$uid]);
    $genre_row = $stmt->fetch();
    if ($genre_row) {
        $fav_genre = $genre_row['genre_name'];
    }
    
    // 2. Most Visited Location
    $stmt = $pdo->prepare("
        SELECT location, COUNT(*) as count 
        FROM memories 
        WHERE user_id = ? AND location IS NOT NULL AND location != ''
        GROUP BY location
        ORDER BY count DESC LIMIT 1
    ");
    $stmt->execute([$uid]);
    $loc_row = $stmt->fetch();
    if ($loc_row) {
        $most_visited = $loc_row['location'];
    }
    
    // 3. Most Active Year
    $stmt = $pdo->prepare("
        SELECT YEAR(memory_date) as year, COUNT(*) as count 
        FROM memories 
        WHERE user_id = ?
        GROUP BY year
        ORDER BY count DESC LIMIT 1
    ");
    $stmt->execute([$uid]);
    $year_row = $stmt->fetch();
    if ($year_row) {
        $top_year = $year_row['year'];
    }
    
    // 4. Average memories per month
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM memories WHERE user_id = ?");
    $stmt->execute([$uid]);
    $total_cnt = $stmt->fetch()['total'];
    
    $stmt = $pdo->prepare("SELECT COUNT(DISTINCT DATE_FORMAT(memory_date, '%Y-%m')) as months FROM memories WHERE user_id = ?");
    $stmt->execute([$uid]);
    $months_cnt = $stmt->fetch()['months'];
    
    if ($months_cnt > 0) {
        $avg_songs_month = round($total_cnt / $months_cnt, 1);
    }
    
    // 5. Overall Vibe (average sentiment score)
    $stmt = $pdo->prepare("
        SELECT sentiment_label, COUNT(*) as count 
        FROM memories 
        WHERE user_id = ? AND sentiment_label IS NOT NULL
        GROUP BY sentiment_label
        ORDER BY count DESC LIMIT 1
    ");
    $stmt->execute([$uid]);
    $vibe_row = $stmt->fetch();
    if ($vibe_row) {
        $vibe_sentiment = $vibe_row['sentiment_label'];
    }
    
} catch (PDOException $e) {
    // Stats calculation failed
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics - Music Memory Vault</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="glow-orb orb-1"></div>
    <div class="glow-orb orb-2"></div>
    <div class="glow-orb orb-3"></div>

    <?php include __DIR__ . '/includes/navbar.php'; ?>

    <main class="container">
        <div class="dashboard-header">
            <div class="dashboard-title">
                <h2>Vault Intelligence & Analytics</h2>
                <p>Python-powered statistical insights and sentiment summaries</p>
            </div>
            <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
        </div>
        
        <!-- Text stats cards -->
        <div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); margin-bottom: 2rem;">
            <div class="card stat-card" style="padding: 1.2rem;">
                <div class="stat-value" style="font-size: 1.4rem; color: #ff007f;"><?php echo htmlspecialchars($fav_genre); ?></div>
                <div class="stat-label" style="font-size: 0.75rem;">Favorite Genre</div>
            </div>
            
            <div class="card stat-card" style="padding: 1.2rem;">
                <div class="stat-value" style="font-size: 1.4rem; color: #00f2fe;"><?php echo htmlspecialchars($most_visited); ?></div>
                <div class="stat-label" style="font-size: 0.75rem;">Top Location</div>
            </div>
            
            <div class="card stat-card" style="padding: 1.2rem;">
                <div class="stat-value" style="font-size: 1.4rem; color: #8a2be2;"><?php echo htmlspecialchars($top_year); ?></div>
                <div class="stat-label" style="font-size: 0.75rem;">Most Active Year</div>
            </div>
            
            <div class="card stat-card" style="padding: 1.2rem;">
                <div class="stat-value" style="font-size: 1.4rem; color: #7fff00;"><?php echo $avg_songs_month; ?></div>
                <div class="stat-label" style="font-size: 0.75rem;">Avg Memories / Month</div>
            </div>
            
            <div class="card stat-card" style="padding: 1.2rem;">
                <div class="stat-value" style="font-size: 1.4rem; color: #ffd700;"><?php echo htmlspecialchars($vibe_sentiment); ?></div>
                <div class="stat-label" style="font-size: 0.75rem;">Overall Vault Vibe</div>
            </div>
        </div>
        
        <!-- Charts Grid -->
        <div class="analytics-grid">
            <!-- Pie Chart -->
            <div class="chart-card card">
                <h3>🎭 Emotional Distribution</h3>
                <?php if (file_exists(__DIR__ . '/uploads/charts/emotions_pie.png')): ?>
                    <img src="uploads/charts/emotions_pie.png?t=<?php echo time(); ?>" alt="Emotions Pie Chart" class="chart-image-placeholder">
                <?php else: ?>
                    <div style="height: 300px; display: flex; align-items: center; justify-content: center; color: var(--text-muted); flex-direction: column; gap: 1rem;">
                        <span style="font-size: 2.5rem;">📊</span>
                        <span>Pie Chart will appear here once memories are added.</span>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Bar Chart -->
            <div class="chart-card card">
                <h3>🎤 Top Artists</h3>
                <?php if (file_exists(__DIR__ . '/uploads/charts/artists_bar.png')): ?>
                    <img src="uploads/charts/artists_bar.png?t=<?php echo time(); ?>" alt="Top Artists Bar Chart" class="chart-image-placeholder">
                <?php else: ?>
                    <div style="height: 300px; display: flex; align-items: center; justify-content: center; color: var(--text-muted); flex-direction: column; gap: 1rem;">
                        <span style="font-size: 2.5rem;">📊</span>
                        <span>Bar Chart will appear here once memories are added.</span>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Line Chart -->
            <div class="chart-card card" style="grid-column: 1 / -1;">
                <h3>📈 Timeline Growth Frequency</h3>
                <?php if (file_exists(__DIR__ . '/uploads/charts/monthly_memories.png')): ?>
                    <img src="uploads/charts/monthly_memories.png?t=<?php echo time(); ?>" alt="Monthly Memories Line Chart" class="chart-image-placeholder" style="max-height: 400px; width: 80%;">
                <?php else: ?>
                    <div style="height: 250px; display: flex; align-items: center; justify-content: center; color: var(--text-muted); flex-direction: column; gap: 1rem;">
                        <span style="font-size: 2.5rem;">📊</span>
                        <span>Activity chart will appear here once memories are added.</span>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <footer>
        <p>&copy; <?php echo date('Y'); ?> Music Memory Vault. WAMP Mini Project.</p>
    </footer>
</body>
</html>
