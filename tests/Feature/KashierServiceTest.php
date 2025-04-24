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
    // Ensure currency is set in the configuration
    config(['kashier.currency' => 'EGP']);
    
    // Mock the URL facade for the test
    \Illuminate\Support\Facades\URL::shouldReceive('to')
        ->with('/kashier/response', true)
        ->andReturn('https://example.com/kashier/response');
        
    \Illuminate\Support\Facades\URL::shouldReceive('to')
        ->with('/kashier/webhook', true)
        ->andReturn('https://example.com/kashier/webhook');
    
    // Update to use array for attributes instead of individual parameters
    $url = Kashier::buildPaymentUrl(100, '12345', [
        'customerReference' => 'customer-001'
    ]);

    expect($url)->toContain('checkout.kashier.io');
});
