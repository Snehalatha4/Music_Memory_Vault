<?php
require_once __DIR__ . '/includes/session.php';
if (is_logged_in()) {
    header("Location: dashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Music Memory Vault - Every Song Tells a Story</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <!-- Background glowing orbs -->
    <div class="glow-orb orb-1"></div>
    <div class="glow-orb orb-2"></div>
    <div class="glow-orb orb-3"></div>

    <?php include __DIR__ . '/includes/navbar.php'; ?>

    <main class="container">
        <section class="hero">
            <div class="hero-content">
                <div class="hero-badge">🎵 College Mini Project - WAMP Stack</div>
                <h1>Music Memory Vault</h1>
                <p>
                    Every song tells a story. We don't just build playlists; we preserve the emotions, stories, places, and times associated with your musical journey. Revisit your memories years later and see how you have evolved.
                </p>
                <div class="hero-btns">
                    <a href="register.php" class="btn btn-primary">Start Your Vault</a>
                    <a href="login.php" class="btn btn-secondary">Login</a>
                </div>
            </div>
            
            <div class="hero-visual">
                <div class="hero-image-container">
                    <div class="floating-vinyl"></div>
                </div>
            </div>
        </section>

        <section style="margin-top: 5rem;">
            <h2 style="text-align: center; font-size: 2.2rem; margin-bottom: 3rem; background: var(--primary-gradient); -webkit-background-clip: text; -webkit-text-fill-color: transparent; font-weight: 800;">
                How It Works
            </h2>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 2rem;">
                <div class="card">
                    <div style="font-size: 2.5rem; margin-bottom: 1rem;">✍️</div>
                    <h3 style="margin-bottom: 0.8rem; font-weight: 700;">1. Write Your Story</h3>
                    <p style="color: var(--text-muted); font-size: 0.95rem;">
                        Connect any song to a specific memory. Share the stories, emotions, dates, weather, and places that make it special.
                    </p>
                </div>
                
                <div class="card">
                    <div style="font-size: 2.5rem; margin-bottom: 1rem;">🧠</div>
                    <h3 style="margin-bottom: 0.8rem; font-weight: 700;">2. Intelligent Analytics</h3>
                    <p style="color: var(--text-muted); font-size: 0.95rem;">
                        Our built-in Python engine runs sentiment analysis on your journal entries and automatically builds graphical analytics charts.
                    </p>
                </div>
                
                <div class="card">
                    <div style="font-size: 2.5rem; margin-bottom: 1rem;">📅</div>
                    <h3 style="margin-bottom: 0.8rem; font-weight: 700;">3. Walk Down Memory Lane</h3>
                    <p style="color: var(--text-muted); font-size: 0.95rem;">
                        Scroll through a beautiful, chronological timeline of your musical moments, complete with sorting and search filters.
                    </p>
                </div>
            </div>
        </section>
    </main>

    <footer>
        <p>&copy; <?php echo date('Y'); ?> Music Memory Vault. Built using WAMP Stack (HTML, CSS, PHP, Python, MySQL).</p>
    </footer>
</body>
</html>
