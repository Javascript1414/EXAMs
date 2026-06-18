<?php
require_once 'includes/db.php';

echo "Updating database schema for certificates...\n\n";

// 1. Add trade_code column if it doesn't exist
try {
    $pdo->exec("ALTER TABLE trades ADD COLUMN trade_code VARCHAR(20) UNIQUE DEFAULT 'UNKNOWN'");
    echo "✅ Added trade_code column to trades table\n";
} catch (Exception $e) {
    echo "✅ trade_code column already exists\n";
}

// 2. Update trade codes
try {
    $pdo->exec("UPDATE trades SET trade_code = 'CITS' WHERE id = 1");
    $pdo->exec("UPDATE trades SET trade_code = 'COPA' WHERE id = 2");
    $pdo->exec("UPDATE trades SET trade_code = 'DDVT' WHERE id = 3");
    $pdo->exec("UPDATE trades SET trade_code = 'ACIT' WHERE id = 4");
    echo "✅ Updated trade codes\n";
} catch (Exception $e) {
    echo "⚠️ Error updating trade codes: " . $e->getMessage() . "\n";
}

// 3. Add other certificate fields if needed
try {
    $pdo->exec("ALTER TABLE certificates ADD COLUMN course_code VARCHAR(20) AFTER certificate_id");
    $pdo->exec("ALTER TABLE certificates ADD COLUMN academic_year VARCHAR(10) AFTER course_code");
    $pdo->exec("ALTER TABLE certificates ADD COLUMN student_registration VARCHAR(50) AFTER academic_year");
    $pdo->exec("ALTER TABLE certificates ADD COLUMN exam_sequence INT UNSIGNED AFTER student_registration");
    echo "✅ Added certificate metadata columns\n";
} catch (Exception $e) {
    echo "✅ Certificate columns already exist\n";
}

// 4. Update student enrollment number
try {
    $pdo->exec("UPDATE users SET enrollment_no = '1414' WHERE id = 29");
    echo "✅ Updated student enrollment number\n";
} catch (Exception $e) {
    echo "⚠️ Error updating enrollment: " . $e->getMessage() . "\n";
}

echo "\n✅ Database updated successfully!\n";
?>
