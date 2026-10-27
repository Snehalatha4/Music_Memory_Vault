<?php
require_once __DIR__ . '/includes/connect.php';
require_once __DIR__ . '/includes/session.php';

check_login();

$logged_user = get_logged_in_user();
$uid = $logged_user['id'];

// Fetch Stats
try {
    // 1. Total Memories
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM memories WHERE user_id = ?");
    $stmt->execute([$uid]);
    $total_memories = $stmt->fetch()['total'];
    
    // 2. Favorite Songs
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM memories WHERE user_id = ? AND favorite = 1");
    $stmt->execute([$uid]);
    $fav_songs = $stmt->fetch()['total'];
    
    // 3. Most Frequent Emotion
    $stmt = $pdo->prepare("SELECT emotion, COUNT(*) as count FROM memories WHERE user_id = ? GROUP BY emotion ORDER BY count DESC LIMIT 1");
    $stmt->execute([$uid]);
    $emotion_row = $stmt->fetch();
    $top_emotion = $emotion_row ? $emotion_row['emotion'] : 'None';
    
    // 4. Most Listened Artist
    $stmt = $pdo->prepare("
        SELECT a.artist_name, COUNT(*) as count 
        FROM memories m
        JOIN songs s ON m.song_id = s.song_id
        JOIN artists a ON s.artist_id = a.artist_id
        WHERE m.user_id = ?
        GROUP BY a.artist_id
        ORDER BY count DESC LIMIT 1
    ");
    $stmt->execute([$uid]);
    $artist_row = $stmt->fetch();
    $top_artist = $artist_row ? $artist_row['artist_name'] : 'None';
    
    // 5. This Month's Memories
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM memories WHERE user_id = ? AND MONTH(memory_date) = MONTH(CURRENT_DATE()) AND YEAR(memory_date) = YEAR(CURRENT_DATE())");
    $stmt->execute([$uid]);
    $month_memories = $stmt->fetch()['total'];
    
    // Fetch Recent Memories (Limit 4)
    $stmt = $pdo->prepare("
        SELECT m.*, s.title as song_title, a.artist_name 
        FROM memories m
        JOIN songs s ON m.song_id = s.song_id
        JOIN artists a ON s.artist_id = a.artist_id
        WHERE m.user_id = ?
        ORDER BY m.created_at DESC
        LIMIT 4
    ");
    $stmt->execute([$uid]);
    $recent_memories = $stmt->fetchAll();
    
} catch (PDOException $e) {
    die("Database stats fetch error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Music Memory Vault</title>
    <link rel="stylesheet" href="css/style.css">
    <script src="js/script.js" defer></script>
</head>
<body>
    <div class="glow-orb orb-1"></div>
    <div class="glow-orb orb-2"></div>
    <div class="glow-orb orb-3"></div>

    <?php include __DIR__ . '/includes/navbar.php'; ?>

    <main class="container">
        <!-- Dashboard Header -->
        <div class="dashboard-header">
            <div class="dashboard-title">
                <h2>Welcome, <?php echo htmlspecialchars($logged_user['name']); ?>! 👋</h2>
                <p>Here is a snapshot of your musical memory vault.</p>
            </div>
            <a href="addMemory.php" class="btn btn-primary">➕ Add New Memory</a>
        </div>
        
        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="card stat-card">
                <span class="stat-icon">📸</span>
                <div class="stat-value"><?php echo $total_memories; ?></div>
                <div class="stat-label">Total Memories</div>
            </div>
            <div class="card stat-card">
                <span class="stat-icon">❤️</span>
                <div class="stat-value"><?php echo $fav_songs; ?></div>
                <div class="stat-label">Favorite Songs</div>
            </div>
            <div class="card stat-card">
                <span class="stat-icon">🎭</span>
                <div class="stat-value" style="font-size: 1.3rem; margin: 0.5rem 0;"><?php echo htmlspecialchars($top_emotion); ?></div>
                <div class="stat-label">Top Emotion</div>
            </div>
            <div class="card stat-card">
                <span class="stat-icon">🎤</span>
                <div class="stat-value" style="font-size: 1.3rem; margin: 0.5rem 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"><?php echo htmlspecialchars($top_artist); ?></div>
                <div class="stat-label">Top Artist</div>
            </div>
            <div class="card stat-card">
                <span class="stat-icon">📅</span>
                <div class="stat-value"><?php echo $month_memories; ?></div>
                <div class="stat-label">This Month</div>
            </div>
        </div>
        
        <!-- Recent Memories Section -->
        <div class="section-header">
            <h3>Recent Memories</h3>
            <a href="memories.php" class="btn btn-secondary btn-sm">View All Memories</a>
        </div>
        
        <div class="memories-grid">
            <?php if (empty($recent_memories)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">🎵</div>
                    <h3>Your vault is empty!</h3>
                    <p>Start recording your thoughts, feelings, and the songs associated with your life events.</p>
                    <a href="addMemory.php" class="btn btn-primary">Add Your First Memory</a>
                </div>
            <?php else: ?>
                <?php foreach ($recent_memories as $memory): 
                    $is_locked = false;
                    $lock_timestamp = null;
                    if (!empty($memory['lock_until_date'])) {
                        $lock_timestamp = strtotime($memory['lock_until_date']);
                        if ($lock_timestamp > time()) {
                            $is_locked = true;
                        }
                    }
                ?>
                    <div class="card memory-card" 
                         <?php if ($is_locked): ?>
                         onclick="alert('This memory capsule is locked until <?php echo date('d M Y', $lock_timestamp); ?>! 🔒')"
                         style="cursor: not-allowed; position: relative;"
                         <?php else: ?>
                         onclick="viewMemoryDetails(<?php echo $memory['memory_id']; ?>)" 
                         style="cursor: pointer; position: relative;"
                         <?php endif; ?>
                         >
                         
                        <?php if ($is_locked): ?>
                            <div class="locked-overlay">
                                <span class="badge-icon">🔒</span>
                                <div class="badge-title" style="color: #ff8c00; font-size: 0.95rem;">Locked Time Capsule</div>
                                <span class="badge-desc">Sealed until <?php echo date('d M Y', $lock_timestamp); ?></span>
                            </div>
                        <?php endif; ?>
                        <div class="memory-photo-container">
                            <?php if ($memory['photo']): ?>
                                <img src="uploads/memoryPhotos/<?php echo htmlspecialchars($memory['photo']); ?>" alt="Memory Photo" class="memory-photo">
                            <?php else: ?>
                                <div style="width:100%; height:100%; background: linear-gradient(135deg, #1b1635, #322a5e); display: flex; align-items: center; justify-content: center; font-size: 3rem;">💿</div>
                            <?php endif; ?>
                            <div class="memory-emotion"><?php echo htmlspecialchars($memory['emotion']); ?></div>
                            <div class="memory-favorite-btn <?php echo $memory['favorite'] ? 'active' : ''; ?>" data-id="<?php echo $memory['memory_id']; ?>">
                                <?php echo $memory['favorite'] ? '❤️' : '🤍'; ?>
                            </div>
                        </div>
                        
                        <div class="memory-details">
                            <div class="memory-date">📅 <?php echo date('d M Y', strtotime($memory['memory_date'])); ?></div>
                            <h4 class="memory-title"><?php echo htmlspecialchars($memory['memory_title']); ?></h4>
                            
                            <div class="song-info-badge">
                                <span class="song-icon">🎵</span>
                                <div class="song-title-artist">
                                    <?php echo htmlspecialchars($memory['song_title']); ?>
                                    <span>by <?php echo htmlspecialchars($memory['artist_name']); ?></span>
                                </div>
                            </div>
                            
                            <p class="memory-story-preview"><?php echo htmlspecialchars($memory['story']); ?></p>
                            
                            <div class="memory-meta">
                                <div class="memory-location">
                                    📍 <?php echo $memory['location'] ? htmlspecialchars($memory['location']) : 'Everywhere'; ?>
                                </div>
                                <div style="display: flex; gap: 0.2rem;">
                                    <?php echo str_repeat('⭐', $memory['rating']); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>

    <!-- Details Modal Wrapper -->
    <div id="memory-modal" class="modal" style="display: none;">
        <div class="card modal-content" style="max-height: 85vh; overflow-y: auto;">
            <span class="close-modal" id="modal-close">&times;</span>
            
            <div class="memory-detail-layout">
                <div class="detail-main">
                    <h2 id="modal-title" style="color: #ff007f; font-weight: 800; font-size: 1.8rem; margin-bottom: 0.5rem;">Memory Title</h2>
                    <img id="modal-photo" src="" alt="Memory Image" class="detail-img" style="display: none;">
                    
                    <h4 style="margin-top: 1rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem; color: #00f2fe;">The Story</h4>
                    <p id="modal-story" class="detail-story">Story text goes here...</p>
                </div>
                
                <div class="detail-sidebar">
                    <h4 style="border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem; color: #8a2be2;">Details</h4>
                    
                    <div class="info-item">
                        <span class="info-label">Song</span>
                        <span class="info-val" id="modal-song">Song Title</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Artist</span>
                        <span class="info-val" id="modal-artist">Artist</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Album</span>
                        <span class="info-val" id="modal-album">Album</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Genre</span>
                        <span class="info-val" id="modal-genre">Genre</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Date</span>
                        <span class="info-val" id="modal-date">Date</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Location</span>
                        <span class="info-val" id="modal-location">Location</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Emotion</span>
                        <span class="info-val" id="modal-emotion">Emotion</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Rating</span>
                        <span class="info-val" id="modal-rating">Rating</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">AI Sentiment</span>
                        <span id="modal-sentiment" class="sentiment-badge">Neutral</span>
                    </div>
                    
                    <!-- Spotify Embed Player Container -->
                    <div id="modal-spotify-container" style="margin-top: 1rem; display: none;">
                        <h4 style="border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem; color: #1DB954; display: flex; align-items: center; gap: 0.5rem; font-size: 0.9rem;">
                            <span style="font-size: 1.1rem;">🎧</span> Spotify Preview
                        </h4>
                        <div id="modal-spotify-player" style="margin-top: 0.5rem;"></div>
                    </div>
                    
                    <div class="memory-actions" style="margin-top: 1.5rem; display: flex; flex-direction: column; gap: 0.8rem;">
                        <a id="modal-edit-link" href="#" class="btn btn-accent" style="width: 100%;">✏️ Edit Memory</a>
                        <a id="modal-delete-link" href="#" class="btn btn-danger" style="width: 100%;" onclick="return confirm('Are you sure you want to delete this memory forever?');">🗑️ Delete Memory</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <footer>
        <p>&copy; <?php echo date('Y'); ?> Music Memory Vault. WAMP Mini Project.</p>
    </footer>
</body>
</html>
