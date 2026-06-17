#!/usr/bin/env python3
"""
Database ER Diagram Generator
Converts database schema to JPG image
"""

import json
import subprocess
from datetime import datetime
from PIL import Image, ImageDraw, ImageFont
import os

# Database configuration
DB_CONFIG = {
    'tables': {
        'roles': {'pk': 'id', 'fk': [], 'columns': ['id', 'name', 'created_at']},
        'trades': {'pk': 'id', 'fk': [], 'columns': ['id', 'trade_name', 'description', 'created_at']},
        'users': {'pk': 'id', 'fk': ['role_id', 'trade_id'], 'columns': ['id', 'full_name', 'email', 'phone', 'password', 'role_id', 'trade_id', 'status', 'created_at']},
        'subjects': {'pk': 'id', 'fk': [], 'columns': ['id', 'name', 'description', 'created_at']},
        'study_materials': {'pk': 'id', 'fk': ['subject_id'], 'columns': ['id', 'subject_id', 'title', 'description', 'file_path', 'created_at']},
        'questions': {'pk': 'id', 'fk': [], 'columns': ['id', 'question_text', 'options', 'correct_answer', 'created_at']},
        'exams': {'pk': 'id', 'fk': [], 'columns': ['id', 'title', 'duration', 'total_questions', 'created_at']},
        'exam_questions': {'pk': 'id', 'fk': ['exam_id', 'question_id'], 'columns': ['id', 'exam_id', 'question_id', 'order']},
        'exam_attempts': {'pk': 'id', 'fk': ['user_id', 'exam_id'], 'columns': ['id', 'user_id', 'exam_id', 'start_time', 'end_time', 'score']},
        'results': {'pk': 'id', 'fk': ['user_id', 'exam_id', 'attempt_id'], 'columns': ['id', 'user_id', 'exam_id', 'attempt_id', 'score', 'created_at']},
        'certificates': {'pk': 'id', 'fk': ['user_id', 'result_id'], 'columns': ['id', 'user_id', 'result_id', 'certificate_no', 'issue_date']},
        'community_posts': {'pk': 'id', 'fk': ['user_id'], 'columns': ['id', 'user_id', 'title', 'content', 'views', 'created_at']},
        'community_comments': {'pk': 'id', 'fk': ['post_id', 'user_id'], 'columns': ['id', 'post_id', 'user_id', 'comment_text', 'created_at']},
        'otp_verifications': {'pk': 'id', 'fk': ['user_id'], 'columns': ['id', 'user_id', 'otp_code', 'expires_at', 'is_used']},
        'notifications': {'pk': 'id', 'fk': ['user_id'], 'columns': ['id', 'user_id', 'title', 'message', 'is_read', 'created_at']},
        'login_logs': {'pk': 'id', 'fk': ['user_id'], 'columns': ['id', 'user_id', 'login_time', 'ip_address', 'user_agent']},
    }
}

def create_er_diagram():
    """Create ER diagram image"""
    
    # Image dimensions
    width = 2000
    height = 2800
    
    # Create white image
    img = Image.new('RGB', (width, height), 'white')
    draw = ImageDraw.Draw(img)
    
    # Colors
    colors = {
        'darkblue': '#193366',
        'lightblue': '#add8e6',
        'white': '#ffffff',
        'black': '#000000',
        'gold': '#ffd700',
        'green': '#90ee90',
        'gray': '#f0f0f0'
    }
    
    # Try to load a nice font
    try:
        font_large = ImageFont.truetype("arial.ttf", 16)
        font_medium = ImageFont.truetype("arial.ttf", 12)
        font_small = ImageFont.truetype("arial.ttf", 10)
    except:
        font_large = ImageFont.load_default()
        font_medium = ImageFont.load_default()
        font_small = ImageFont.load_default()
    
    # Draw title
    draw.rectangle([0, 0, width, 60], fill=colors['darkblue'])
    draw.text((30, 15), "DATABASE ER DIAGRAM - exams_lms", fill=colors['white'], font=font_large)
    
    # Table positions in grid
    tables = DB_CONFIG['tables']
    col_width = 350
    row_height = 220
    start_x = 40
    start_y = 80
    cols = 5
    
    table_positions = {}
    
    # Draw tables
    idx = 0
    for table_name, table_data in tables.items():
        col = idx % cols
        row = idx // cols
        
        x = start_x + col * col_width
        y = start_y + row * row_height
        
        # Store position
        table_positions[table_name] = {'x': x, 'y': y}
        
        # Table box
        box_height = 25 + len(table_data['columns']) * 18
        draw.rectangle([x, y, x + 320, y + box_height], outline=colors['darkblue'], width=2)
        
        # Table header
        draw.rectangle([x, y, x + 320, y + 25], fill=colors['darkblue'])
        draw.text((x + 10, y + 5), table_name, fill=colors['white'], font=font_medium)
        
        # Columns
        col_y = y + 28
        for col_name in table_data['columns']:
            col_color = colors['gray']
            if col_name == table_data['pk']:
                col_color = colors['gold']
            elif col_name in table_data['fk']:
                col_color = colors['green']
            
            draw.rectangle([x+1, col_y, x+319, col_y+16], fill=col_color)
            draw.rectangle([x+1, col_y, x+319, col_y+16], outline=colors['black'])
            draw.text((x+5, col_y+1), col_name, fill=colors['black'], font=font_small)
            col_y += 17
        
        idx += 1
    
    # Footer
    footer_y = start_y + ((len(tables) // cols + 1) * row_height) + 20
    draw.text((40, footer_y), f"Total Tables: {len(tables)} | Generated: {datetime.now().strftime('%Y-%m-%d %H:%M:%S')}", 
              fill=colors['darkblue'], font=font_small)
    
    # Legend
    legend_y = footer_y + 40
    draw.rectangle([40, legend_y, 320, legend_y+80], fill=colors['gray'], outline=colors['darkblue'])
    draw.rectangle([50, legend_y+10, 70, legend_y+25], fill=colors['gold'])
    draw.text((80, legend_y+8), "PK - Primary Key", fill=colors['black'], font=font_small)
    draw.rectangle([50, legend_y+30, 70, legend_y+45], fill=colors['green'])
    draw.text((80, legend_y+28), "FK - Foreign Key", fill=colors['black'], font=font_small)
    draw.rectangle([50, legend_y+50, 70, legend_y+65], fill=colors['lightblue'])
    draw.text((80, legend_y+48), "Column", fill=colors['black'], font=font_small)
    
    # Save
    output_dir = os.path.join(os.path.dirname(__file__), 'uploads')
    os.makedirs(output_dir, exist_ok=True)
    output_path = os.path.join(output_dir, f'ER_Diagram_{datetime.now().strftime("%Y%m%d_%H%M%S")}.jpg')
    
    img.save(output_path, 'JPEG', quality=95)
    
    return output_path

if __name__ == '__main__':
    try:
        output_file = create_er_diagram()
        print(f"✅ ER Diagram generated successfully!")
        print(f"📁 File: {output_file}")
        print(f"📊 Size: 2000x2800 pixels")
    except Exception as e:
        print(f"❌ Error: {e}")
        import traceback
        traceback.print_exc()
