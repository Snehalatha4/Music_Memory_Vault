<?php
require_once __DIR__ . '/includes/connect.php';
require_once __DIR__ . '/includes/session.php';

check_login();

$logged_user = get_logged_in_user();
$uid = $logged_user['id'];

// Fetch stats for milestones
try {
    // 1. Total memories
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM memories WHERE user_id = ?");
    $stmt->execute([$uid]);
    $total_memories = intval($stmt->fetchColumn());

    // 2. Unique artists
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT s.artist_id) 
        FROM memories m 
        JOIN songs s ON m.song_id = s.song_id 
        WHERE m.user_id = ?
    ");
    $stmt->execute([$uid]);
    $unique_artists = intval($stmt->fetchColumn());

    // 3. Unique cities (locations)
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT LOWER(TRIM(location))) 
        FROM memories 
        WHERE user_id = ? AND location IS NOT NULL AND location != ''
    ");
    $stmt->execute([$uid]);
    $unique_cities = intval($stmt->fetchColumn());

} catch (PDOException $e) {
    die("Database stats query failed: " . $e->getMessage());
}

// Define the milestones criteria
$milestones = [
    [
        'id' => 'first_memory',
        'title' => 'First Memory',
        'desc' => 'Seal your very first musical moment in the vault.',
        'icon' => '🚀',
        'current' => $total_memories,
        'target' => 1,
        'unlocked' => ($total_memories >= 1),
        'tier' => 'Bronze'
    ],
    [
        'id' => 'memories_50',
        'title' => 'Collector of Moments',
        'desc' => 'Record 50 memories inside your personal vault.',
        'icon' => '📚',
        'current' => $total_memories,
        'target' => 50,
        'unlocked' => ($total_memories >= 50),
        'tier' => 'Gold'
    ],
    [
        'id' => 'artists_10',
        'title' => 'Music Aficionado',
        'desc' => 'Save memories for at least 10 different artists.',
        'icon' => '🎧',
        'current' => $unique_artists,
        'target' => 10,
        'unlocked' => ($unique_artists >= 10),
        'tier' => 'Silver'
    ],
    [
        'id' => 'cities_5',
        'title' => 'Globetrotter',
        'desc' => 'Pin memories in 5 or more different locations/cities.',
        'icon' => '🌍',
        'current' => $unique_cities,
        'target' => 5,
        'unlocked' => ($unique_cities >= 5),
        'tier' => 'Silver'
    ]
];

