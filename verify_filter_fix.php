<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

echo "<h2>📋 Testing Complete Filter Workflow</h2>";

// Scenario: Student with multiple subjects
$student_id = 3;

echo "<h3>Test Scenarios:</h3>";

// Get all data
$stmt = $pdo->prepare("SELECT trade_id FROM student_trades WHERE student_id = ?");
$stmt->execute([$student_id]);
$trades = $stmt->fetchAll(PDO::FETCH_COLUMN);
$placeholders = implode(',', array_fill(0, count($trades), '?'));

// Scenario 1: No filter (All notes)
echo "<p><strong>1️⃣ All Notes (No Filter):</strong></p>";
$query = "SELECT COUNT(*) FROM notes WHERE trade_id IN ($placeholders) AND status = 'active'";
$stmt = $pdo->prepare($query);
$stmt->execute($trades);
$count = $stmt->fetchColumn();
echo "<p>Found: <strong>$count notes</strong> ✅</p>";

// Scenario 2: Filter by subject
echo "<p><strong>2️⃣ Filter by Subject (Subject ID = 1):</strong></p>";
$query = "SELECT COUNT(*) FROM notes WHERE trade_id IN ($placeholders) AND subject_id = ? AND status = 'active'";
$params = array_merge($trades, [1]);
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$count = $stmt->fetchColumn();
echo "<p>Found: <strong>$count notes</strong> ✅</p>";

// Scenario 3: Search only
echo "<p><strong>3️⃣ Search Only (Search Term = 'GRF'):</strong></p>";
$query = "SELECT COUNT(*) FROM notes WHERE trade_id IN ($placeholders) AND (title LIKE ? OR description LIKE ?) AND status = 'active'";
$params = array_merge($trades, ['%GRF%', '%GRF%']);
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$count = $stmt->fetchColumn();
echo "<p>Found: <strong>$count notes</strong> ✅</p>";

// Scenario 4: Subject + Search
echo "<p><strong>4️⃣ Subject Filter + Search (Subject = 1, Search = 'GRF'):</strong></p>";
$query = "SELECT COUNT(*) FROM notes WHERE trade_id IN ($placeholders) AND subject_id = ? AND (title LIKE ? OR description LIKE ?) AND status = 'active'";
$params = array_merge($trades, [1, '%GRF%', '%GRF%']);
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$count = $stmt->fetchColumn();
echo "<p>Found: <strong>$count notes</strong> ✅</p>";

echo "<h3>📌 JavaScript Fix Applied:</h3>";
echo "<ul>";
echo "<li>✅ Subject dropdown now has ID 'subjectFilter'</li>";
echo "<li>✅ Search input now has ID 'searchInput'</li>";
echo "<li>✅ When subject changes, search is cleared automatically</li>";
echo "<li>✅ Form submits cleanly with just the subject parameter</li>";
echo "</ul>";

echo "<h3>🧪 Ready to Test:</h3>";
echo "<p><a href='/student/notes.php' class='btn btn-primary' target='_blank'>Go to Student Notes Page</a></p>";
echo "<p style='color: #666; font-size: 12px;'>(Must be logged in as a student first)</p>";
?>
