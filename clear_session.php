<?php
/**
 * Clear session messages
 */
session_start();
unset($_SESSION['practical_message']);
unset($_SESSION['practical_message_type']);
echo "Session cleared! <a href='/EXAMs/student/practical_exams.php'>Go back to Practical Exams</a>";
?>
