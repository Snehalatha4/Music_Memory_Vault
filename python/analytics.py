import sys
import mysql.connector
import re
import os
import matplotlib
# Use Agg backend for non-GUI headless plotting
matplotlib.use('Agg')
import matplotlib.pyplot as plt
import pandas as pd

# Database Configuration (matching WAMP standards)
DB_CONFIG = {
    'host': 'localhost',
    'user': 'root',
    'password': '',
    'database': 'musicvault'
}

# Offline Sentiment Lexicon for 100% reliability during evaluation
POSITIVE_WORDS = {
    'happy', 'love', 'loved', 'beautiful', 'great', 'awesome', 'amazing', 'perfect', 
    'joy', 'smile', 'glad', 'wonderful', 'fun', 'excited', 'nice', 'peaceful', 
    'motivated', 'inspired', 'celebration', 'dance', 'laugh', 'romantic', 'cheerful',
    'good', 'best', 'creative', 'magical', 'sweet', 'warm', 'smiling', 'favorite'
}

NEGATIVE_WORDS = {
    'sad', 'cry', 'cried', 'heartbroken', 'lonely', 'breakup', 'pain', 'hurt', 
    'tear', 'tears', 'grief', 'bad', 'worst', 'angry', 'hate', 'alone', 'scared', 
    'fear', 'miss', 'missed', 'depressed', 'dark', 'gloomy', 'broken', 'empty',
    'bitter', 'sorry', 'anxious', 'stress', 'worried', 'bored', 'tired', 'ruined'
}

def analyze_sentiment(text):
    """
    Performs sentiment analysis on text using local lexicon matching.
    Returns: (label, score)
    score range: -1.0 (highly negative) to 1.0 (highly positive)
    """
    if not text:
        return 'Neutral', 0.0
        
    words = re.findall(r'\w+', text.lower())
    pos_count = sum(1 for w in words if w in POSITIVE_WORDS)
    neg_count = sum(1 for w in words if w in NEGATIVE_WORDS)
    
    total = pos_count + neg_count
    if total == 0:
        return 'Neutral', 0.0
        
    score = (pos_count - neg_count) / total
    
    # Classify based on score thresholds
    if score > 0.15:
        label = 'Positive'
    elif score < -0.15:
        label = 'Negative'
    else:
        label = 'Neutral'
        
    # Standardize score to [0, 1] for visual display
    # (absolute confidence rating)
    display_score = abs(score)
    
    return label, display_score

def update_memory_sentiment(memory_id):
    """
    Fetches a memory story, analyzes its sentiment, and updates database.
    """
    try:
        conn = mysql.connector.connect(**DB_CONFIG)
        cursor = conn.cursor(dictionary=True)
        
        # Get story
        cursor.execute("SELECT story FROM memories WHERE memory_id = %s", (memory_id,))
        row = cursor.fetchone()
        
        if row:
            label, score = analyze_sentiment(row['story'])
            
            # Update database
            cursor.execute(
                "UPDATE memories SET sentiment_label = %s, sentiment_score = %s WHERE memory_id = %s",
                (label, score, memory_id)
            )
            conn.commit()
            print(f"Updated Sentiment for Memory ID {memory_id}: {label} ({score:.2f})")
            
        cursor.close()
        conn.close()
    except Exception as e:
        print(f"Sentiment Analysis DB Error: {e}", file=sys.stderr)

