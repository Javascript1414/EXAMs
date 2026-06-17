<?php
/**
 * Payment API - Get Billing History
 */
session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    die(json_encode(['success' => false, 'message' => 'Unauthorized']));
}

require_once '../../config.php';

try {
    $user_id = $_SESSION['user_id'];
    
    $history = [
        ['date' => date('Y-m-d', strtotime('-1 month')), 'plan' => 'Pro', 'amount' => '$99.00', 'status' => 'Paid', 'invoice' => '#INV-001'],
        ['date' => date('Y-m-d', strtotime('-2 months')), 'plan' => 'Pro', 'amount' => '$99.00', 'status' => 'Paid', 'invoice' => '#INV-002'],
        ['date' => date('Y-m-d', strtotime('-3 months')), 'plan' => 'Free', 'amount' => '$0.00', 'status' => 'Active', 'invoice' => '#INV-003']
    ];
    
    echo json_encode(['success' => true, 'history' => $history]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
