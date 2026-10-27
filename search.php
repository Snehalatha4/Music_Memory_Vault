<?php
require_once __DIR__ . '/includes/connect.php';
require_once __DIR__ . '/includes/session.php';

check_login();

$logged_user = get_logged_in_user();
$uid = $logged_user['id'];

$search_query = trim($_GET['q'] ?? '');
$emotion_filter = trim($_GET['emotion'] ?? '');
$genre_filter = trim($_GET['genre'] ?? '');
$year_filter = trim($_GET['year'] ?? '');

$results = [];

try {
    // Dynamically compile search query
    $sql = "
        SELECT m.*, s.title as song_title, s.release_year, a.artist_name, al.album_name, g.genre_name
        FROM memories m
        JOIN songs s ON m.song_id = s.song_id
        JOIN artists a ON s.artist_id = a.artist_id
        LEFT JOIN albums al ON s.album_id = al.album_id
        LEFT JOIN genres g ON s.genre_id = g.genre_id
        WHERE m.user_id = ?
    ";
    
    $params = [$uid];
    
    if ($search_query !== '') {
        $sql .= " AND (
            s.title LIKE ? OR 
            a.artist_name LIKE ? OR 
            al.album_name LIKE ? OR 
            m.memory_title LIKE ? OR 
            m.story LIKE ? OR 
            m.location LIKE ?
        )";
        $wildcard = "%$search_query%";
        array_push($params, $wildcard, $wildcard, $wildcard, $wildcard, $wildcard, $wildcard);
    }
    
    if ($emotion_filter !== '') {
        $sql .= " AND m.emotion = ?";
        $params[] = $emotion_filter;
    }
    
    if ($genre_filter !== '') {
        $sql .= " AND g.genre_name LIKE ?";
        $params[] = "%$genre_filter%";
    }
    
    if ($year_filter !== '') {
        $sql .= " AND (s.release_year = ? OR YEAR(m.memory_date) = ?)";
        array_push($params, intval($year_filter), intval($year_filter));
    }
    
    $sql .= " ORDER BY m.memory_date DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $results = $stmt->fetchAll();
    
    // Fetch unique genres and years for search dropdown filters
    $genres_stmt = $pdo->prepare("
        SELECT DISTINCT g.genre_name 
        FROM memories m 
        JOIN songs s ON m.song_id = s.song_id 
        JOIN genres g ON s.genre_id = g.genre_id 
        WHERE m.user_id = ?
    ");
    $genres_stmt->execute([$uid]);
    $available_genres = $genres_stmt->fetchAll();
    
    $years_stmt = $pdo->prepare("
        SELECT DISTINCT IFNULL(s.release_year, YEAR(m.memory_date)) as year 
        FROM memories m 
        JOIN songs s ON m.song_id = s.song_id 
        WHERE m.user_id = ? 
        ORDER BY year DESC
    ");
    $years_stmt->execute([$uid]);
    $available_years = $years_stmt->fetchAll();
    
} catch (PDOException $e) {
    die("Database search error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Vault - Music Memory Vault</title>
    <link rel="stylesheet" href="css/style.css">
    <script src="js/script.js" defer></script>
</head>
<body>
    <div class="glow-orb orb-1"></div>
    <div class="glow-orb orb-3"></div>

    <?php include __DIR__ . '/includes/navbar.php'; ?>

    <main class="container">
        <div class="dashboard-header">
            <div class="dashboard-title">
                <h2>Advanced Database Search</h2>
                <p>Query your vault using targeted server-side criteria</p>
            </div>
            <a href="memories.php" class="btn btn-secondary">Interactive Catalog</a>
        </div>
        
        <!-- Search Filters form -->
        <div class="search-panel">
            <form action="search.php" method="GET">
                <div class="search-grid" style="grid-template-columns: 2fr 1fr 1fr 1fr 0.5fr; gap: 0.8rem; align-items: end;">
                    <div class="form-group" style="margin-bottom: 0;">
                        <label for="q">Keywords</label>
                        <input type="text" name="q" id="q" class="form-control" placeholder="Song, Artist, Story, Place..." value="<?php echo htmlspecialchars($search_query); ?>">
                    </div>
                    
                    <div class="form-group" style="margin-bottom: 0;">
                        <label for="emotion">Emotion</label>
                        <select name="emotion" id="emotion" class="form-control">
                            <option value="">Any Emotion</option>
                            <?php 
                            $emotions = ["😊 Happy", "❤️ Loved", "🥺 Nostalgic", "😭 Emotional", "😢 Heartbroken", "😌 Peaceful", "💪 Motivated", "🥳 Celebration", "😎 Confident", "🌧 Lonely", "🤩 Excited", "😴 Relaxed", "✨ Inspired"];
                            foreach ($emotions as $emo) {
                                $selected = ($emotion_filter === $emo) ? 'selected' : '';
                                echo "<option value=\"$emo\" $selected>$emo</option>";
                            }
                            ?>
                        </select>
                    </div>
                    
                    <div class="form-group" style="margin-bottom: 0;">
                        <label for="genre">Genre</label>
                        <select name="genre" id="genre" class="form-control">
                            <option value="">Any Genre</option>
                            <?php foreach ($available_genres as $g): ?>
                                <option value="<?php echo htmlspecialchars($g['genre_name']); ?>" <?php echo $genre_filter === $g['genre_name'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($g['genre_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group" style="margin-bottom: 0;">
                        <label for="year">Year</label>
                        <select name="year" id="year" class="form-control">
                            <option value="">Any Year</option>
                            <?php foreach ($available_years as $y): ?>
                                <?php if ($y['year']): ?>
                                    <option value="<?php echo intval($y['year']); ?>" <?php echo $year_filter == $y['year'] ? 'selected' : ''; ?>>
                                        <?php echo intval($y['year']); ?>
                                    </option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn btn-primary" style="padding: 0.8rem; width: 100%;">🔍</button>
                </div>
            </form>
        </div>
        
        <!-- Search Results Grid -->
        <h3 style="margin-bottom: 1.5rem; font-weight: 700;">
            Search Results (<?php echo count($results); ?>)
        </h3>
        
        <div class="memories-grid">
            <?php if (empty($results)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">🔍</div>
                    <h3>No results found</h3>
                    <p style="color: var(--text-muted); margin-bottom: 1.5rem;">We couldn't find any memories matching your exact filters. Try broadening your keywords!</p>
                    <a href="search.php" class="btn btn-secondary">Clear Filters</a>
                </div>
            <?php else: ?>
                <?php foreach ($results as $memory): ?>
                    <div class="card memory-card" onclick="viewMemoryDetails(<?php echo $memory['memory_id']; ?>)" style="cursor: pointer;">
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
