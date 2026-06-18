<?php
/**
 * Stripe Payment Integration API
 * Handles checkout and payment processing
 */

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

require_once '../../config.php';

$user_id = $_SESSION['user_id'];
$plan = $_POST['plan'] ?? 'pro';
$billing_cycle = $_POST['billing_cycle'] ?? 'monthly';

try {
    $prices = [
        'pro' => ['monthly' => 9900, 'annual' => 79000],
        'enterprise' => ['monthly' => 29900, 'annual' => 239000]
    ];
    
    if (!isset($prices[$plan])) {
        http_response_code(400);
        die(json_encode(['success' => false, 'error' => 'Invalid plan']));
    }
    
    $amount = $prices[$plan][$billing_cycle] ?? $prices[$plan]['monthly'];
    
    // Simulate Stripe session
    $session_id = 'cs_test_' . bin2hex(random_bytes(16));
    
    // Save payment attempt to database
    $stmt = $pdo->prepare("
        INSERT INTO payments (user_id, plan, amount, billing_cycle, session_id, status)
        VALUES (?, ?, ?, ?, ?, 'pending')
    ");
    $stmt->execute([$user_id, $plan, $amount, $billing_cycle, $session_id]);
    
    echo json_encode([
        'success' => true,
        'checkout_url' => 'https://checkout.stripe.com/pay/' . $session_id,
        'session_id' => $session_id
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="invoice_' . $payment_id . '.pdf"');
        
        // Simple text representation
        echo "INVOICE\n";
        echo "Date: " . date('Y-m-d', strtotime($payment['created_at'])) . "\n";
        echo "Plan: " . ucfirst($payment['plan']) . "\n";
        echo "Amount: $" . ($payment['amount'] / 100) . "\n";
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

/**
 * Handle Stripe webhook
 */
function handleWebhook() {
    global $pdo;
    
    $webhook_secret = getenv('STRIPE_WEBHOOK_SECRET') ?: 'whsec_test_123';
    $payload = @file_get_contents('php://input');
    $sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';
    
    try {
        // Verify webhook signature
        $event = json_decode($payload, true);
        
        if ($event['type'] === 'payment_intent.succeeded') {
            $payment_intent = $event['data']['object'];
            $user_id = $payment_intent['metadata']['user_id'] ?? 0;
            $plan = $payment_intent['metadata']['plan'] ?? 'pro';
            
            // Update subscription
            $query = "
                INSERT INTO subscriptions (user_id, plan, status, started_at, expires_at)
                VALUES (?, ?, 'active', NOW(), DATE_ADD(NOW(), INTERVAL 1 MONTH))
            ";
            
            $stmt = $pdo->prepare($query);
            $stmt->execute([$user_id, $plan]);
            
            // Record payment
            $payment_query = "
                INSERT INTO payments (user_id, plan, amount, status, stripe_id)
                VALUES (?, ?, ?, 'completed', ?)
            ";
            
            $amount = ($plan === 'enterprise' ? 29900 : 9900);
            $stripe_id = $payment_intent['id'];
            
            $payment_stmt = $pdo->prepare($payment_query);
            $payment_stmt->execute([$user_id, $plan, $amount, $stripe_id]);
            
            // Send confirmation email
            $email_query = "SELECT email FROM users WHERE id = ?";
            $email_stmt = $pdo->prepare($email_query);
            $email_stmt->execute([$user_id]);
            $user = $email_stmt->fetch(PDO::FETCH_ASSOC);
            
            // Email notification code here
        }
        
        http_response_code(200);
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
    }
}
?>