def generate_analytics_charts():
    """
    Queries database for all memories, compiles analytics, and outputs styled PNG charts.
    """
    charts_dir = os.path.join(os.path.dirname(os.path.abspath(__file__)), '../uploads/charts')
    os.makedirs(charts_dir, exist_ok=True)
    
    try:
        conn = mysql.connector.connect(**DB_CONFIG)
        
        # 1. EMOTIONS DISTRIBUTION CHART (PIE)
        df_emotions = pd.read_sql("SELECT emotion, COUNT(*) as count FROM memories GROUP BY emotion", conn)
        
        plt.style.use('dark_background')
        fig, ax = plt.subplots(figsize=(6, 5), facecolor='#16122d')
        ax.set_facecolor('#16122d')
        
        if not df_emotions.empty:
            colors = ['#8a2be2', '#ff007f', '#00f2fe', '#ffd700', '#7fff00', '#ff4500', '#1e90ff', '#ff69b4', '#00ff7f']
            wedges, texts, autotexts = ax.pie(
                df_emotions['count'], 
                labels=df_emotions['emotion'], 
                autopct='%1.1f%%',
                startangle=140,
                colors=colors[:len(df_emotions)],
                textprops=dict(color="w", weight="bold")
            )
            plt.setp(autotexts, size=9, weight="bold")
            ax.set_title("Distribution of Emotions in Your Vault", color='white', weight='bold', pad=20)
        else:
            ax.text(0.5, 0.5, "No memories recorded yet.\nAdd memories to see charts!", 
                    ha='center', va='center', color='white', fontsize=12)
            ax.axis('off')
            
        plt.tight_layout()
        plt.savefig(os.path.join(charts_dir, 'emotions_pie.png'), dpi=150, facecolor=fig.get_facecolor(), edgecolor='none')
        plt.close()
        
        # 2. TOP ARTISTS CHART (BAR)
        query_artists = """
            SELECT a.artist_name, COUNT(*) as count 
            FROM memories m
            JOIN songs s ON m.song_id = s.song_id
            JOIN artists a ON s.artist_id = a.artist_id
            GROUP BY a.artist_name
            ORDER BY count DESC
            LIMIT 5
        """
        df_artists = pd.read_sql(query_artists, conn)
        
        fig, ax = plt.subplots(figsize=(6, 5), facecolor='#16122d')
        ax.set_facecolor('#16122d')
        
        if not df_artists.empty:
            bars = ax.bar(df_artists['artist_name'], df_artists['count'], color='#ff007f', edgecolor='#8a2be2', width=0.5)
            ax.set_ylabel('Number of Memories', color='white', weight='bold')
            ax.set_xlabel('Artists', color='white', weight='bold')
            ax.set_title('Top Artists in Your Vault', color='white', weight='bold', pad=20)
            ax.tick_params(colors='white')
            # Add counts on top of bars
            for bar in bars:
                height = bar.get_height()
                ax.annotate(f'{int(height)}',
                            xy=(bar.get_x() + bar.get_width() / 2, height),
                            xytext=(0, 3),  # 3 points vertical offset
                            textcoords="offset points",
                            ha='center', va='bottom', color='white', weight='bold')
        else:
            ax.text(0.5, 0.5, "No artists recorded yet.", ha='center', va='center', color='white', fontsize=12)
            ax.axis('off')
            
        plt.tight_layout()
        plt.savefig(os.path.join(charts_dir, 'artists_bar.png'), dpi=150, facecolor=fig.get_facecolor(), edgecolor='none')
        plt.close()

        # 3. MONTHLY MEMORY FREQUENCY CHART (LINE)
        query_monthly = """
            SELECT DATE_FORMAT(memory_date, '%Y-%m') as month, COUNT(*) as count 
            FROM memories 
            GROUP BY month 
            ORDER BY month ASC 
            LIMIT 12
        """
        df_monthly = pd.read_sql(query_monthly, conn)
        
        fig, ax = plt.subplots(figsize=(7, 4.5), facecolor='#16122d')
        ax.set_facecolor('#16122d')
        
        if not df_monthly.empty:
            ax.plot(df_monthly['month'], df_monthly['count'], marker='o', color='#00f2fe', linewidth=2.5, markersize=8)
            ax.fill_between(df_monthly['month'], df_monthly['count'], color='#00f2fe', alpha=0.15)
            ax.set_ylabel('Memories Added', color='white', weight='bold')
            ax.set_xlabel('Month', color='white', weight='bold')
            ax.set_title('Memory Capture Activity Over Time', color='white', weight='bold', pad=20)
            ax.tick_params(colors='white')
            plt.xticks(rotation=45)
            ax.grid(True, color='rgba(255,255,255,0.05)')
        else:
            ax.text(0.5, 0.5, "No temporal data available.", ha='center', va='center', color='white', fontsize=12)
            ax.axis('off')
            
        plt.tight_layout()
        plt.savefig(os.path.join(charts_dir, 'monthly_memories.png'), dpi=150, facecolor=fig.get_facecolor(), edgecolor='none')
        plt.close()
        
        conn.close()
        print("Analytics charts successfully generated!")
    except Exception as e:
        print(f"Analytics Generation Error: {e}", file=sys.stderr)

if __name__ == '__main__':
    # If a specific memory ID is passed, run sentiment analysis first
    if len(sys.argv) > 1:
        mem_id = sys.argv[1]
        if mem_id.isdigit():
            update_memory_sentiment(int(mem_id))
            
    # Always regenerate aggregate charts
    generate_analytics_charts()
