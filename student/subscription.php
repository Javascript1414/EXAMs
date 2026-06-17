<?php
/**
 * Payment Gateway Integration
 * Stripe & PayPal integration for premium features
 */

require_once __DIR__ . '/../includes/functions.php';
requireRole('student');

$page_title = 'Subscription Plans';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

// Stripe public key
$stripe_public_key = getenv('STRIPE_PUBLIC_KEY') ?: 'pk_test_51234567890abcdefg';
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="h3 mb-2">
                <i data-lucide="credit-card" class="me-2"></i>
                Premium Subscription Plans
            </h1>
            <p class="text-muted">Upgrade to unlock advanced features and premium content</p>
        </div>
    </div>

    <!-- Toggle Annual/Monthly -->
    <div class="row mb-4 justify-content-center">
        <div class="col-auto">
            <div class="btn-group" role="group">
                <input type="radio" class="btn-check" name="billing" id="monthly" value="monthly" checked onchange="updatePricing()">
                <label class="btn btn-outline-primary" for="monthly">Monthly</label>
                
                <input type="radio" class="btn-check" name="billing" id="annual" value="annual" onchange="updatePricing()">
                <label class="btn btn-outline-primary" for="annual">Annual (Save 20%)</label>
            </div>
        </div>
    </div>

    <!-- Pricing Cards -->
    <div class="row mb-4">
        <!-- Free Plan -->
        <div class="col-md-4 mb-4">
            <div class="card border-0 shadow-sm pricing-card">
                <div class="card-header bg-transparent border-bottom py-4 text-center">
                    <h5 class="card-title mb-2">Free</h5>
                    <div class="price mb-0">
                        <span class="amount">$0</span>
                        <span class="period">/month</span>
                    </div>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled">
                        <li class="mb-3">
                            <i data-lucide="check-circle" style="width: 20px; color: #43b581;"></i>
                            Limited exams (5/month)
                        </li>
                        <li class="mb-3">
                            <i data-lucide="check-circle" style="width: 20px; color: #43b581;"></i>
                            Basic materials
                        </li>
                        <li class="mb-3">
                            <i data-lucide="x-circle" style="width: 20px; color: #999;"></i>
                            No premium support
                        </li>
                        <li class="mb-3">
                            <i data-lucide="x-circle" style="width: 20px; color: #999;"></i>
                            AI recommendations
                        </li>
                        <li class="mb-3">
                            <i data-lucide="x-circle" style="width: 20px; color: #999;"></i>
                            Video streaming
                        </li>
                    </ul>
                </div>
                <div class="card-footer bg-transparent border-top p-4">
                    <button class="btn btn-outline-secondary w-100" disabled>Current Plan</button>
                </div>
            </div>
        </div>

        <!-- Pro Plan -->
        <div class="col-md-4 mb-4">
            <div class="card border-0 shadow-sm pricing-card pricing-card-featured">
                <div class="badge bg-success position-absolute" style="top: 10px; right: 10px;">POPULAR</div>
                <div class="card-header bg-primary text-white border-bottom py-4 text-center">
                    <h5 class="card-title mb-2">Pro</h5>
                    <div class="price mb-0">
                        <span class="amount text-white" id="pro-price">$99</span>
                        <span class="period text-white">/month</span>
                    </div>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled">
                        <li class="mb-3">
                            <i data-lucide="check-circle" style="width: 20px; color: #43b581;"></i>
                            Unlimited exams
                        </li>
                        <li class="mb-3">
                            <i data-lucide="check-circle" style="width: 20px; color: #43b581;"></i>
                            All study materials
                        </li>
                        <li class="mb-3">
                            <i data-lucide="check-circle" style="width: 20px; color: #43b581;"></i>
                            Email support
                        </li>
                        <li class="mb-3">
                            <i data-lucide="check-circle" style="width: 20px; color: #43b581;"></i>
                            AI recommendations
                        </li>
                        <li class="mb-3">
                            <i data-lucide="x-circle" style="width: 20px; color: #999;"></i>
                            Premium video streaming
                        </li>
                    </ul>
                </div>
                <div class="card-footer bg-transparent border-top p-4">
                    <button class="btn btn-primary w-100" onclick="checkout('pro')">Subscribe Now</button>
                </div>
            </div>
        </div>

        <!-- Enterprise Plan -->
        <div class="col-md-4 mb-4">
            <div class="card border-0 shadow-sm pricing-card">
                <div class="card-header bg-transparent border-bottom py-4 text-center">
                    <h5 class="card-title mb-2">Enterprise</h5>
                    <div class="price mb-0">
                        <span class="amount" id="enterprise-price">$299</span>
                        <span class="period">/month</span>
                    </div>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled">
                        <li class="mb-3">
                            <i data-lucide="check-circle" style="width: 20px; color: #43b581;"></i>
                            Everything in Pro
                        </li>
                        <li class="mb-3">
                            <i data-lucide="check-circle" style="width: 20px; color: #43b581;"></i>
                            24/7 Premium support
                        </li>
                        <li class="mb-3">
                            <i data-lucide="check-circle" style="width: 20px; color: #43b581;"></i>
                            Video streaming
                        </li>
                        <li class="mb-3">
                            <i data-lucide="check-circle" style="width: 20px; color: #43b581;"></i>
                            Custom learning path
                        </li>
                        <li class="mb-3">
                            <i data-lucide="check-circle" style="width: 20px; color: #43b581;"></i>
                            Certificates & credits
                        </li>
                    </ul>
                </div>
                <div class="card-footer bg-transparent border-top p-4">
                    <button class="btn btn-outline-primary w-100" onclick="checkout('enterprise')">Subscribe Now</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Billing History -->
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0">Billing History</h5>
                </div>
                <div class="card-body table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Plan</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Invoice</th>
                            </tr>
                        </thead>
                        <tbody id="billingHistory">
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">No billing history yet</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Stripe Script -->
<script src="https://js.stripe.com/v3/"></script>

