<?php
/**
 * ER Diagram Generator with Image Output
 * Uses PHP GD Library to create a visual ER diagram
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';

// Get all tables with their relationships
$tables = [];
$relationships = [];

$tables_result = $pdo->query('SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE()');
$table_names = $tables_result->fetchAll(PDO::FETCH_COLUMN);

// Collect table information
foreach ($table_names as $table) {
    $columns_result = $pdo->query("DESCRIBE $table");
    $columns = $columns_result->fetchAll(PDO::FETCH_ASSOC);
    
    $primary_key = null;
    $column_list = [];
    
    foreach ($columns as $col) {
        $col_name = $col['Field'];
        $col_type = $col['Type'];
        if ($col['Key'] === 'PRI') {
            $primary_key = $col_name;
        }
        $column_list[] = ['name' => $col_name, 'type' => $col_type, 'key' => $col['Key']];
    }
    
    // Get foreign keys
    $fk_result = $pdo->query("
        SELECT COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
        FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
        WHERE TABLE_NAME = '$table' AND TABLE_SCHEMA = DATABASE() AND REFERENCED_TABLE_NAME IS NOT NULL
    ");
    $foreign_keys = $fk_result->fetchAll(PDO::FETCH_ASSOC);
    
    $tables[$table] = [
        'columns' => $column_list,
        'primary_key' => $primary_key,
        'foreign_keys' => $foreign_keys
    ];
    
    // Add relationships
    foreach ($foreign_keys as $fk) {
        $relationships[] = [
            'from_table' => $table,
            'from_col' => $fk['COLUMN_NAME'],
            'to_table' => $fk['REFERENCED_TABLE_NAME'],
            'to_col' => $fk['REFERENCED_COLUMN_NAME']
        ];
    }
}

// Create image
$width = 2000;
$height = 3000;
$img = imagecreatetruecolor($width, $height);

// Colors
$white = imagecolorallocate($img, 255, 255, 255);
$black = imagecolorallocate($img, 0, 0, 0);
$lightgray = imagecolorallocate($img, 240, 240, 240);
$darkblue = imagecolorallocate($img, 25, 51, 102);
$lightblue = imagecolorallocate($img, 173, 216, 230);
$gold = imagecolorallocate($img, 255, 215, 0);
$red = imagecolorallocate($img, 220, 50, 50);
$green = imagecolorallocate($img, 50, 150, 50);

// Fill background
imagefilledrectangle($img, 0, 0, $width, $height, $white);

// Title
$title = "Database ER Diagram - exams_lms";
$font_title = 5;
$title_x = ($width - strlen($title) * imagefontwidth($font_title)) / 2;
imagestringup($img, $font_title, 50, 80, $title, $darkblue);

// Position tables in a grid
$col_width = 300;
$row_height = 200;
$start_x = 50;
$start_y = 120;

$table_positions = [];
$idx = 0;
$cols = 5;

foreach ($tables as $table_name => $table_data) {
    $col = $idx % $cols;
    $row = intval($idx / $cols);
    
    $x = $start_x + $col * $col_width;
    $y = $start_y + $row * $row_height;
    
    $table_positions[$table_name] = ['x' => $x, 'y' => $y, 'width' => 280, 'height' => 30 + (count($table_data['columns']) * 18)];
    $idx++;
}

// Draw tables
foreach ($tables as $table_name => $table_data) {
    $pos = $table_positions[$table_name];
    $x = $pos['x'];
    $y = $pos['y'];
    $w = $pos['width'];
    $h = $pos['height'];
    
    // Table border
    imagerectangle($img, $x, $y, $x + $w, $y + $h, $darkblue);
    
    // Table header
    imagefilledrectangle($img, $x, $y, $x + $w, $y + 25, $darkblue);
    imagestring($img, 2, $x + 5, $y + 7, substr($table_name, 0, 25), $white);
    
    // Columns
    $col_y = $y + 28;
    foreach ($table_data['columns'] as $col) {
        $is_pk = $col['key'] === 'PRI';
        $is_fk = false;
        
        foreach ($table_data['foreign_keys'] as $fk) {
            if ($fk['COLUMN_NAME'] === $col['name']) {
                $is_fk = true;
                break;
            }
        }
        
        $col_text = '';
        if ($is_pk) $col_text = '🔑 ';
        if ($is_fk) $col_text .= '🔗 ';
        $col_text .= $col['name'];
        
        $col_color = $is_pk ? $gold : ($is_fk ? $lightblue : $white);
        imagefilledrectangle($img, $x, $col_y, $x + $w, $col_y + 16, $col_color);
        imagerectangle($img, $x, $col_y, $x + $w, $col_y + 16, $black);
        
        imagestring($img, 1, $x + 3, $col_y + 2, substr($col_text, 0, 32), $black);
        $col_y += 17;
    }
}

// Draw relationships
foreach ($relationships as $rel) {
    $from_pos = $table_positions[$rel['from_table']];
    $to_pos = $table_positions[$rel['to_table']];
    
    $from_x = $from_pos['x'] + $from_pos['width'] / 2;
    $from_y = $from_pos['y'] + $from_pos['height'];
    
    $to_x = $to_pos['x'] + $to_pos['width'] / 2;
    $to_y = $to_pos['y'];
    
    // Draw line
    imageline($img, intval($from_x), intval($from_y), intval($to_x), intval($to_y), $red);
    
    // Draw arrow
    $angle = atan2($to_y - $from_y, $to_x - $from_x);
    $arr_size = 10;
    $arrow_x1 = intval($to_x - $arr_size * cos($angle - M_PI / 6));
    $arrow_y1 = intval($to_y - $arr_size * sin($angle - M_PI / 6));
    $arrow_x2 = intval($to_x - $arr_size * cos($angle + M_PI / 6));
    $arrow_y2 = intval($to_y - $arr_size * sin($angle + M_PI / 6));
    
    imageline($img, intval($to_x), intval($to_y), $arrow_x1, $arrow_y1, $red);
    imageline($img, intval($to_x), intval($to_y), $arrow_x2, $arrow_y2, $red);
}

// Footer
$footer = "Total Tables: " . count($tables) . " | Total Relationships: " . count($relationships);
imagestring($img, 2, 50, $height - 30, $footer, $darkblue);

// Save as JPG
$output_path = __DIR__ . '/uploads/ER_Diagram.jpg';
if (!is_dir(dirname($output_path))) {
    mkdir(dirname($output_path), 0755, true);
}

imagejpeg($img, $output_path, 90);
imagedestroy($img);

echo "ER Diagram generated successfully: " . $output_path;
echo "\n\nDatabase Summary:\n";
echo "Total Tables: " . count($tables) . "\n";
echo "Total Relationships: " . count($relationships) . "\n\n";
echo "Tables:\n";
foreach ($tables as $table_name => $table_data) {
    echo "- " . $table_name . " (" . count($table_data['columns']) . " columns)\n";
}
?>