// Count unlocked badges
$unlocked_count = 0;
foreach ($milestones as $m) {
    if ($m['unlocked']) $unlocked_count++;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Memory Milestones - Music Memory Vault</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .milestones-header-card {
            background: linear-gradient(135deg, rgba(138, 43, 226, 0.15), rgba(255, 0, 127, 0.15));
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 2rem;
            margin-bottom: 2rem;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        .progress-bar-container {
            width: 100%;
            height: 10px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 5px;
            overflow: hidden;
            margin-top: 0.5rem;
            margin-bottom: 0.5rem;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .progress-bar-fill {
            height: 100%;
            background: linear-gradient(to right, #00f2fe, #ff007f);
            border-radius: 5px;
            transition: width 1s ease-in-out;
        }
        .progress-text {
            font-size: 0.8rem;
            color: var(--text-muted);
            text-align: right;
            font-weight: 600;
        }
        .stats-summary-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1.5rem;
            margin-top: 1.5rem;
        }
        .stat-box {
            background: rgba(10, 8, 20, 0.5);
            border: 1px solid var(--border-color);
            padding: 1rem;
            border-radius: 12px;
        }
        .stat-box h4 {
            font-size: 1.8rem;
            color: #00f2fe;
            margin-bottom: 0.2rem;
        }
        .stat-box p {
            font-size: 0.8rem;
            color: var(--text-muted);
            margin: 0;
        }
        @media (max-width: 600px) {
            .stats-summary-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="glow-orb orb-1"></div>
    <div class="glow-orb orb-2"></div>

    <?php include __DIR__ . '/includes/navbar.php'; ?>

    <main class="container">
        <div class="dashboard-header">
            <div class="dashboard-title">
                <h2>Memory Milestones</h2>
                <p>Track your accomplishments and unlock musical badges as your vault grows</p>
            </div>
            <a href="dashboard.php" class="btn btn-secondary">Dashboard</a>
        </div>

        <div class="milestones-header-card">
            <h3 style="color:#ffffff; font-size:1.5rem; margin-bottom: 0.5rem;">Vault Badges: <?php echo $unlocked_count; ?> / <?php echo count($milestones); ?> Unlocked</h3>
            <p style="color:var(--text-muted); margin-bottom:1.5rem;">Keep listening, traveling, and writing to claim more awards!</p>
            
            <div class="progress-bar-container" style="max-width: 600px; margin: 0 auto;">
                <?php 
                $overall_percent = count($milestones) > 0 ? ($unlocked_count / count($milestones)) * 100 : 0;
                ?>
                <div class="progress-bar-fill" style="width: <?php echo $overall_percent; ?>%;"></div>
            </div>
            <div class="progress-text" style="max-width: 600px; margin: 0.25rem auto 0 auto; text-align: center;">
                <?php echo round($overall_percent); ?>% Unlocked
            </div>

            <div class="stats-summary-grid">
                <div class="stat-box">
                    <h4><?php echo $total_memories; ?></h4>
                    <p>Total Memories</p>
                </div>
                <div class="stat-box">
                    <h4><?php echo $unique_artists; ?></h4>
                    <p>Artists Saved</p>
                </div>
                <div class="stat-box">
                    <h4><?php echo $unique_cities; ?></h4>
                    <p>Cities Visited</p>
                </div>
            </div>
        </div>

        <div class="badge-grid" style="grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 1.5rem;">
            <?php foreach ($milestones as $m): ?>
                <?php 
                $status_class = $m['unlocked'] ? 'unlocked' : 'locked';
                if ($m['unlocked'] && $m['tier'] === 'Gold') {
                    $status_class .= ' gold';
                }
                
                // Calculate percentage progress
                $progress_percent = $m['target'] > 0 ? min(($m['current'] / $m['target']) * 100, 100) : 0;
                ?>
                <div class="card badge-card <?php echo $status_class; ?>">
                    <span class="badge-icon"><?php echo $m['icon']; ?></span>
                    <div class="badge-title"><?php echo htmlspecialchars($m['title']); ?></div>
                    <div style="font-size: 0.7rem; font-weight: 700; color: <?php echo $m['unlocked'] ? '#ff007f' : '#888'; ?>; text-transform: uppercase; margin-bottom: 0.5rem;">
                        <?php echo $m['tier']; ?> Tier
                    </div>
                    <div class="badge-desc" style="margin-bottom: 1rem; min-height: 2.5rem; text-align: center;">
                        <?php echo htmlspecialchars($m['desc']); ?>
                    </div>
                    
                    <!-- Progress bar for locked badges -->
                    <div style="width: 100%; margin-top: auto;">
                        <div style="display:flex; justify-content:space-between; font-size:0.7rem; color:var(--text-muted); margin-bottom:0.2rem;">
                            <span>Progress</span>
                            <span><?php echo $m['current']; ?> / <?php echo $m['target']; ?></span>
                        </div>
                        <div class="progress-bar-container" style="height: 6px;">
                            <div class="progress-bar-fill" style="width: <?php echo $progress_percent; ?>%; background: <?php echo $m['unlocked'] ? 'linear-gradient(to right, #00ff87, #60efff)' : 'var(--text-muted)'; ?>"></div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </main>

    <footer>
        <p>&copy; <?php echo date('Y'); ?> Music Memory Vault. WAMP Mini Project.</p>
    </footer>
</body>
</html>
