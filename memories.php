<?php
require_once __DIR__ . '/includes/connect.php';
require_once __DIR__ . '/includes/session.php';

check_login();

$logged_user = get_logged_in_user();
$uid = $logged_user['id'];

// AJAX Detail Endpoint
if (isset($_GET['action']) && $_GET['action'] === 'get_details') {
    header('Content-Type: application/json');
    $memory_id = intval($_GET['id'] ?? 0);
    
    try {
        $stmt = $pdo->prepare("
            SELECT m.*, s.title, s.duration, s.release_year, a.artist_name, al.album_name, g.genre_name
            FROM memories m
            JOIN songs s ON m.song_id = s.song_id
            JOIN artists a ON s.artist_id = a.artist_id
            LEFT JOIN albums al ON s.album_id = al.album_id
            LEFT JOIN genres g ON s.genre_id = g.genre_id
            WHERE m.memory_id = ? AND m.user_id = ?
        ");
        $stmt->execute([$memory_id, $uid]);
        $details = $stmt->fetch();
        
        if ($details) {
            // Guarantee labels exist
            if (empty($details['sentiment_label'])) $details['sentiment_label'] = 'Neutral';
            echo json_encode($details);
        } else {
            echo json_encode(['error' => 'Memory not found or unauthorized.']);
        }
    } catch (PDOException $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit();
}

// Fetch all memories for the user
try {
    $stmt = $pdo->prepare("
        SELECT m.*, s.title as song_title, a.artist_name 
        FROM memories m
        JOIN songs s ON m.song_id = s.song_id
        JOIN artists a ON s.artist_id = a.artist_id
        WHERE m.user_id = ?
        ORDER BY m.memory_date DESC
    ");
    $stmt->execute([$uid]);
    $memories = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Database memories fetch error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Memories - Music Memory Vault</title>
    <link rel="stylesheet" href="css/style.css">
    <script src="js/script.js" defer></script>
    <script src="js/search.js" defer></script>
</head>
<body>
    <div class="glow-orb orb-1"></div>
    <div class="glow-orb orb-2"></div>
    <div class="glow-orb orb-3"></div>

    <?php include __DIR__ . '/includes/navbar.php'; ?>

    <main class="container">
        <div class="dashboard-header">
            <div class="dashboard-title">
                <h2>My Memories Vault</h2>
                <p>Browse, filter, and search your collection of musical moments</p>
            </div>
            <a href="addMemory.php" class="btn btn-primary">➕ Add Memory</a>
        </div>
        
        <!-- Search and Filter Panel -->
        <div class="search-panel">
            <div class="search-grid">
                <input type="text" id="search-input" class="form-control" placeholder="Search by song, artist, story, location...">
                
                <select id="filter-emotion" class="form-control">
                    <option value="">Filter by Emotion...</option>
                    <option value="😊 Happy">😊 Happy</option>
                    <option value="❤️ Loved">❤️ Loved</option>
                    <option value="🥺 Nostalgic">🥺 Nostalgic</option>
                    <option value="😭 Emotional">😭 Emotional</option>
                    <option value="😢 Heartbroken">😢 Heartbroken</option>
                    <option value="😌 Peaceful">😌 Peaceful</option>
                    <option value="💪 Motivated">💪 Motivated</option>
                    <option value="🥳 Celebration">🥳 Celebration</option>
                    <option value="😎 Confident">😎 Confident</option>
                    <option value="🌧 Lonely">🌧 Lonely</option>
                    <option value="🤩 Excited">🤩 Excited</option>
                    <option value="😴 Relaxed">😴 Relaxed</option>
                    <option value="✨ Inspired">✨ Inspired</option>
                </select>
                
                <select id="sort-by" class="form-control">
                    <option value="date-desc">Newest First</option>
                    <option value="date-asc">Oldest First</option>
                    <option value="rating-desc">Highest Rated</option>
                    <option value="title-asc">Title A-Z</option>
                </select>
                
                <a href="timeline.php" class="btn btn-secondary" style="height: 100%; display: flex; align-items: center; justify-content: center; gap: 0.5rem;">
                    📅 Timeline View
                </a>
            </div>
        </div>
        
        <!-- Memories Grid -->
        <div class="memories-grid">
            <?php if (empty($memories)): ?>
                <div class="empty-state" id="search-empty-state">
                    <div class="empty-state-icon">💿</div>
                    <h3>No memories found</h3>
                    <p>Create a new memory card to store your songs and stories.</p>
                    <a href="addMemory.php" class="btn btn-primary">Add Memory</a>
                </div>
            <?php else: ?>
                <!-- Javascript dynamic empty state placeholder -->
                <div class="empty-state" id="search-empty-state" style="display: none;">
                    <div class="empty-state-icon">🔍</div>
                    <h3>No matches found</h3>
                    <p>Try adjusting your search keywords or filters.</p>
                </div>
                
                <?php foreach ($memories as $memory): 
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
                         data-emotion="<?php echo htmlspecialchars($memory['emotion']); ?>"
                         data-date="<?php echo $memory['memory_date']; ?>"
                         data-rating="<?php echo $memory['rating']; ?>">
                         
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
                                <div style="width:100%; height:100%; background: linear-gradient(135deg, #1b1635, #322a5e); display: flex; align-items: center; justify-content: center; font-size: 3.5rem;">💿</div>
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
