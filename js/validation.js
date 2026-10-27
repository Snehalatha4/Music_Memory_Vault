// js/validation.js - Form validation for Music Memory Vault

document.addEventListener("DOMContentLoaded", function() {
    
    // Validate Register Form
    const registerForm = document.getElementById("register-form");
    if (registerForm) {
        registerForm.addEventListener("submit", function(e) {
            const name = document.getElementById("name").value.trim();
            const email = document.getElementById("email").value.trim();
            const password = document.getElementById("password").value;
            const confirmPassword = document.getElementById("confirm_password").value;
            
            if (!name || !email || !password || !confirmPassword) {
                showError("All fields are required.");
                e.preventDefault();
                return;
            }
            
            // Basic email regex
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                showError("Please enter a valid email address.");
                e.preventDefault();
                return;
            }
            
            if (password.length < 6) {
                showError("Password must be at least 6 characters long.");
                e.preventDefault();
                return;
            }
            
            if (password !== confirmPassword) {
                showError("Passwords do not match.");
                e.preventDefault();
                return;
            }
        });
    }

    // Validate Memory Form (Add/Edit)
    const memoryForm = document.getElementById("memory-form");
    if (memoryForm) {
        memoryForm.addEventListener("submit", function(e) {
            const songTitle = document.getElementById("song_title").value.trim();
            const artist = document.getElementById("artist").value.trim();
            const memoryTitle = document.getElementById("memory_title").value.trim();
            const story = document.getElementById("story").value.trim();
            const memoryDate = document.getElementById("memory_date").value;
            
            if (!songTitle || !artist || !memoryTitle || !story || !memoryDate) {
                showError("Please fill in all required fields marked with *.");
                e.preventDefault();
                return;
            }
            
            // Check rating is between 1 and 5
            const rating = parseInt(document.getElementById("rating").value);
            if (isNaN(rating) || rating < 1 || rating > 5) {
                showError("Rating must be between 1 and 5.");
                e.preventDefault();
                return;
            }
        });
    }
    
    // Helper function to display alerts nicely
    function showError(message) {
        // Look for existing alert container
        let alertBox = document.getElementById("js-validation-alert");
        if (!alertBox) {
            alertBox = document.createElement("div");
            alertBox.id = "js-validation-alert";
            alertBox.className = "alert alert-danger";
            
            // Insert at the top of the form
            const form = document.querySelector("form");
            form.parentNode.insertBefore(alertBox, form);
        }
        alertBox.textContent = message;
        alertBox.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
});
