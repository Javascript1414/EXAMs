<?php
require_once __DIR__ . '/includes/db.php';

echo "<h2>🚀 Populating All Trades with Students, Teachers & Notes</h2>";

$trades_data = [
    ['id' => 2, 'name' => 'CSA', 'subjects' => ['Data Structures', 'Algorithms', 'Database']],
    ['id' => 5, 'name' => 'RODA', 'subjects' => ['Mechanical Design', 'CAD', 'Manufacturing']],
    ['id' => 6, 'name' => 'DMM', 'subjects' => ['Digital Marketing', 'Social Media', 'Analytics']]
];

// Step 1: Create subjects for each trade
echo "<h3>Step 1: Creating subjects for each trade...</h3>";
foreach ($trades_data as $trade) {
    foreach ($trade['subjects'] as $subject) {
        $stmt = $pdo->prepare("INSERT IGNORE INTO subjects (trade_id, subject_name) VALUES (?, ?)");
        $stmt->execute([$trade['id'], $subject]);
    }
    echo "✓ " . $trade['name'] . " subjects created<br>";
}

// Step 2: Create teachers/admins for each trade
echo "<h3>Step 2: Creating teachers for each trade...</h3>";
$teacher_data = [
    ['trade' => 2, 'name' => 'Prof. CSA Teacher', 'email' => 'csa_teacher@examlms.com'],
    ['trade' => 5, 'name' => 'Prof. RODA Teacher', 'email' => 'roda_teacher@examlms.com'],
    ['trade' => 6, 'name' => 'Prof. DMM Teacher', 'email' => 'dmm_teacher@examlms.com']
];

$teacher_ids = [];
foreach ($teacher_data as $teacher) {
    // Check if teacher exists
    $check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $check->execute([$teacher['email']]);
    $existing = $check->fetch();
    
    if ($existing) {
        $teacher_ids[$teacher['trade']] = $existing['id'];
        echo "✓ " . $teacher['name'] . " already exists<br>";
    } else {
        // Create teacher
        $password = password_hash('teacher123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("
            INSERT INTO users (full_name, email, password, trade_id, role_id, status, email_verified) 
            VALUES (?, ?, ?, ?, 2, 'active', 1)
        ");
        $stmt->execute([$teacher['name'], $teacher['email'], $password, $teacher['trade']]);
        $teacher_ids[$teacher['trade']] = $pdo->lastInsertId();
        echo "✓ " . $teacher['name'] . " created (ID: " . $teacher_ids[$teacher['trade']] . ")<br>";
    }
}

// Step 3: Create students for each trade
echo "<h3>Step 3: Creating students for each trade...</h3>";
$trades_names = [2 => 'CSA', 5 => 'RODA', 6 => 'DMM'];

foreach ([2, 5, 6] as $trade_id) {
    for ($i = 1; $i <= 3; $i++) {
        $name = $trades_names[$trade_id] . " Student " . $i;
        $email = "student_" . strtolower($trades_names[$trade_id]) . "_" . $i . "@examlms.com";
        
        // Check if student exists
        $check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $check->execute([$email]);
        $existing = $check->fetch();
        
        if (!$existing) {
            $password = password_hash('student123', PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("
                INSERT INTO users (full_name, email, password, trade_id, role_id, status, email_verified) 
                VALUES (?, ?, ?, ?, 3, 'active', 1)
            ");
            $stmt->execute([$name, $email, $password, $trade_id]);
            echo "✓ $name created<br>";
        } else {
            echo "✓ $name already exists<br>";
        }
    }
}

// Step 4: Create sample notes for each trade
echo "<h3>Step 4: Creating sample notes for each trade...</h3>";
$notes_data = [
    2 => [
        ['subject' => 'Data Structures', 'title' => 'Arrays and Linked Lists', 'description' => 'Complete guide to arrays and linked lists implementation'],
        ['subject' => 'Algorithms', 'title' => 'Sorting Algorithms', 'description' => 'Understanding different sorting techniques']
    ],
    5 => [
        ['subject' => 'Mechanical Design', 'title' => 'Design Principles', 'description' => 'Basic principles of mechanical design'],
        ['subject' => 'CAD', 'title' => 'AutoCAD Basics', 'description' => 'Introduction to CAD software']
    ],
    6 => [
        ['subject' => 'Digital Marketing', 'title' => 'SEO Fundamentals', 'description' => 'Search Engine Optimization basics'],
        ['subject' => 'Analytics', 'title' => 'Google Analytics Guide', 'description' => 'Using Google Analytics for business insights']
    ]
];

foreach ($notes_data as $trade_id => $notes) {
    foreach ($notes as $note) {
        // Get subject ID
        $subject_stmt = $pdo->prepare("SELECT id FROM subjects WHERE trade_id = ? AND subject_name = ?");
        $subject_stmt->execute([$trade_id, $note['subject']]);
        $subject = $subject_stmt->fetch();
        
        if ($subject) {
            // Check if note exists
            $check = $pdo->prepare("SELECT id FROM notes WHERE trade_id = ? AND title = ?");
            $check->execute([$trade_id, $note['title']]);
            $existing = $check->fetch();
            
            if (!$existing) {
                $stmt = $pdo->prepare("
                    INSERT INTO notes (trade_id, subject_id, title, description, file_path, uploaded_by, status) 
                    VALUES (?, ?, ?, ?, ?, ?, 'active')
                ");
                $teacher_id = $teacher_ids[$trade_id];
                $file_path = 'uploads/notes/sample_' . time() . '.pdf';
                $stmt->execute([$trade_id, $subject['id'], $note['title'], $note['description'], $file_path, $teacher_id]);
                echo "✓ Note: " . $note['title'] . " (" . $trades_names[$trade_id] . ") created<br>";
            }
        }
    }
}

// Final summary
echo "<h3>✅ Setup Complete!</h3>";
$result = $pdo->query("
    SELECT t.id, t.trade_name, COUNT(DISTINCT u.id) as students, COUNT(DISTINCT n.id) as notes
    FROM trades t
    LEFT JOIN users u ON t.id = u.trade_id
    LEFT JOIN notes n ON t.id = n.trade_id
    GROUP BY t.id, t.trade_name
");
$summary = $result->fetchAll();

echo "<table border='1' cellpadding='10' style='border-collapse: collapse; width: 100%;'>";
echo "<tr style='background: #4CAF50; color: white;'><th>Trade</th><th>Students</th><th>Notes</th></tr>";
foreach ($summary as $row) {
    echo "<tr><td><strong>" . $row['trade_name'] . "</strong></td><td>" . $row['students'] . "</td><td>" . $row['notes'] . "</td></tr>";
}
echo "</table>";

echo "<p><strong>Default credentials:</strong></p>";
echo "<ul>";
echo "<li>Teachers: Password = <code>teacher123</code></li>";
echo "<li>Students: Password = <code>student123</code></li>";
echo "</ul>";
?>
