<?php
/**
 * Fix Missing Teacher Role
 * Checks if teacher role exists, creates it if missing
 */

require_once 'config.php';
require_once 'includes/db.php';

echo "<h2>Fixing Teacher Role...</h2>";

try {
    // First, check the structure of roles table
    echo "<h3>Roles Table Structure:</h3>";
    $schema = $pdo->query("DESCRIBE roles")->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>";
    print_r($schema);
    echo "</pre>";
    
    // Check if teacher role exists
    $check_role = $pdo->prepare("SELECT id, name FROM roles WHERE name = 'teacher' LIMIT 1");
    $check_role->execute();
    $teacher_role = $check_role->fetch(PDO::FETCH_ASSOC);
    
    if ($teacher_role) {
        echo "✅ Teacher role already exists (ID: " . $teacher_role['id'] . ")<br>";
    } else {
        echo "❌ Teacher role NOT found. Creating it now...<br>";
        
        // Insert teacher role (without timestamp columns)
        $insert_role = $pdo->prepare("INSERT INTO roles (name) VALUES (?)");
        $insert_role->execute(['teacher']);
        
        $new_role_id = $pdo->lastInsertId();
        echo "✅ Teacher role created successfully (ID: " . $new_role_id . ")<br>";
    }
    
    // Check what roles exist in database
    echo "<hr>";
    echo "<h3>All Roles in Database:</h3>";
    $all_roles = $pdo->query("SELECT * FROM roles ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
    
    if ($all_roles) {
        echo "<table border='1' cellpadding='10'>";
        echo "<tr>";
        if (!empty($all_roles[0])) {
            foreach (array_keys($all_roles[0]) as $column) {
                echo "<th>" . htmlspecialchars($column) . "</th>";
            }
        }
        echo "</tr>";
        foreach ($all_roles as $role) {
            echo "<tr>";
            foreach ($role as $value) {
                echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "No roles found in database!<br>";
    }
    
    echo "<hr>";
    echo "<h3>✅ All Done! Try accessing the Add Teacher page now:</h3>";
    echo "<a href='admin/add_teacher.php' style='padding: 10px 20px; background: #667eea; color: white; text-decoration: none; border-radius: 5px;'>Go to Add Teacher Page</a>";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>
