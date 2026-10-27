<?php
require_once __DIR__ . '/includes/connect.php';
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/db_helpers.php';

check_login();

$error = '';
$success = '';
$logged_user = get_logged_in_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $song_title = trim($_POST['song_title'] ?? '');
    $artist_name = trim($_POST['artist'] ?? '');
    $album_name = trim($_POST['album'] ?? '');
    $genre_name = trim($_POST['genre'] ?? '');
    $release_year = !empty($_POST['release_year']) ? intval($_POST['release_year']) : null;
    $duration = trim($_POST['duration'] ?? '');
    
    $memory_title = trim($_POST['memory_title'] ?? '');
    $story = trim($_POST['story'] ?? '');
    $emotion = trim($_POST['emotion'] ?? '');
    $memory_date = trim($_POST['memory_date'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $rating = intval($_POST['rating'] ?? 5);
    $favorite = isset($_POST['favorite']) ? 1 : 0;
    
    // Parse Spotify Link / Track ID
    $spotify_track_id = null;
    $spotify_input = trim($_POST['spotify_link'] ?? '');
    if (!empty($spotify_input)) {
        if (preg_match('/(?:spotify\.com(?:\/[a-z\-]+)?\/track\/|spotify:track:)([a-zA-Z0-9]{22})/', $spotify_input, $matches)) {
            $spotify_track_id = $matches[1];
        } else if (preg_match('/^[a-zA-Z0-9]{22}$/', $spotify_input)) {
            $spotify_track_id = $spotify_input;
        }
    }
    
    $latitude = !empty($_POST['latitude']) ? floatval($_POST['latitude']) : null;
    $longitude = !empty($_POST['longitude']) ? floatval($_POST['longitude']) : null;
    $lock_until_date = !empty($_POST['lock_until_date']) ? $_POST['lock_until_date'] : null;
    
    if (empty($song_title) || empty($artist_name) || empty($memory_title) || empty($story) || empty($memory_date) || empty($emotion)) {
        $error = 'Please fill in all required fields (*)';
    } else {
        try {
            // Start transaction
            $pdo->beginTransaction();
            
            // Get or create entities
            $artist_id = get_or_create_artist($pdo, $artist_name);
            $genre_id = get_or_create_genre($pdo, $genre_name);
            $album_id = get_or_create_album($pdo, $album_name, $artist_id, $release_year);
            $song_id = get_or_create_song($pdo, $song_title, $artist_id, $album_id, $genre_id, $duration, $release_year);
            
            // Upload photo
            $photo_name = null;
            if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                $file_tmp = $_FILES['photo']['tmp_name'];
                $file_name = $_FILES['photo']['name'];
                $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                $allowed_exts = ['jpg', 'jpeg', 'png', 'gif'];
                
                if (in_array($file_ext, $allowed_exts)) {
                    $photo_name = 'memory_' . uniqid() . '.' . $file_ext;
                    $upload_dir = __DIR__ . '/uploads/memoryPhotos/';
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }
                    move_uploaded_file($file_tmp, $upload_dir . $photo_name);
                } else {
                    $error = 'Invalid image format. Allowed: JPG, PNG, GIF.';
                }
            }
            
            if (empty($error)) {
                // Insert memory
                $stmt = $pdo->prepare("
                    INSERT INTO memories (user_id, song_id, memory_title, story, emotion, memory_date, location, photo, rating, favorite, lock_until_date, latitude, longitude, spotify_track_id)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $logged_user['id'],
                    $song_id,
                    $memory_title,
                    $story,
                    $emotion,
                    $memory_date,
                    $location,
                    $photo_name,
                    $rating,
                    $favorite,
                    $lock_until_date,
                    $latitude,
                    $longitude,
                    $spotify_track_id
                ]);
                
                $memory_id = $pdo->lastInsertId();
                $pdo->commit();
                
                // Trigger python analytics/sentiment analysis in the background
                trigger_python_analytics($memory_id);
                
                $success = 'Memory added successfully! Check it out in your Memories page.';
                
                // Reset form values
                $_POST = [];
            } else {
                $pdo->rollBack();
            }
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error = 'Error saving memory: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Memory - Music Memory Vault</title>
    <link rel="stylesheet" href="css/style.css">
    <!-- Leaflet Map CSS and JS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="js/validation.js" defer></script>
</head>
<body>
    <div class="glow-orb orb-1"></div>
    <div class="glow-orb orb-2"></div>

    <?php include __DIR__ . '/includes/navbar.php'; ?>

    <main class="container">
        <div class="dashboard-header">
            <div class="dashboard-title">
                <h2>Capture a Memory</h2>
                <p>Attach a song to a moment and lock it in time</p>
            </div>
            <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
        </div>
        
        <div class="card" style="max-width: 800px; margin: 0 auto;">
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <form action="addMemory.php" method="POST" enctype="multipart/form-data" id="memory-form">
                <h3 style="margin-bottom: 1.5rem; color: #ff007f; font-weight: 700; border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem;">
                    🎵 Song Details
                </h3>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="song_title">Song Title *</label>
                        <input type="text" name="song_title" id="song_title" class="form-control" placeholder="e.g. Perfect" required value="<?php echo htmlspecialchars($_POST['song_title'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="artist">Artist *</label>
                        <input type="text" name="artist" id="artist" class="form-control" placeholder="e.g. Ed Sheeran" required value="<?php echo htmlspecialchars($_POST['artist'] ?? ''); ?>">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="album">Album</label>
                        <input type="text" name="album" id="album" class="form-control" placeholder="e.g. ÷ (Divide)" value="<?php echo htmlspecialchars($_POST['album'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="genre">Genre</label>
                        <input type="text" name="genre" id="genre" class="form-control" placeholder="e.g. Pop" value="<?php echo htmlspecialchars($_POST['genre'] ?? ''); ?>">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="release_year">Release Year</label>
                        <input type="number" name="release_year" id="release_year" class="form-control" placeholder="e.g. 2017" value="<?php echo htmlspecialchars($_POST['release_year'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="duration">Duration</label>
                        <input type="text" name="duration" id="duration" class="form-control" placeholder="e.g. 4:23" value="<?php echo htmlspecialchars($_POST['duration'] ?? ''); ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="spotify_link">🎧 Spotify Song Link or Track ID (Optional)</label>
                    <input type="text" name="spotify_link" id="spotify_link" class="form-control" placeholder="e.g. https://open.spotify.com/track/4PTG3Z6ehGkBF3zIqYQGS3" value="<?php echo htmlspecialchars($_POST['spotify_link'] ?? ''); ?>">
                    <span style="font-size: 0.8rem; color: var(--text-muted); display: block; margin-top: 0.25rem;">Paste a Spotify song link to embed a 30-second audio player directly into your memory details!</span>
                </div>
                
                <h3 style="margin-top: 2rem; margin-bottom: 1.5rem; color: #00f2fe; font-weight: 700; border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem;">
                    📝 The Memory
                </h3>
                
                <div class="form-group">
                    <label for="memory_title">Memory Title / Headline *</label>
                    <input type="text" name="memory_title" id="memory_title" class="form-control" placeholder="e.g. First Dance at Graduation / Road Trip to Ooty" required value="<?php echo htmlspecialchars($_POST['memory_title'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="story">Your Story (What happened during this song?) *</label>
                    <textarea name="story" id="story" class="form-control" placeholder="Write down your details, feelings, and what you were doing. The Python backend will analyze your sentiment." required><?php echo htmlspecialchars($_POST['story'] ?? ''); ?></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="emotion">Emotion *</label>
                        <select name="emotion" id="emotion" class="form-control" required>
                            <option value="">Select Emotion...</option>
                            <option value="😊 Happy" <?php echo ($_POST['emotion'] ?? '') === '😊 Happy' ? 'selected' : ''; ?>>😊 Happy</option>
                            <option value="❤️ Loved" <?php echo ($_POST['emotion'] ?? '') === '❤️ Loved' ? 'selected' : ''; ?>>❤️ Loved</option>
                            <option value="🥺 Nostalgic" <?php echo ($_POST['emotion'] ?? '') === '🥺 Nostalgic' ? 'selected' : ''; ?>>🥺 Nostalgic</option>
                            <option value="😭 Emotional" <?php echo ($_POST['emotion'] ?? '') === '😭 Emotional' ? 'selected' : ''; ?>>😭 Emotional</option>
                            <option value="😢 Heartbroken" <?php echo ($_POST['emotion'] ?? '') === '😢 Heartbroken' ? 'selected' : ''; ?>>😢 Heartbroken</option>
                            <option value="😌 Peaceful" <?php echo ($_POST['emotion'] ?? '') === '😌 Peaceful' ? 'selected' : ''; ?>>😌 Peaceful</option>
                            <option value="💪 Motivated" <?php echo ($_POST['emotion'] ?? '') === '💪 Motivated' ? 'selected' : ''; ?>>💪 Motivated</option>
                            <option value="🥳 Celebration" <?php echo ($_POST['emotion'] ?? '') === '🥳 Celebration' ? 'selected' : ''; ?>>🥳 Celebration</option>
                            <option value="😎 Confident" <?php echo ($_POST['emotion'] ?? '') === '😎 Confident' ? 'selected' : ''; ?>>😎 Confident</option>
                            <option value="🌧 Lonely" <?php echo ($_POST['emotion'] ?? '') === '🌧 Lonely' ? 'selected' : ''; ?>>🌧 Lonely</option>
                            <option value="🤩 Excited" <?php echo ($_POST['emotion'] ?? '') === '🤩 Excited' ? 'selected' : ''; ?>>🤩 Excited</option>
                            <option value="😴 Relaxed" <?php echo ($_POST['emotion'] ?? '') === '😴 Relaxed' ? 'selected' : ''; ?>>😴 Relaxed</option>
                            <option value="✨ Inspired" <?php echo ($_POST['emotion'] ?? '') === '✨ Inspired' ? 'selected' : ''; ?>>✨ Inspired</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="memory_date">Memory Date *</label>
                        <input type="date" name="memory_date" id="memory_date" class="form-control" required value="<?php echo htmlspecialchars($_POST['memory_date'] ?? date('Y-m-d')); ?>">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="location">Location / Place</label>
                        <div style="display: flex; gap: 0.5rem;">
                            <input type="text" name="location" id="location" class="form-control" placeholder="e.g. Ooty, Tamil Nadu" value="<?php echo htmlspecialchars($_POST['location'] ?? ''); ?>">
                            <button type="button" id="btn-locate" class="btn btn-accent" style="padding: 0 1rem; font-size: 0.85rem; white-space: nowrap;">🗺️ Pin on Map</button>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="lock_until_date">🎁 Memory Capsule (Lock Until Date - Optional)</label>
                        <input type="date" name="lock_until_date" id="lock_until_date" class="form-control" value="<?php echo htmlspecialchars($_POST['lock_until_date'] ?? ''); ?>">
                    </div>
                </div>
                
                <div id="map-container" style="margin-bottom: 1.5rem; display: none;">
                    <div id="map" style="height: 250px; width: 100%; border: 1px solid var(--border-color); border-radius: 8px;"></div>
                    <div class="form-row" style="margin-top: 0.8rem;">
                        <div class="form-group">
                            <label for="latitude">Latitude</label>
                            <input type="number" step="any" name="latitude" id="latitude" class="form-control" placeholder="e.g. 11.4102" value="<?php echo htmlspecialchars($_POST['latitude'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label for="longitude">Longitude</label>
                            <input type="number" step="any" name="longitude" id="longitude" class="form-control" placeholder="e.g. 76.6950" value="<?php echo htmlspecialchars($_POST['longitude'] ?? ''); ?>">
                        </div>
                    </div>
                    <span style="font-size: 0.8rem; color: var(--text-muted);">You can also click on the map to drag the pin or set coordinates manually.</span>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="rating">Rating (Significance score 1-5)</label>
                        <select name="rating" id="rating" class="form-control">
                            <option value="5" <?php echo ($_POST['rating'] ?? '') === '5' ? 'selected' : ''; ?>>⭐⭐⭐⭐⭐ (Life-Defining)</option>
                            <option value="4" <?php echo ($_POST['rating'] ?? '') === '4' ? 'selected' : ''; ?>>⭐⭐⭐⭐ (Significant)</option>
                            <option value="3" <?php echo ($_POST['rating'] ?? '') === '3' ? 'selected' : ''; ?>>⭐⭐⭐ (Memorable)</option>
                            <option value="2" <?php echo ($_POST['rating'] ?? '') === '2' ? 'selected' : ''; ?>>⭐⭐ (Moderate)</option>
                            <option value="1" <?php echo ($_POST['rating'] ?? '') === '1' ? 'selected' : ''; ?>>⭐ (Casual)</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="photo">Upload Photo (A picture associated with the memory)</label>
                        <input type="file" name="photo" id="photo" class="form-control" accept="image/*">
                    </div>
                </div>
                
                <div class="form-group" style="display: flex; align-items: center; gap: 0.5rem; margin-top: 1rem;">
                    <input type="checkbox" name="favorite" id="favorite" style="width: 18px; height: 18px; cursor: pointer;" <?php echo isset($_POST['favorite']) ? 'checked' : ''; ?>>
                    <label for="favorite" style="margin-bottom: 0; cursor: pointer; font-size: 0.95rem; font-weight: 600;">
                        Add this memory to Favorites ❤️
                    </label>
                </div>
                
                <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 2rem;">Save Memory to Vault</button>
            </form>
        </div>
    </main>

    <footer>
        <p>&copy; <?php echo date('Y'); ?> Music Memory Vault. WAMP Mini Project.</p>
    </footer>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            let map = null;
            let marker = null;
            
            const btnLocate = document.getElementById("btn-locate");
            const locationInput = document.getElementById("location");
            const mapContainer = document.getElementById("map-container");
            const latInput = document.getElementById("latitude");
            const lngInput = document.getElementById("longitude");
            
            function initMap(lat, lng) {
                mapContainer.style.display = "block";
                if (!map) {
                    map = L.map('map').setView([lat, lng], 13);
                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        attribution: '&copy; OpenStreetMap contributors'
                    }).addTo(map);
                    
                    // Click map to set pin
                    map.on('click', function(e) {
                        setMarker(e.latlng.lat, e.latlng.lng);
                    });
                } else {
                    map.setView([lat, lng], 13);
                }
                setMarker(lat, lng);
                setTimeout(() => {
                    map.invalidateSize();
                }, 200); // fix Leaflet rendering in hidden container
            }
            
            function setMarker(lat, lng) {
                latInput.value = lat.toFixed(6);
                lngInput.value = lng.toFixed(6);
                if (!marker) {
                    marker = L.marker([lat, lng], {draggable: true}).addTo(map);
                    marker.on('dragend', function() {
                        const pos = marker.getLatLng();
                        latInput.value = pos.lat.toFixed(6);
                        lngInput.value = pos.lng.toFixed(6);
                    });
                } else {
                    marker.setLatLng([lat, lng]);
                }
            }
            
            btnLocate.addEventListener("click", function(e) {
                e.preventDefault();
                const query = locationInput.value.trim();
                if (!query) {
                    alert("Please enter a location name first.");
                    return;
                }
                
                // Fetch coordinates from OpenStreetMap Nominatim
                fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}`)
                    .then(res => res.json())
                    .then(data => {
                        if (data && data.length > 0) {
                            const lat = parseFloat(data[0].lat);
                            const lon = parseFloat(data[0].lon);
                            initMap(lat, lon);
                        } else {
                            alert("Location not found on map. Initializing at default coordinates. You can click to set your own.");
                            initMap(20.5937, 78.9629); // Default coordinates
                        }
                    })
                    .catch(err => {
                        console.error(err);
                        initMap(20.5937, 78.9629); // fallback
                    });
            });
            
            // If coordinates already filled (e.g. form validation fail page reload), init map
            if (latInput.value && lngInput.value) {
                initMap(parseFloat(latInput.value), parseFloat(lngInput.value));
            }
        });
    </script>
</body>
</html>
