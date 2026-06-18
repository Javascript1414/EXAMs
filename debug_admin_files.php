<?php
require_once __DIR__ . '/includes/db.php';

echo "<h2>🔍 Checking Admin Note Files</h2>";

// Get all notes from database
$notes = $pdo->query("SELECT id, title, file_path, status FROM notes ORDER BY created_at DESC LIMIT 10")->fetchAll();

echo "<h3>Notes in Database:</h3>";
echo "<table border='1' cellpadding='10'>";
echo "<tr style='background: #4CAF50; color: white;'><th>ID</th><th>Title</th><th>File Path (DB)</th><th>Status</th><th>File Exists?</th><th>View Link</th></tr>";

foreach ($notes as $note) {
    $full_path = __DIR__ . '/' . $note['file_path'];
    $file_exists = file_exists($full_path);
    $exists_badge = $file_exists ? '<span style="color: green; font-weight: bold;">✅ YES</span>' : '<span style="color: red; font-weight: bold;">❌ NO</span>';
    
    echo "<tr>";
    echo "<td>" . $note['id'] . "</td>";
    echo "<td>" . htmlspecialchars($note['title']) . "</td>";
    echo "<td><code>" . htmlspecialchars($note['file_path']) . "</code></td>";
    echo "<td>" . $note['status'] . "</td>";
    echo "<td>" . $exists_badge . "</td>";
    echo "<td><a href='/" . htmlspecialchars($note['file_path']) . "' target='_blank'>Test Link</a></td>";
    echo "</tr>";
}
echo "</table>";

echo "<h3>Checking Upload Directory:</h3>";
$upload_dir = __DIR__ . '/uploads/notes/';
if (is_dir($upload_dir)) {
    echo "<p style='color: green;'>✅ Directory exists: " . $upload_dir . "</p>";
    
    $files = glob($upload_dir . '*');
    echo "<p><strong>Files in folder:</strong> " . count($files) . "</p>";
    
    if (count($files) > 0) {
        echo "<ul>";
        foreach ($files as $file) {
            echo "<li>" . basename($file) . " (" . filesize($file) . " bytes)</li>";
        }
        echo "</ul>";
    }
} else {
    echo "<p style='color: red;'>❌ Directory does NOT exist: " . $upload_dir . "</p>";
}

echo "<h3>Direct File Test:</h3>";
echo "<p>Try accessing this directly:</p>";
echo "<ul>";
foreach (array_slice($notes, 0, 3) as $note) {
    echo "<li><a href='/" . htmlspecialchars($note['file_path']) . "' target='_blank'>" . htmlspecialchars($note['title']) . "</a></li>";
}
echo "</ul>";

?>
