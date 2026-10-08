<?php
/**
 * Gloy Skincare - Multi-Gateway Payment Configuration
 * Supports: Cash on Delivery (COD), Credit/Debit Card (Stripe), JazzCash, Easypaisa, PayFast
 */

return [
    // Current active default gateway
    'default_gateway' => 'COD',

    // 1. Cash on Delivery (COD) - Standard Pakistan eCommerce
    'cod' => [
        'enabled' => true,
        'name' => 'Cash on Delivery (COD)',
        'description' => 'Pay in cash when your Gloy parcel arrives safely at your doorstep.',
        'icon' => 'bi-cash-stack',
        'badge' => 'No Extra Fee',
        'fee' => 0,
        'currency' => 'PKR',
        'instructions' => 'Please keep the exact amount ready upon delivery for a smooth handover.'
    ],

    // 2. Stripe Payment Gateway (Disabled)
    'stripe' => [
        'enabled' => false,
        'sandbox_mode' => true,
        'name' => 'Credit / Debit Card (Stripe Gateway)',
        'description' => 'Pay securely via Visa, MasterCard, UnionPay, or Amex with 256-bit SSL encryption.',
        'icon' => 'bi-credit-card-2-front',
        'badge' => 'Instant & Secure',
        'public_key' => 'pk_test_51MockGloySkincareTestKeyPublic99281',
        'secret_key' => 'sk_test_51MockGloySkincareTestKeySecret88172',
        'webhook_secret' => 'whsec_test_mock_gloy',
        'fee_percentage' => 0,
        'fee_fixed' => 0,
        'currency' => 'PKR'
    ],

    // 3. JazzCash (Disabled)
    'jazzcash' => [
        'enabled' => false,
        'sandbox_mode' => true,
        'name' => 'JazzCash Mobile Account',
        'account_number' => '03132775608',
        'account_title' => 'Gloy Skincare Official',
        'description' => 'Pay via JazzCash to merchant account 03132775608.',
        'icon' => 'bi-phone',
        'badge' => 'Instant Mobile Pay',
        'merchant_id' => '03132775608',
        'password' => 'mock_pass_jc_98',
        'integrity_salt' => 'mock_salt_jc_9921',
        'api_url' => 'https://sandbox.jazzcash.com.pk/ApplicationAPI/API/2.0/Purchase/DoMWalletTransaction',
        'fee_percentage' => 0,
        'fee_fixed' => 0,
        'currency' => 'PKR',
        'instructions' => 'Transfer order total to JazzCash Account: 03132775608 (Gloy Skincare Official).'
    ],

    // 4. Easypaisa Mobile Account Gateway (Active)
    'easypaisa' => [
        'enabled' => true,
        'sandbox_mode' => true,
        'name' => 'Easypaisa Mobile Account',
        'account_number' => '03132775608',
        'account_title' => 'Iqra Sohail',
        'description' => 'Pay via Easypaisa to account 03132775608 (Iqra Sohail).',
        'icon' => 'bi-wallet2',
        'badge' => 'Fast Checkout',
        'store_id' => '03132775608',
        'api_key' => 'ep_sec_mock_gloy_22',
        'api_url' => 'https://easypaystg.easypaisa.com.pk/easypay/Index.jsf',
        'fee_percentage' => 0,
        'fee_fixed' => 0,
        'currency' => 'PKR',
        'instructions' => 'Transfer order total to Easypaisa Account: 03132775608 (Iqra Sohail).'
    ],

    // General Settings
    'settings' => [
        'currency' => 'PKR',
        'currency_symbol' => 'Rs.',
        'test_mode' => true,
        'success_url' => 'order_confirmation.php',
        'cancel_url' => 'checkout.php'
    ]
];
