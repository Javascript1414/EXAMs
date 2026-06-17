<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';

echo "=== DATABASE PROFILE STORAGE TEST ===\n\n";

try {
    // Check user_profiles table
    $result = $pdo->query('SELECT COUNT(*) as count FROM user_profiles')->fetch();
    echo "✓ Total Profile Records: " . $result['count'] . "\n\n";

    // Check users with phone
    $result = $pdo->query('SELECT COUNT(*) as count FROM users WHERE phone IS NOT NULL')->fetch();
    echo "✓ Users with Phone: " . $result['count'] . "\n\n";

    // Check approval status
    $result = $pdo->query('SELECT approval_status, COUNT(*) as count FROM users GROUP BY approval_status')->fetchAll();
    echo "✓ Approval Status Breakdown:\n";
    foreach ($result as $row) {
        echo "  - " . ucfirst($row['approval_status']) . ": " . $row['count'] . "\n";
    }
    echo "\n";

    // Check profile completeness
    $result = $pdo->query('
        SELECT 
            (SELECT COUNT(*) FROM user_profiles WHERE bio IS NOT NULL) as with_bio,
            (SELECT COUNT(*) FROM user_profiles WHERE profile_photo_path IS NOT NULL) as with_photo,
            (SELECT COUNT(*) FROM user_profiles WHERE skills IS NOT NULL) as with_skills
    ')->fetch();
    
    echo "✓ Profile Completeness:\n";
    echo "  - With Bio: " . $result['with_bio'] . "\n";
    echo "  - With Photo: " . $result['with_photo'] . "\n";
    echo "  - With Skills: " . $result['with_skills'] . "\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>
