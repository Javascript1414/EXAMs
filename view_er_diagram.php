<?php
/**
 * Database ER Diagram Viewer
 * Interactive web interface to view database relationships
 */

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

// Get database structure
$tables_info = [];

// Fetch all tables
$tables_result = $pdo->query("
    SELECT TABLE_NAME 
    FROM INFORMATION_SCHEMA.TABLES 
    WHERE TABLE_SCHEMA = DATABASE()
    ORDER BY TABLE_NAME
");

$all_tables = $tables_result ? $tables_result->fetchAll(PDO::FETCH_COLUMN) : [];

foreach ($all_tables as $table) {
    // Get columns
    $cols_result = $pdo->query("DESCRIBE `$table`");
    $columns = $cols_result ? $cols_result->fetchAll(PDO::FETCH_ASSOC) : [];
    
    // Get foreign keys
    $fk_result = $pdo->query("
        SELECT COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
        FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
        WHERE TABLE_NAME = '$table' 
        AND TABLE_SCHEMA = DATABASE()
        AND REFERENCED_TABLE_NAME IS NOT NULL
    ");
    
    $foreign_keys = $fk_result ? $fk_result->fetchAll(PDO::FETCH_ASSOC) : [];
    
    $tables_info[$table] = [
        'columns' => $columns,
        'foreign_keys' => $foreign_keys
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database ER Diagram - exams_lms</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/mermaid@10/dist/mermaid.min.css">
    <script src="https://cdn.jsdelivr.net/npm/mermaid@10/dist/mermaid.min.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 1400px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 { font-size: 2.5em; margin-bottom: 10px; }
        .header p { font-size: 1.1em; opacity: 0.9; }
        .controls {
            display: flex;
            gap: 10px;
            justify-content: center;
            flex-wrap: wrap;
            padding: 20px;
            background: #f8f9fa;
            border-bottom: 1px solid #ddd;
        }
        button {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            background: #667eea;
            color: white;
            cursor: pointer;
            font-size: 1em;
            transition: all 0.3s;
        }
        button:hover { background: #764ba2; transform: translateY(-2px); box-shadow: 0 5px 15px rgba(0,0,0,0.2); }
        .content { padding: 30px; }
        .mermaid {
            display: flex;
            justify-content: center;
            margin: 20px 0;
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            overflow-x: auto;
        }
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 30px 0;
        }
        .stat-box {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
        }
        .stat-box h3 { font-size: 2em; margin-bottom: 10px; }
        .stat-box p { opacity: 0.9; }
        .tables-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            margin: 30px 0;
        }
        .table-card {
            background: white;
            border: 2px solid #667eea;
            border-radius: 8px;
            padding: 15px;
            transition: all 0.3s;
        }
        .table-card:hover {
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
            transform: translateY(-5px);
        }
        .table-card h3 {
            background: #667eea;
            color: white;
            padding: 10px;
            margin: -15px -15px 10px -15px;
            border-radius: 6px 6px 0 0;
            font-size: 1.1em;
        }
        .table-card .columns {
            font-size: 0.9em;
            line-height: 1.8;
        }
        .column-item {
            padding: 5px;
            margin: 3px 0;
            background: #f8f9fa;
            border-left: 3px solid #667eea;
            padding-left: 10px;
            border-radius: 3px;
        }
        .column-pk { border-left-color: #ffc107; background: #fffbf0; }
        .column-fk { border-left-color: #28a745; background: #f0f8f5; }
        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 0.8em;
            margin-left: 5px;
        }
        .badge-pk { background: #ffc107; color: black; }
        .badge-fk { background: #28a745; color: white; }
        .badge-uq { background: #17a2b8; color: white; }
        h2 {
            color: #333;
            margin: 30px 0 20px 0;
            border-bottom: 2px solid #667eea;
            padding-bottom: 10px;
        }
        .info-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            border-left: 4px solid #667eea;
        }
        .info-section ul { margin-left: 20px; }
        .info-section li { margin: 10px 0; }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>📊 Database ER Diagram</h1>
            <p>Database: <strong><?= DB_NAME ?></strong> | Tables: <strong><?= count($tables_info) ?></strong></p>
        </div>

        <!-- Controls -->
        <div class="controls">
            <button onclick="downloadMermaid()">📥 Download Mermaid Code</button>
            <button onclick="printPage()">🖨️ Print</button>
            <button onclick="window.scrollTo(0,0)">⬆️ Top</button>
        </div>

        <!-- Content -->
        <div class="content">
            <!-- Statistics -->
            <div class="stats">
                <div class="stat-box">
                    <h3><?= count($tables_info) ?></h3>
                    <p>Total Tables</p>
                </div>
                <div class="stat-box">
                    <h3><?php echo array_reduce($tables_info, function($c, $t) { return $c + count($t['columns']); }, 0); ?></h3>
                    <p>Total Columns</p>
                </div>
                <div class="stat-box">
                    <h3><?php echo array_reduce($tables_info, function($c, $t) { return $c + count($t['foreign_keys']); }, 0); ?></h3>
                    <p>Foreign Keys</p>
                </div>
                <div class="stat-box">
                    <h3><?= date('Y-m-d H:i') ?></h3>
                    <p>Generated</p>
                </div>
            </div>

            <!-- ER Diagram -->
            <h2>🔗 Entity Relationship Diagram</h2>
            <div class="mermaid">
<?php
// Generate Mermaid ER diagram code
$mermaid_code = "erDiagram\n";

// Add entities
foreach ($tables_info as $table => $info) {
    $mermaid_code .= "    $table {\n";
    foreach ($info['columns'] as $col) {
        $col_name = $col['Field'];
        $col_type = strtoupper(preg_replace('/\(.*\)/', '', $col['Type']));
        $key_info = '';
        
        if ($col['Key'] === 'PRI') {
            $key_info = ' PK';
        } elseif ($col['Key'] === 'MUL') {
            $key_info = ' FK';
        }
        
        $mermaid_code .= "        $col_type $col_name$key_info\n";
    }
    $mermaid_code .= "    }\n";
}

// Add relationships
$processed_relationships = [];
foreach ($tables_info as $table => $info) {
    foreach ($info['foreign_keys'] as $fk) {
        $rel_key = $table . '-' . $fk['REFERENCED_TABLE_NAME'];
        if (!isset($processed_relationships[$rel_key])) {
            $mermaid_code .= "    $table ||--o{ {$fk['REFERENCED_TABLE_NAME']} : \"{$fk['COLUMN_NAME']}\"\n";
            $processed_relationships[$rel_key] = true;
        }
    }
}

echo $mermaid_code;
?>
            </div>

            <!-- Table Details -->
            <h2>📋 Table Details</h2>
            <div class="tables-grid">
<?php foreach ($tables_info as $table => $info): ?>
                <div class="table-card">
                    <h3><?= $table ?></h3>
                    <div class="columns">
<?php foreach ($info['columns'] as $col): ?>
                        <div class="column-item<?= $col['Key'] === 'PRI' ? ' column-pk' : ($col['Key'] === 'MUL' ? ' column-fk' : '') ?>">
                            <strong><?= $col['Field'] ?></strong>
                            <span style="color: #666;">(<?= preg_replace('/\(.*\)/', '', $col['Type']) ?>)</span>
                            <?php if ($col['Key'] === 'PRI'): ?>
                                <span class="badge badge-pk">PK</span>
                            <?php elseif ($col['Key'] === 'MUL'): ?>
                                <span class="badge badge-fk">FK</span>
                            <?php elseif ($col['Key'] === 'UNI'): ?>
                                <span class="badge badge-uq">UQ</span>
                            <?php endif; ?>
                        </div>
<?php endforeach; ?>
                    </div>
<?php if (!empty($info['foreign_keys'])): ?>
                    <div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #ddd;">
                        <strong style="color: #28a745;">References:</strong>
<?php foreach ($info['foreign_keys'] as $fk): ?>
                            <div style="font-size: 0.9em; margin-top: 5px;">
                                🔗 <?= $fk['REFERENCED_TABLE_NAME'] ?> (<?= $fk['REFERENCED_COLUMN_NAME'] ?>)
                            </div>
<?php endforeach; ?>
                    </div>
<?php endif; ?>
                </div>
<?php endforeach; ?>
            </div>

            <!-- Key Relationships -->
            <h2>🔑 Key Relationships</h2>
            <div class="info-section">
                <ul>
<?php
$relationships = [];
foreach ($tables_info as $table => $info) {
    foreach ($info['foreign_keys'] as $fk) {
        $relationships[] = "
                    <li><strong>{$table}.{$fk['COLUMN_NAME']}</strong> → <strong>{$fk['REFERENCED_TABLE_NAME']}.{$fk['REFERENCED_COLUMN_NAME']}</strong></li>";
    }
}
echo implode('', $relationships);
?>
                </ul>
            </div>

            <!-- Legend -->
            <h2>📖 Legend</h2>
            <div class="info-section">
                <ul>
                    <li><span class="badge badge-pk">PK</span> <strong>Primary Key</strong> - Unique identifier for each row</li>
                    <li><span class="badge badge-fk">FK</span> <strong>Foreign Key</strong> - Reference to another table's primary key</li>
                    <li><span class="badge badge-uq">UQ</span> <strong>Unique</strong> - All values must be unique (but can be NULL)</li>
                </ul>
            </div>
        </div>
    </div>

    <script>
        mermaid.initialize({ startOnLoad: true, theme: 'default', securityLevel: 'loose' });
        mermaid.contentLoaded();

        function downloadMermaid() {
            const code = document.querySelector('.mermaid').textContent;
            const element = document.createElement('a');
            element.setAttribute('href', 'data:text/plain;charset=utf-8,' + encodeURIComponent(code));
            element.setAttribute('download', 'ER_Diagram_' + new Date().getTime() + '.txt');
            element.style.display = 'none';
            document.body.appendChild(element);
            element.click();
            document.body.removeChild(element);
        }

        function printPage() {
            window.print();
        }
    </script>
</body>
</html>
