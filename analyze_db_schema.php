<?php
/**
 * Advanced ER Diagram Generator
 * Creates detailed database schema visualization
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';

// Get all tables
$tables_data = [];
$all_relationships = [];

$stmt = $pdo->query("
    SELECT TABLE_NAME 
    FROM INFORMATION_SCHEMA.TABLES 
    WHERE TABLE_SCHEMA = DATABASE()
    ORDER BY TABLE_NAME
");

$tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

echo "=== DATABASE SCHEMA ANALYSIS ===\n";
echo "Database: " . DB_NAME . "\n";
echo "Total Tables: " . count($tables) . "\n\n";

foreach ($tables as $table) {
    echo "TABLE: $table\n";
    echo str_repeat("-", 60) . "\n";
    
    // Get columns
    $cols_result = $pdo->query("DESCRIBE $table");
    $columns = $cols_result->fetchAll(PDO::FETCH_ASSOC);
    
    $tables_data[$table] = [
        'columns' => $columns,
        'column_names' => array_column($columns, 'Field')
    ];
    
    foreach ($columns as $col) {
        $key_type = $col['Key'] ?: '-';
        printf("  %-30s %-20s %s\n", $col['Field'], $col['Type'], "[$key_type]");
    }
    
    // Get foreign keys
    $fk_stmt = $pdo->query("
        SELECT COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
        FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
        WHERE TABLE_NAME = '$table' 
        AND TABLE_SCHEMA = DATABASE() 
        AND REFERENCED_TABLE_NAME IS NOT NULL
    ");
    
    $foreign_keys = $fk_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($foreign_keys)) {
        echo "\n  Foreign Keys:\n";
        foreach ($foreign_keys as $fk) {
            echo "    {$fk['COLUMN_NAME']} -> {$fk['REFERENCED_TABLE_NAME']}.{$fk['REFERENCED_COLUMN_NAME']}\n";
            $all_relationships[] = $fk;
        }
    }
    
    echo "\n";
}

echo "=== RELATIONSHIPS ===\n";
echo "Total Foreign Keys: " . count($all_relationships) . "\n\n";

foreach ($all_relationships as $rel) {
    echo "- {$rel['COLUMN_NAME']} references {$rel['REFERENCED_TABLE_NAME']}.{$rel['REFERENCED_COLUMN_NAME']}\n";
}

// Generate Mermaid ER Diagram syntax
$mermaid_code = "erDiagram\n";
$processed_tables = [];

foreach ($tables as $table) {
    if (isset($tables_data[$table])) {
        $processed_tables[$table] = true;
        
        // Add entity definition
        $mermaid_code .= "    $table {\n";
        
        foreach ($tables_data[$table]['columns'] as $col) {
            $col_name = $col['Field'];
            $col_type = strtoupper(explode('(', $col['Type'])[0]);
            $key_info = '';
            
            if ($col['Key'] === 'PRI') {
                $key_info = ' PK';
            } elseif ($col['Key'] === 'MUL' || $col['Key'] === 'UNI') {
                $key_info = ' FK';
            }
            
            $mermaid_code .= "        $col_type $col_name$key_info\n";
        }
        
        $mermaid_code .= "    }\n";
    }
}

// Add relationships
foreach ($all_relationships as $rel) {
    $col = $rel['COLUMN_NAME'];
    $ref_table = $rel['REFERENCED_TABLE_NAME'];
    $ref_col = $rel['REFERENCED_COLUMN_NAME'];
    
    $from_table = null;
    // Find which table this column belongs to
    foreach ($tables_data as $table_name => $tdata) {
        if (in_array($col, $tdata['column_names'])) {
            $from_table = $table_name;
            break;
        }
    }
    
    if ($from_table) {
        $mermaid_code .= "    $from_table ||--o{ $ref_table : \"$col-$ref_col\"\n";
    }
}

// Save Mermaid code
$mermaid_file = __DIR__ . '/ER_Diagram_Mermaid.md';
file_put_contents($mermaid_file, "# ER Diagram Code (Mermaid)\n\n" . "```mermaid\n" . $mermaid_code . "```\n");

echo "\n=== OUTPUT ===\n";
echo "Mermaid ER code saved to: ER_Diagram_Mermaid.md\n";
echo "\nTo convert to JPG, use: https://mermaid.live/\n";
?>
