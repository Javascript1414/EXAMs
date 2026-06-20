<?php
/**
 * Maintenance Mode Configuration & Management
 * Controls when system is in maintenance vs production
 */

return [
    'maintenance_mode' => false,  // Set to TRUE when updating | FALSE when live
    'maintenance_message' => 'System Maintenance in Progress',
    'maintenance_details' => 'We are currently updating the system with new features. Please try again in a few moments.',
    'maintenance_estimated_time' => '5-10 minutes',
    'show_countdown' => true,
    'show_admin_panel' => true,  // Allow admins to access even during maintenance
    'allowed_ips' => [  // IPs allowed during maintenance
        '127.0.0.1',
        'localhost',
        // Add your admin IP if static
    ],
    'last_maintenance' => null,
    'next_scheduled_maintenance' => null,
];
?>
