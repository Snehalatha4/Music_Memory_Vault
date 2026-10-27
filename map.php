<?php
require_once __DIR__ . '/includes/connect.php';
require_once __DIR__ . '/includes/session.php';

check_login();

$logged_user = get_logged_in_user();
$uid = $logged_user['id'];

// Fetch memories with valid coordinates
try {
    $stmt = $pdo->prepare("
        SELECT m.*, s.title as song_title, a.artist_name 
        FROM memories m
        JOIN songs s ON m.song_id = s.song_id
        JOIN artists a ON s.artist_id = a.artist_id
        WHERE m.user_id = ? AND m.latitude IS NOT NULL AND m.longitude IS NOT NULL
        ORDER BY m.memory_date DESC
    ");
    $stmt->execute([$uid]);
    $map_memories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database memories fetch error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Memory Map - Music Memory Vault</title>
    <link rel="stylesheet" href="css/style.css">
    <!-- Leaflet Map CSS and JS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="js/script.js" defer></script>
    <style>
        .map-wrapper {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 1rem;
            margin-top: 1.5rem;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
        }
        #memory-map-canvas {
            height: 600px;
            width: 100%;
            border-radius: 12px;
        }
        .map-popup-card {
            color: #ffffff;
            font-family: inherit;
            max-width: 220px;
        }
        .map-popup-title {
            font-weight: 700;
            font-size: 0.95rem;
            color: #ff007f;
            margin-bottom: 0.25rem;
        }
        .map-popup-song {
            font-size: 0.8rem;
            color: #00f2fe;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }
        .map-popup-thumb {
            width: 100%;
            height: 90px;
            object-fit: cover;
            border-radius: 6px;
            margin-bottom: 0.5rem;
            border: 1px solid var(--border-color);
        }
        .map-popup-btn {
            display: block;
            text-align: center;
            background: linear-gradient(135deg, #8a2be2, #ff007f);
            color: #ffffff !important;
            padding: 0.4rem;
            border-radius: 6px;
            font-size: 0.75rem;
            text-decoration: none;
            font-weight: 600;
            margin-top: 0.5rem;
            transition: opacity 0.2s;
        }
        .map-popup-btn:hover {
            opacity: 0.9;
        }
        .leaflet-popup-content-wrapper, .leaflet-popup-tip {
            background: #0d0b18 !important;
            border: 1px solid var(--border-color);
            box-shadow: 0 4px 20px rgba(0,0,0,0.5);
            color: #ffffff !important;
        }
        .leaflet-popup-close-button {
            color: #ffffff !important;
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
                <h2>Interactive Memory Map</h2>
                <p>Pinpoint the geographical locations of your musical memories around the world</p>
            </div>
            <a href="memories.php" class="btn btn-secondary">Back to List</a>
        </div>

        <div class="map-wrapper">
            <div id="memory-map-canvas"></div>
        </div>
    </main>

    <!-- Details Modal Wrapper (Same as memories.php to handle detail requests) -->
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

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // Check if we have pins
            const pins = <?php echo json_encode($map_memories); ?>;
            
            // Map defaults to India center if empty, or first pin location
            let mapCenter = [20.5937, 78.9629];
            let mapZoom = 4;
            
            if (pins.length > 0) {
                mapCenter = [parseFloat(pins[0].latitude), parseFloat(pins[0].longitude)];
                mapZoom = 6;
            }
            
            const map = L.map('memory-map-canvas').setView(mapCenter, mapZoom);
            
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap contributors'
            }).addTo(map);
            
            // Track bounds to fit map to view all pins
            const markerBounds = L.latLngBounds();
            
            pins.forEach(pin => {
                const lat = parseFloat(pin.latitude);
                const lng = parseFloat(pin.longitude);
                
                // Capsule check
                let isLocked = false;
                if (pin.lock_until_date) {
                    const lockTime = new Date(pin.lock_until_date).getTime();
                    const now = new Date().getTime();
                    if (lockTime > now) {
                        isLocked = true;
                    }
                }
                
                // Add to bounds
                markerBounds.extend([lat, lng]);
                
                // Create custom popup markup
                let thumbHtml = '';
                if (isLocked) {
                    thumbHtml = `<div style="width:100%; height:90px; background:#1a152d; display:flex; align-items:center; justify-content:center; border-radius:6px; font-size:1.5rem; margin-bottom:0.5rem; border: 1px solid var(--border-color);">🔒</div>`;
                } else if (pin.photo) {
                    thumbHtml = `<img class="map-popup-thumb" src="uploads/memoryPhotos/${pin.photo}" alt="${pin.memory_title}">`;
                } else {
                    thumbHtml = `<div style="width:100%; height:90px; background:#2c2250; display:flex; align-items:center; justify-content:center; border-radius:6px; font-size:1.8rem; margin-bottom:0.5rem; border: 1px solid var(--border-color);">💿</div>`;
                }
                
                const btnHtml = isLocked 
                    ? `<span class="map-popup-btn" style="background:#ff8c00; cursor:not-allowed;">🔒 Sealed Capsule</span>`
                    : `<a href="#" class="map-popup-btn" onclick="event.preventDefault(); viewMemoryDetails(${pin.memory_id});">View Story Details</a>`;
                
                const titleText = isLocked ? `🔒 Locked Memory` : pin.memory_title;
                const dateText = new Date(pin.memory_date).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
                
                const popupContent = `
                    <div class="map-popup-card">
                        <div class="map-popup-title">${titleText}</div>
                        <div class="map-popup-song">🎵 ${pin.song_title} <span style="font-size:0.75rem; color:#888;">by ${pin.artist_name}</span></div>
                        ${thumbHtml}
                        <div style="font-size:0.75rem; color:#aaa; margin-top:0.25rem;">📅 ${dateText}</div>
                        <div style="font-size:0.75rem; color:#aaa;">📍 ${pin.location || 'Coordinates'}</div>
                        ${btnHtml}
                    </div>
                `;
                
                // Create marker and bind popup
                // Choose marker color or icon based on locked state
                const markerOptions = {};
                if (isLocked) {
                    // We can use a custom emoji marker or default
                }
                
                const marker = L.marker([lat, lng], markerOptions).addTo(map);
                marker.bindPopup(popupContent);
            });
            
            // Adjust map view if we have multiple pins
            if (pins.length > 1) {
                map.fitBounds(markerBounds, { padding: [50, 50] });
            }
        });
    </script>
</body>
</html>
