<?php
/**
 * Migration Script: Add trade_id column to courses table
 * This enables trade-based filtering for videos
 */

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Add Trade to Courses</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <style>
            body {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            .card {
                box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
                border: none;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-6">
                    <div class="card p-4">
                        <h3 class="mb-4 fw-bold">🔄 Add Trade to Courses</h3>
                        <p class="text-muted mb-4">This will add <code>trade_id</code> column to the courses table, enabling trade-based video filtering.</p>
                        
                        <form method="POST">
                            <button type="submit" class="btn btn-primary btn-lg w-100">Run Migration</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Check if column already exists
$check = $pdo->query("
    SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_NAME='courses' AND COLUMN_NAME='trade_id'
");

if ($check->fetch()) {
    echo "✅ Column already exists!";
    exit;
}

try {
    // Get default trade ID (usually 1)
    $default_trade = $pdo->query("SELECT id FROM trades LIMIT 1")->fetch();
    $default_trade_id = $default_trade['id'] ?? 1;
    
    // Add trade_id column
    $pdo->exec("
        ALTER TABLE courses 
        ADD COLUMN trade_id INT UNSIGNED DEFAULT $default_trade_id AFTER course_id
    ");
    
    // Add foreign key constraint
    $pdo->exec("
        ALTER TABLE courses 
        ADD CONSTRAINT fk_courses_trade 
        FOREIGN KEY (trade_id) REFERENCES trades(id) ON DELETE CASCADE
    ");
    
    // Add index
    $pdo->exec("
        ALTER TABLE courses 
        ADD INDEX idx_trade_id (trade_id)
    ");
    
    echo "✅ Migration completed successfully! trade_id column added to courses table.";
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage();
}
