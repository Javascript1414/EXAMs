<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/includes/db.php';

// Get existing students
$stmt = $pdo->query("SELECT id, email, full_name FROM users WHERE role_id = 3 LIMIT 5");
$students = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html>
<head>
    <title>Quick Login as Student</title>
    <style>
        body { font-family: Arial; padding: 20px; background: #f5f5f5; }
        .card { background: white; padding: 20px; border-radius: 5px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        a { display: block; padding: 10px; margin: 5px 0; background: #2196F3; color: white; text-decoration: none; border-radius: 5px; }
        a:hover { background: #1976D2; }
    </style>
</head>
<body>
<div class="card">
    <h2>🎓 Select a Student to Login</h2>
    <p>Click on a student to login and view their notes:</p>
    <?php
    if (count($students) > 0) {
        foreach ($students as $student) {
            $link = "?login=" . urlencode($student['email']);
            echo "<a href='$link'>{$student['full_name']} ({$student['email']})</a>";
        }
    } else {
        echo "<p>No students found!</p>";
    }
    ?>
</div>

<?php
// Handle login
if (isset($_GET['login'])) {
    $email = $_GET['login'];
    $stmt = $pdo->prepare("SELECT id, full_name, role_id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if ($user) {
        session_start();
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['email'] = $email;
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['role_id'] = $user['role_id'];
        $_SESSION['role_name'] = 'student';
        
        header("Location: /EXAMs/student/notes.php");
        exit;
    }
}
?>
</body>
</html>
