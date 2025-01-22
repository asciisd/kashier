<?php

namespace Asciisd\Kashier;

class KashierService
{
    public string $allowedMethods = "card,wallet,bank_installments";

    public function processPayment(): string
    {
        return "Processing Kashier payment!";
    }

    public function generateOrderHash($amount, $orderId, $currency): string
    {
        $mid = config('kashier.mid');
        $secret = config('kashier.apikey');

        $path = "/?payment=".$mid.".".$orderId.".".$amount.".".$currency;
        return hash_hmac('sha256', $path, $secret);
    }

    public function buildPaymentUrl($amount, $orderId, $attributes = []): string
    {
        $currency = config('kashier.currency');
        $hash = $this->generateOrderHash($amount, $orderId, $currency);

        $callbackUrl = url(path: '/kashier/response', secure: true);
        $webhookUrl = url(path: '/kashier/webhook', secure: true);

        $query = http_build_query(array_merge([
            'merchantId' => config('kashier.mid'),
            'orderId' => $orderId,
            'amount' => $amount,
            'currency' => $currency,
            'mode' => config('kashier.mode'),
            'hash' => $hash,
            'merchantRedirect' => $callbackUrl,
            'serverWebhook' => $webhookUrl,
            'allowedMethods' => $this->allowedMethods,
            'display' => 'en',
            'redirectMethod' => 'post'
        ], $attributes));

        return "https://checkout.kashier.io/?$query";
    }
}
