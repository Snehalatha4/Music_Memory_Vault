import os
from PIL import Image, ImageDraw, ImageFont

def generate_default_profile(filepath):
    # Create a nice 200x200 avatar image with a gradient-like bg and a music symbol
    img = Image.new('RGBA', (200, 200), color=(138, 43, 226, 255)) # Violet bg
    draw = ImageDraw.Draw(img)
    
    # Draw a simple circle inner border
    draw.arc([10, 10, 190, 190], start=0, end=360, fill=(255, 0, 127, 255), width=4) # pink border
    
    # Draw a stylized music note representation
    # Draw note head
    draw.ellipse([70, 110, 100, 130], fill=(255, 255, 255, 255))
    # Draw note stem
    draw.rectangle([90, 60, 100, 120], fill=(255, 255, 255, 255))
    # Draw note flag
    draw.polygon([(100, 60), (130, 70), (130, 85), (100, 75)], fill=(255, 255, 255, 255))
    
    # Save image
    os.makedirs(os.path.dirname(filepath), exist_ok=True)
    img.save(filepath, 'PNG')
    print(f"Placeholder created at: {filepath}")

if __name__ == '__main__':
    project_root = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
    
    # Create in both images/ and uploads/ folders for safe relative routing
    generate_default_profile(os.path.join(project_root, 'images/default_profile.png'))
    generate_default_profile(os.path.join(project_root, 'uploads/default_profile.png'))
