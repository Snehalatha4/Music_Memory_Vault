<?php
require_once __DIR__ . '/includes/connect.php';
require_once __DIR__ . '/includes/session.php';

check_login();

$logged_user = get_logged_in_user();
$uid = $logged_user['id'];

// Fetch memories sorted chronologically (oldest first for timeline flow)
try {
    $stmt = $pdo->prepare("
        SELECT m.*, s.title as song_title, a.artist_name 
        FROM memories m
        JOIN songs s ON m.song_id = s.song_id
        JOIN artists a ON s.artist_id = a.artist_id
        WHERE m.user_id = ?
        ORDER BY m.memory_date ASC
    ");
    $stmt->execute([$uid]);
    $timeline_memories = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Database timeline fetch error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Memory Timeline - Music Memory Vault</title>
    <link rel="stylesheet" href="css/style.css">
    <script src="js/script.js" defer></script>
</head>
<body>
    <div class="glow-orb orb-1"></div>
    <div class="glow-orb orb-2"></div>
    <div class="glow-orb orb-3"></div>

    <?php include __DIR__ . '/includes/navbar.php'; ?>

    <main class="container">
        <div class="dashboard-header">
            <div class="dashboard-title">
                <h2>Musical Journey Timeline</h2>
                <p>Scroll through your musical moments, ordered chronologically</p>
            </div>
            <a href="memories.php" class="btn btn-secondary">Grid View</a>
        </div>
        
        <?php if (empty($timeline_memories)): ?>
            <div class="card" style="text-align: center; padding: 4rem 2rem;">
                <div style="font-size: 3rem; margin-bottom: 1rem;">📅</div>
                <h3>Your timeline is empty</h3>
                <p style="color: var(--text-muted); margin-bottom: 1.5rem;">Add some memories with dates to populate this timeline!</p>
                <a href="addMemory.php" class="btn btn-primary">Add Memory</a>
            </div>
        <?php else: ?>
            <div class="timeline-container">
                <?php 
                $count = 0;
                foreach ($timeline_memories as $memory): 
                    $sideClass = ($count % 2 === 0) ? 'left-item' : 'right-item';
                    $year = date('Y', strtotime($memory['memory_date']));
                    $formattedDate = date('d M Y', strtotime($memory['memory_date']));
                    $count++;
                ?>
                    <div class="timeline-item <?php echo $sideClass; ?>">
                        <div class="timeline-card card" onclick="viewMemoryDetails(<?php echo $memory['memory_id']; ?>)" style="cursor: pointer;">
                            <span class="timeline-year"><?php echo htmlspecialchars($year); ?></span>
                            <div style="font-size: 0.8rem; color: var(--text-dim); margin-bottom: 0.5rem;">📅 <?php echo $formattedDate; ?></div>
                            
                            <h4 style="margin-bottom: 0.4rem; font-weight: 700;"><?php echo htmlspecialchars($memory['memory_title']); ?></h4>
                            
                            <div style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 0.6rem; display: flex; align-items: center; gap: 0.3rem;">
                                <span>🎵</span> <strong><?php echo htmlspecialchars($memory['song_title']); ?></strong> - <?php echo htmlspecialchars($memory['artist_name']); ?>
                            </div>
                            
                            <p style="font-size: 0.85rem; color: var(--text-muted); display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                                <?php echo htmlspecialchars($memory['story']); ?>
                            </p>
                            
                            <div style="margin-top: 0.8rem; display: flex; justify-content: space-between; align-items: center; font-size: 0.8rem;">
                                <span style="background: rgba(255,255,255,0.05); padding: 0.2rem 0.5rem; border-radius: 10px;">
                                    <?php echo htmlspecialchars($memory['emotion']); ?>
                                </span>
                                <span>📍 <?php echo $memory['location'] ? htmlspecialchars($memory['location']) : 'Everywhere'; ?></span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
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
