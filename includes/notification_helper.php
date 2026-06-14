<?php
/**
 * Notification Dispatch Helper
 */

function sendNotification($pdo, $title, $message, $type, $target_type, $target_id = null, $action_url = null, $icon = 'bell', $created_by = null) {
    // 1. Insert the master notification payload
    $stmt = $pdo->prepare("INSERT INTO notifications (title, message, notification_type, target_type, target_id, action_url, icon, created_by, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'sent')");
    $stmt->execute([$title, $message, $type, $target_type, $target_id, $action_url, $icon, $created_by]);
    $notif_id = $pdo->lastInsertId();

    // 2. Distribute to recipients efficiently via INSERT ... SELECT
    if ($target_type === 'all') {
        $pdo->prepare("INSERT INTO notification_recipients (notification_id, user_id) SELECT ?, id FROM users WHERE status = 'active'")->execute([$notif_id]);
    } 
    elseif ($target_type === 'role') {
        $pdo->prepare("INSERT INTO notification_recipients (notification_id, user_id) SELECT ?, id FROM users WHERE role_id = ? AND status = 'active'")->execute([$notif_id, $target_id]);
    } 
    elseif ($target_type === 'trade') {
        $pdo->prepare("INSERT INTO notification_recipients (notification_id, user_id) SELECT ?, id FROM users WHERE trade_id = ? AND status = 'active'")->execute([$notif_id, $target_id]);
    } 
    elseif ($target_type === 'subject') {
        // Target users enrolled in the trade associated with the subject
        $pdo->prepare("
            INSERT INTO notification_recipients (notification_id, user_id) 
            SELECT ?, id FROM users 
            WHERE trade_id = (SELECT trade_id FROM subjects WHERE id = ?) AND status = 'active'
        ")->execute([$notif_id, $target_id]);
    } 
    elseif ($target_type === 'user') {
        $pdo->prepare("INSERT INTO notification_recipients (notification_id, user_id) VALUES (?, ?)")->execute([$notif_id, $target_id]);
    }
    
    return $notif_id;
}
?>