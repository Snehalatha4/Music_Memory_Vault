// js/script.js - Main UI interactions for Music Memory Vault

document.addEventListener("DOMContentLoaded", function() {
    
    // Modal implementation for Memory Details
    const modal = document.getElementById("memory-modal");
    const modalClose = document.getElementById("modal-close");
    
    if (modal && modalClose) {
        modalClose.addEventListener("click", function() {
            modal.style.display = "none";
        });
        
        window.addEventListener("click", function(e) {
            if (e.target === modal) {
                modal.style.display = "none";
            }
        });
    }

    // Interactive Favorite Toggle (AJAX)
    const favButtons = document.querySelectorAll(".memory-favorite-btn");
    favButtons.forEach(btn => {
        btn.addEventListener("click", function(e) {
            e.stopPropagation(); // Prevent opening modal
            e.preventDefault();
            
            const memoryId = this.dataset.id;
            const isFav = this.classList.contains("active") ? 0 : 1;
            const icon = this;
            
            // Call PHP script to update favorite status
            fetch(`editMemory.php?action=toggle_favorite&id=${memoryId}&fav=${isFav}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        if (isFav) {
                            icon.classList.add("active");
                            icon.innerHTML = "❤️";
                        } else {
                            icon.classList.remove("active");
                            icon.innerHTML = "🤍";
                        }
                    } else {
                        console.error("Failed to toggle favorite:", data.message);
                    }
                })
                .catch(err => {
                    console.error("Error toggling favorite:", err);
                });
        });
    });
});

/**
 * Open memory details in modal dynamically
 * @param {number} memoryId 
 */
function viewMemoryDetails(memoryId) {
    const modal = document.getElementById("memory-modal");
    if (!modal) return;
    
    // Fetch details
    fetch(`memories.php?action=get_details&id=${memoryId}`)
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                alert(data.error);
                return;
            }
            
            // Populate modal
            document.getElementById("modal-title").textContent = data.memory_title;
            document.getElementById("modal-song").textContent = data.title;
            document.getElementById("modal-artist").textContent = data.artist_name;
            document.getElementById("modal-album").textContent = data.album_name || 'N/A';
            document.getElementById("modal-genre").textContent = data.genre_name || 'N/A';
            document.getElementById("modal-date").textContent = data.memory_date;
            document.getElementById("modal-location").textContent = data.location || 'Not Specified';
            document.getElementById("modal-emotion").textContent = data.emotion;
            document.getElementById("modal-rating").innerHTML = "⭐".repeat(data.rating);
            document.getElementById("modal-story").textContent = data.story;
            
            // Sentiment
            const sentimentBox = document.getElementById("modal-sentiment");
            if (sentimentBox) {
                sentimentBox.className = `sentiment-badge sentiment-${data.sentiment_label.toLowerCase()}`;
                sentimentBox.textContent = `${data.sentiment_label} (${Math.round(data.sentiment_score * 100)}%)`;
            }
            
            const photoEl = document.getElementById("modal-photo");
            if (data.photo) {
                photoEl.src = `uploads/memoryPhotos/${data.photo}`;
                photoEl.style.display = "block";
            } else {
                photoEl.style.display = "none";
            }
            
            // Spotify Preview Player
            const spotifyContainer = document.getElementById('modal-spotify-container');
            const spotifyPlayer = document.getElementById('modal-spotify-player');
            if (spotifyContainer && spotifyPlayer) {
                if (data.spotify_track_id) {
                    spotifyContainer.style.display = 'block';
                    spotifyPlayer.innerHTML = `<iframe src="https://open.spotify.com/embed/track/${data.spotify_track_id}?utm_source=generator&theme=0" width="100%" height="80" frameBorder="0" allowfullscreen="" allow="autoplay; clipboard-write; encrypted-media; fullscreen; picture-in-picture" loading="lazy" style="border-radius: 8px; border: none; margin-top: 0.5rem;"></iframe>`;
                } else {
                    spotifyContainer.style.display = 'none';
                    spotifyPlayer.innerHTML = '';
                }
            }
            
            // Set edit/delete links
            const editBtn = document.getElementById("modal-edit-link");
            const deleteBtn = document.getElementById("modal-delete-link");
            if (editBtn) editBtn.href = `editMemory.php?id=${data.memory_id}`;
            if (deleteBtn) deleteBtn.href = `deleteMemory.php?id=${data.memory_id}`;
            
            // Open modal
            modal.style.display = "flex";
        })
        .catch(err => {
            console.error("Error fetching memory details:", err);
        });
}
