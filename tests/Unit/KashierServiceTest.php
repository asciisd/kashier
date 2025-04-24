<?php

namespace Asciisd\Kashier\Tests\Unit;

use Asciisd\Kashier\KashierService;
use Asciisd\Kashier\Tests\TestCase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\URL;
use ReflectionClass;

class KashierServiceTest extends TestCase
{
    protected KashierService $kashierService;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Set up config values for testing
        config(['kashier.mid' => 'test-merchant-id']);
        config(['kashier.secretKey' => 'test-secret-key']);
        config(['kashier.mode' => 'test']);
        config(['kashier.currency' => 'EGP']);
        
        // Mock URL facade
        URL::shouldReceive('to')
            ->with('/kashier/response', true)
            ->andReturn('https://example.com/kashier/response');
            
        URL::shouldReceive('to')
            ->with('/kashier/webhook', true)
            ->andReturn('https://example.com/kashier/webhook');
        
        // Create a fresh instance of KashierService for each test
        $this->kashierService = new KashierService();
    }

    /**
     * Helper method to call private methods for testing
     */
    protected function callPrivateMethod($object, $methodName, array $parameters = [])
    {
        $reflection = new ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);
        
        return $method->invokeArgs($object, $parameters);
    }

    /** @test */
    public function it_detects_test_mode_correctly()
    {
        // Test mode = test
        config(['kashier.mode' => 'test']);
        $result = $this->callPrivateMethod($this->kashierService, 'isTestMode');
        $this->assertTrue($result);
        
        // Test mode = live
        config(['kashier.mode' => 'live']);
        $result = $this->callPrivateMethod($this->kashierService, 'isTestMode');
        $this->assertFalse($result);
    }

    /** @test */
    public function it_creates_correct_hash()
    {
        $amount = 500;
        $orderId = 'test-order-123';
        $currency = 'EGP';
        
        $mid = config('kashier.mid');
        $secret = config('kashier.secretKey');
        
        $path = "/?payment={$mid}.{$orderId}.{$amount}.{$currency}";
        $expectedHash = hash_hmac('sha256', $path, $secret);
        
        $hash = $this->kashierService->generateOrderHash($amount, $orderId, $currency);
        
        $this->assertEquals($expectedHash, $hash);
    }

    /** @test */
    public function it_creates_correct_payment_url()
    {
        // Skip this test if URL facade mocking doesn't work properly
        if (!method_exists(URL::class, 'shouldReceive')) {
            $this->markTestSkipped('URL facade mocking not available');
            return;
        }
        
        $amount = 500;
        $orderId = 'test-order-123';
        $attributes = [
            'customerReference' => 'customer-123'
        ];
        
        $url = $this->kashierService->buildPaymentUrl($amount, $orderId, $attributes);
        
        // Test that the URL contains the expected parameters
        $this->assertStringContainsString('merchantId=test-merchant-id', $url);
        $this->assertStringContainsString('orderId=test-order-123', $url);
        $this->assertStringContainsString('amount=500', $url);
        $this->assertStringContainsString('currency=EGP', $url);
        $this->assertStringContainsString('customerReference=customer-123', $url);
        $this->assertStringContainsString('mode=test', $url);
    }

    /** @test */
    public function it_accepts_valid_order_ids()
    {
        // Valid order IDs
        $this->expectNotToPerformAssertions();
        
        $this->callPrivateMethod($this->kashierService, 'validateOrderId', ['valid-order-123']);
        $this->callPrivateMethod($this->kashierService, 'validateOrderId', ['ORDER_123']);
        $this->callPrivateMethod($this->kashierService, 'validateOrderId', ['order123']);
        $this->callPrivateMethod($this->kashierService, 'validateOrderId', ['123456789']);
    }

    /** @test */
    public function it_rejects_invalid_order_ids()
    {
        // Empty order ID
        $this->expectException(\Exception::class);
        $this->callPrivateMethod($this->kashierService, 'validateOrderId', ['']);
    }

    /** @test */
    public function it_validates_transaction_details_parameters()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Order ID cannot be empty');
        
        $this->kashierService->getTransactionDetails('');
    }
}
