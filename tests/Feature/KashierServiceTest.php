<?php

use Asciisd\Kashier\Facades\Kashier;

it('can process a payment', function () {
    $response = Kashier::processPayment();

    expect($response)->toBe('Processing Kashier payment!');
});

it('can generate a payment URL', function () {
    $order = [
        'orderId' => '12345',
        'amount' => '100.00',
        'currency' => 'EGP',
        'customerReference' => 'customer-001',
    ];

    $url = Kashier::buildPaymentUrl($order);

    expect($url)->toContain('checkout.kashier.io');
});