<style>
    .pricing-card {
        transition: all 0.3s ease;
        border-radius: 12px;
    }
    
    .pricing-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15) !important;
    }
    
    .pricing-card-featured {
        border: 2px solid #5865f2;
    }
    
    .price {
        display: flex;
        align-items: baseline;
        justify-content: center;
        gap: 5px;
    }
    
    .price .amount {
        font-size: 32px;
        font-weight: bold;
        color: #5865f2;
    }
    
    .price .period {
        color: #999;
        font-size: 14px;
    }
    
    body[data-theme="dark"] .card {
        background-color: #252a33;
    }
</style>

<script>
    function updatePricing() {
        const isAnnual = document.getElementById('annual').checked;
        document.getElementById('pro-price').textContent = isAnnual ? '$79' : '$99';
        document.getElementById('enterprise-price').textContent = isAnnual ? '$239' : '$299';
    }
    
    function checkout(plan) {
        // In production, this would redirect to Stripe checkout
        window.location.href = `/api/payment/create_checkout.php?plan=${plan}`;
    }
    
    // Load billing history
    document.addEventListener('DOMContentLoaded', function() {
        loadBillingHistory();
    });
    
    function loadBillingHistory() {
        fetch('/api/payment/get_billing_history.php')
            .then(r => r.json())
            .then(data => {
                if (data.success && data.history.length > 0) {
                    const tbody = document.getElementById('billingHistory');
                    tbody.innerHTML = '';
                    data.history.forEach(item => {
                        tbody.innerHTML += `
                            <tr>
                                <td>${new Date(item.created_at).toLocaleDateString()}</td>
                                <td><strong>${item.plan}</strong></td>
                                <td>$${item.amount}</td>
                                <td><span class="badge bg-success">${item.status}</span></td>
                                <td><a href="/api/payment/get_invoice.php?id=${item.id}" target="_blank">Download</a></td>
                            </tr>
                        `;
                    });
                }
            });
    }
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
