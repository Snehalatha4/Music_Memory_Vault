# 🎵 Music Memory Vault - Project Guide
> **"Every Song Tells a Story."**
> A Full-Stack WAMP Mini-Project using HTML, CSS, JavaScript, PHP, MySQL, and Python.

---

## 📂 Project Architecture & Code Structure
The files created inside the `MusicMemoryVault/` directory correspond directly to the required academic layers:

```
MusicMemoryVault/
│
├── database/
│   └── musicvault.sql         <-- [LAYER 1] Database schema definitions
│
├── css/
│   └── style.css              <-- [LAYER 2] Master stylesheet (glassmorphism/dark mode)
│
├── js/
│   ├── script.js              <-- [LAYER 2] AJAX favorite toggles, modal handling
│   ├── search.js              <-- [LAYER 2] Client-side live search filtering
│   └── validation.js          <-- [LAYER 2] Client-side form inputs verification
│
├── includes/
│   ├── connect.php            <-- [LAYER 2] PHP database connection & auto-installer
│   ├── session.php            <-- [LAYER 2] Session handling and authorization
│   ├── db_helpers.php         <-- [LAYER 2] Entity insertion helpers (ORM-like resolver)
│   └── navbar.php             <-- [LAYER 2] Reusable navigation component
│
├── python/
│   ├── analytics.py           <-- [LAYER 3] MySQL connector, lexicon sentiment analyzer, Matplotlib charts
│   └── create_placeholders.py <-- Utility to generate default images
│
├── uploads/
│   ├── memoryPhotos/          <-- Uploaded photos for memory cards
│   └── charts/                <-- Python-generated Matplotlib images
│
├── index.php                  <-- Welcome/landing gate
├── register.php               <-- User registration (Bcrypt hashing)
├── login.php                  <-- Session starter/sign-in
├── dashboard.php              <-- User dashboard (Metrics, recent entries)
├── addMemory.php              <-- Create memory (Form upload, launches Python backend)
├── editMemory.php             <-- Update memory (Form or AJAX favorite update)
├── deleteMemory.php           <-- Delete memory (Filesystem cleanup, updates python charts)
├── memories.php               <-- Search, sort, filter memories grid & details API
├── search.php                 <-- Server-side SQL search using LIKE operations
├── timeline.php               <-- Chronological visual path of memories
└── logout.php                 <-- Session destruction
```

---

## 🛠️ Step-by-Step Setup Guide

Follow these steps to run the application on your computer using WAMP:

### Step 1: Clone or Copy Project Files
Copy the entire `MusicMemoryVault/` folder into your WAMP web root directory.
* Typically: `C:\wamp64\www\MusicMemoryVault` (for WAMP)
* Or `C:\xampp\htdocs\MusicMemoryVault` (if using XAMPP)

### Step 2: Start WAMP Server
Double-click the WAMP icon on your desktop. Ensure the status bar turns green, indicating Apache, PHP, and MySQL services are fully active.

### Step 3: Run the Zero-Config Auto-Setup
The project includes a **smart installer** inside `includes/connect.php`. 
1. Open your web browser.
2. Navigate to: `http://localhost/MusicMemoryVault/`
3. Click **Start Your Vault** or **Register**.
4. The database `musicvault` and all required tables (Users, Songs, Artists, Albums, Genres, Memories) will be **automatically created and imported**! No manual phpMyAdmin actions are required.

> **Note:** If your MySQL root user has a password set, open `includes/connect.php` and `python/analytics.py` to update the password parameter.

---

## 🖥️ How the Four Exercises/Layers are Met

| Layer in Syllabus | Implementation in Project | Description |
| :--- | :--- | :--- |
| **Layer 1: DBMS** | `database/musicvault.sql` | Normalised MySQL database structure with standard relationships, primary keys, and foreign keys. |
| **Layer 2: Frontend Scripting / Web Server** | HTML5, CSS3, JS & PHP | **HTML/CSS/JS:** Glassmorphism neon-dark UI, Client-side live search (`js/search.js`), Client validation (`js/validation.js`).<br>**PHP:** Session controls, file upload management, AJAX endpoints. |
| **Layer 3: Business Logic / App Server** | `python/analytics.py` | Connects to MySQL using `mysql-connector-python`. Reads entries, runs lexicon-based sentiment analysis, updates database columns, and compiles Matplotlib PNG charts. |
| **Layer 4: Full Stack Programming** | `MusicMemoryVault` | Integrating all three layers to create a complete story-based journal application. |

---

