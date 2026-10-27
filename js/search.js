// js/search.js - Client-side search and filtering for Music Memory Vault

document.addEventListener("DOMContentLoaded", function() {
    const searchInput = document.getElementById("search-input");
    const emotionFilter = document.getElementById("filter-emotion");
    const sortingSelect = document.getElementById("sort-by");
    const cards = document.querySelectorAll(".memory-card");
    const emptyState = document.getElementById("search-empty-state");

    if (searchInput || emotionFilter || sortingSelect) {
        // Add event listeners
        if (searchInput) searchInput.addEventListener("input", filterMemories);
        if (emotionFilter) emotionFilter.addEventListener("change", filterMemories);
        if (sortingSelect) sortingSelect.addEventListener("change", sortMemories);
    }

    function filterMemories() {
        const query = searchInput ? searchInput.value.toLowerCase().trim() : "";
        const emotion = emotionFilter ? emotionFilter.value : "";
        let visibleCount = 0;

        cards.forEach(card => {
            const title = card.querySelector(".memory-title").textContent.toLowerCase();
            const song = card.querySelector(".song-title-artist").textContent.toLowerCase();
            const story = card.querySelector(".memory-story-preview").textContent.toLowerCase();
            const location = card.querySelector(".memory-location").textContent.toLowerCase();
            const cardEmotion = card.dataset.emotion; // Store emotion in data-emotion attribute
            
            // Matching conditions
            const matchesQuery = !query || 
                                 title.includes(query) || 
                                 song.includes(query) || 
                                 story.includes(query) || 
                                 location.includes(query);
                                 
            const matchesEmotion = !emotion || cardEmotion === emotion;

            if (matchesQuery && matchesEmotion) {
                card.style.display = "flex";
                visibleCount++;
            } else {
                card.style.display = "none";
            }
        });

        // Toggle empty state message
        if (emptyState) {
            if (visibleCount === 0) {
                emptyState.style.display = "block";
            } else {
                emptyState.style.display = "none";
            }
        }
    }

    function sortMemories() {
        const sortBy = sortingSelect.value;
        const grid = document.querySelector(".memories-grid");
        if (!grid || !cards.length) return;

        const cardsArray = Array.from(cards);

        cardsArray.sort((a, b) => {
            if (sortBy === "date-desc") {
                return new Date(b.dataset.date) - new Date(a.dataset.date);
            } else if (sortBy === "date-asc") {
                return new Date(a.dataset.date) - new Date(b.dataset.date);
            } else if (sortBy === "rating-desc") {
                return parseInt(b.dataset.rating) - parseInt(a.dataset.rating);
            } else if (sortBy === "title-asc") {
                return a.querySelector(".memory-title").textContent.localeCompare(
                    b.querySelector(".memory-title").textContent
                );
            }
            return 0;
        });

        // Re-append sorted elements
        cardsArray.forEach(card => grid.appendChild(card));
    }
});
