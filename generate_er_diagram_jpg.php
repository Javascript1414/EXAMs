<?php
/**
 * ER Diagram JPG Generator
 * Creates a visual ER diagram as JPG image
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';

// Database structure
$db_structure = [
    'roles' => [
        'columns' => ['id (PK)', 'name', 'created_at'],
        'relations' => ['users'],
        'y' => 100
    ],
    'trades' => [
        'columns' => ['id (PK)', 'trade_name', 'description', 'created_at'],
        'relations' => ['users'],
        'y' => 100
    ],
    'users' => [
        'columns' => ['id (PK)', 'full_name', 'email (UQ)', 'phone', 'password', 'role_id (FK)', 'trade_id (FK)', 'status', 'created_at'],
        'relations' => ['otp_verifications', 'notifications', 'study_material_progress', 'exam_attempts', 'results', 'certificates', 'community_posts', 'login_logs'],
        'y' => 250
    ],
    'subjects' => [
        'columns' => ['id (PK)', 'name', 'description', 'created_at'],
        'relations' => ['study_materials'],
        'y' => 100
    ],
    'study_materials' => [
        'columns' => ['id (PK)', 'subject_id (FK)', 'title', 'description', 'file_path', 'created_at'],
        'relations' => ['study_material_progress'],
        'y' => 250
    ],
    'study_material_progress' => [
        'columns' => ['id (PK)', 'user_id (FK)', 'material_id (FK)', 'progress', 'completed_at'],
        'relations' => [],
        'y' => 400
    ],
    'questions' => [
        'columns' => ['id (PK)', 'question_text', 'options (JSON)', 'correct_answer', 'created_at'],
        'relations' => ['exam_questions', 'exam_answers'],
        'y' => 550
    ],
    'exams' => [
        'columns' => ['id (PK)', 'title', 'description', 'duration', 'total_questions', 'created_at'],
        'relations' => ['exam_questions', 'exam_attempts'],
        'y' => 700
    ],
    'exam_questions' => [
        'columns' => ['id (PK)', 'exam_id (FK)', 'question_id (FK)', 'order', 'created_at'],
        'relations' => [],
        'y' => 850
    ],
    'exam_attempts' => [
        'columns' => ['id (PK)', 'user_id (FK)', 'exam_id (FK)', 'start_time', 'end_time', 'score'],
        'relations' => ['exam_answers', 'results'],
        'y' => 1000
    ],
    'exam_answers' => [
        'columns' => ['id (PK)', 'attempt_id (FK)', 'user_id (FK)', 'question_id (FK)', 'answer', 'is_correct'],
        'relations' => [],
        'y' => 1150
    ],
    'results' => [
        'columns' => ['id (PK)', 'user_id (FK)', 'exam_id', 'attempt_id (FK)', 'score', 'created_at'],
        'relations' => ['certificates'],
        'y' => 1300
    ],
    'certificates' => [
        'columns' => ['id (PK)', 'user_id (FK)', 'result_id (FK)', 'certificate_no', 'issue_date'],
        'relations' => [],
        'y' => 1450
    ],
    'community_posts' => [
        'columns' => ['id (PK)', 'user_id (FK)', 'title', 'content', 'views', 'created_at'],
        'relations' => ['community_comments', 'community_reports'],
        'y' => 1600
    ],
    'community_comments' => [
        'columns' => ['id (PK)', 'post_id (FK)', 'user_id (FK)', 'comment_text', 'created_at'],
        'relations' => [],
        'y' => 1750
    ],
    'community_reports' => [
        'columns' => ['id (PK)', 'post_id (FK)', 'reason', 'status', 'created_at'],
        'relations' => [],
        'y' => 1900
    ],
    'otp_verifications' => [
        'columns' => ['id (PK)', 'user_id (FK)', 'otp_code', 'expires_at', 'is_used'],
        'relations' => [],
        'y' => 2050
    ],
    'notifications' => [
        'columns' => ['id (PK)', 'user_id (FK)', 'title', 'message', 'is_read', 'created_at'],
        'relations' => [],
        'y' => 2150
    ],
    'login_logs' => [
        'columns' => ['id (PK)', 'user_id (FK)', 'login_time', 'ip_address', 'user_agent'],
        'relations' => [],
        'y' => 2250
    ]
];

// Create image
$width = 1600;
$height = 2400;
$img = imagecreatetruecolor($width, $height);

// Colors
$white = imagecolorallocate($img, 255, 255, 255);
$black = imagecolorallocate($img, 0, 0, 0);
$darkblue = imagecolorallocate($img, 25, 51, 102);
$lightblue = imagecolorallocate($img, 173, 216, 230);
$lightgray = imagecolorallocate($img, 240, 240, 240);
$gold = imagecolorallocate($img, 255, 215, 0);
$darkgreen = imagecolorallocate($img, 0, 100, 50);
$lightgreen = imagecolorallocate($img, 144, 238, 144);

// Fill background
imagefilledrectangle($img, 0, 0, $width, $height, $white);

// Title section
imagefilledrectangle($img, 0, 0, $width, 50, $darkblue);
imagestring($img, 5, 20, 15, "DATABASE ER DIAGRAM - exams_lms", $white);

// Draw tables
$table_positions = [];
$col = 0;
$row = 0;
$box_width = 280;
$box_height_per_col = 30;

$x_positions = [50, 400, 750, 1100];
$y_offset = 70;

$table_idx = 0;
foreach ($db_structure as $table_name => $table_data) {
    // Calculate position
    $x = $x_positions[$col % 4];
    $y = $y_offset + $row * 40;
    
    if ($col % 4 === 3) {
        $row++;
        $col = 0;
    } else {
        $col++;
    }
    
    $table_height = 25 + (count($table_data['columns']) * 15);
    
    // Draw table box
    imagerectangle($img, $x, $y, $x + $box_width, $y + $table_height, $darkblue);
    
    // Table header
    imagefilledrectangle($img, $x, $y, $x + $box_width, $y + 22, $darkblue);
    imagestring($img, 2, $x + 5, $y + 5, substr($table_name, 0, 30), $white);
    
    // Store position for relationships
    $table_positions[$table_name] = [
        'x' => $x + $box_width/2,
        'y' => $y + $table_height/2,
        'box_y' => $y,
        'box_height' => $table_height
    ];
    
    // Draw columns
    $col_y = $y + 24;
    foreach ($table_data['columns'] as $col_data) {
        $col_text = substr($col_data, 0, 32);
        
        if (strpos($col_data, 'PK') !== false) {
            $col_color = $gold;
        } elseif (strpos($col_data, 'FK') !== false) {
            $col_color = $lightgreen;
        } elseif (strpos($col_data, 'UQ') !== false) {
            $col_color = $lightblue;
        } else {
            $col_color = $lightgray;
        }
        
        imagefilledrectangle($img, $x+1, $col_y, $x + $box_width - 1, $col_y + 14, $col_color);
        imagerectangle($img, $x+1, $col_y, $x + $box_width - 1, $col_y + 14, $black);
        imagestring($img, 1, $x + 3, $col_y + 1, $col_text, $black);
        
        $col_y += 15;
    }
}

// Draw legend
$legend_y = $y_offset + ($row + 1) * 40 + 20;
imagestring($img, 2, 50, $legend_y, "Legend:", $darkblue);
imagefilledrectangle($img, 50, $legend_y + 20, 70, $legend_y + 30, $gold);
imagerectangle($img, 50, $legend_y + 20, 70, $legend_y + 30, $black);
imagestring($img, 2, 80, $legend_y + 20, "PK - Primary Key", $black);

imagefilledrectangle($img, 250, $legend_y + 20, 270, $legend_y + 30, $lightgreen);
imagerectangle($img, 250, $legend_y + 20, 270, $legend_y + 30, $black);
imagestring($img, 2, 280, $legend_y + 20, "FK - Foreign Key", $black);

imagefilledrectangle($img, 500, $legend_y + 20, 520, $legend_y + 30, $lightblue);
imagerectangle($img, 500, $legend_y + 20, 520, $legend_y + 30, $black);
imagestring($img, 2, 530, $legend_y + 20, "UQ - Unique", $black);

// Footer
$footer_y = $legend_y + 60;
imagestring($img, 1, 50, $footer_y, "Total Tables: " . count($db_structure), $darkblue);
imagestring($img, 1, 50, $footer_y + 20, "Generated: " . date('Y-m-d H:i:s'), $darkblue);

// Save as JPG
$uploads_dir = __DIR__ . '/uploads';
if (!is_dir($uploads_dir)) {
    mkdir($uploads_dir, 0755, true);
}

$output_path = $uploads_dir . '/ER_Diagram_' . date('YmdHis') . '.jpg';
$success = imagejpeg($img, $output_path, 95);
imagedestroy($img);

if ($success) {
    echo "✅ ER Diagram generated successfully!\n\n";
    echo "📁 File saved: " . $output_path . "\n";
    echo "📊 View at: " . BASE_URL . "/uploads/" . basename($output_path) . "\n";
    echo "📏 Dimensions: " . $width . "x" . $height . " pixels\n";
    echo "📊 Tables: " . count($db_structure) . "\n";
} else {
    echo "❌ Failed to generate ER diagram\n";
}
?>
