<?php

use Asciisd\Kashier\Facades\Kashier;

it('can process a payment', function () {
    $response = Kashier::processPayment();

    expect($response)->toBe('Processing Kashier payment!');
});

it('has the testing environment set', function () {
    expect(config('kashier.mode'))->toBe('test');
});

it('has the testing mid set', function () {
    expect(config('kashier.mid'))->toBe('MID-3552-454');
});

it('can generate a payment URL', function () {
    $url = Kashier::buildPaymentUrl(100, '12345', 'EGP', 'customer-001');

    expect($url)->toContain('checkout.kashier.io');
});
