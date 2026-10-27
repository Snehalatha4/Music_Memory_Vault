<?php
/**
 * Helper database functions to manage normalized entities.
 */

function get_or_create_artist($pdo, $artist_name) {
    $artist_name = trim($artist_name);
    if (empty($artist_name)) return null;
    
    // Check if artist exists
    $stmt = $pdo->prepare("SELECT artist_id FROM artists WHERE artist_name = ?");
    $stmt->execute([$artist_name]);
    $row = $stmt->fetch();
    
    if ($row) {
        return $row['artist_id'];
    }
    
    // Insert new artist
    $stmt = $pdo->prepare("INSERT INTO artists (artist_name) VALUES (?)");
    $stmt->execute([$artist_name]);
    return $pdo->lastInsertId();
}

function get_or_create_genre($pdo, $genre_name) {
    $genre_name = trim($genre_name);
    if (empty($genre_name)) return null;
    
    // Check if genre exists
    $stmt = $pdo->prepare("SELECT genre_id FROM genres WHERE genre_name = ?");
    $stmt->execute([$genre_name]);
    $row = $stmt->fetch();
    
    if ($row) {
        return $row['genre_id'];
    }
    
    // Insert new genre
    $stmt = $pdo->prepare("INSERT INTO genres (genre_name) VALUES (?)");
    $stmt->execute([$genre_name]);
    return $pdo->lastInsertId();
}

function get_or_create_album($pdo, $album_name, $artist_id, $release_year = null) {
    $album_name = trim($album_name);
    if (empty($album_name) || !$artist_id) return null;
    
    // Check if album exists for this artist
    $stmt = $pdo->prepare("SELECT album_id FROM albums WHERE album_name = ? AND artist_id = ?");
    $stmt->execute([$album_name, $artist_id]);
    $row = $stmt->fetch();
    
    if ($row) {
        return $row['album_id'];
    }
    
    // Insert new album
    $stmt = $pdo->prepare("INSERT INTO albums (album_name, artist_id, release_year) VALUES (?, ?, ?)");
    $stmt->execute([$album_name, $artist_id, $release_year]);
    return $pdo->lastInsertId();
}

function get_or_create_song($pdo, $title, $artist_id, $album_id = null, $genre_id = null, $duration = null, $release_year = null) {
    $title = trim($title);
    if (empty($title) || !$artist_id) return null;
    
    // Check if song exists for this artist
    $stmt = $pdo->prepare("SELECT song_id FROM songs WHERE title = ? AND artist_id = ?");
    $stmt->execute([$title, $artist_id]);
    $row = $stmt->fetch();
    
    if ($row) {
        return $row['song_id'];
    }
    
    // Insert new song
    $stmt = $pdo->prepare("INSERT INTO songs (title, artist_id, album_id, genre_id, duration, release_year) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$title, $artist_id, $album_id, $genre_id, $duration, $release_year]);
    return $pdo->lastInsertId();
}

/**
 * Executes python analytics script in the background
 */
function trigger_python_analytics($memory_id = null) {
    $pythonDir = dirname(__DIR__) . '/python/analytics.py';
    $cmd = "python " . escapeshellarg($pythonDir);
    if ($memory_id !== null) {
        $cmd .= " " . intval($memory_id);
    }
    
    // Execute command based on OS
    if (substr(php_uname(), 0, 7) == "Windows") {
        pclose(popen("start /B " . $cmd, "r"));
    } else {
        exec($cmd . " > /dev/null 2>&1 &");
    }
}
?>
