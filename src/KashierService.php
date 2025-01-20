<?php

namespace Asciisd\Kashier;

class KashierService
{
    public function processPayment(): string
    {
        return "Processing Kashier payment!";
    }

    public function generateOrderHash(array $order): string
    {
        $merchantId = config('kashier.merchant_id');
        $apiKey = config('kashier.api_key');

        $path = "/?payment=".$merchantId.".".
            $order['orderId'].".".
            $order['amount'].".".
            $order['currency'];

        if (! empty($order['customerReference'])) {
            $path .= ".".$order['customerReference'];
        }

        return hash_hmac('sha256', $path, $apiKey, false);
    }

    public function buildPaymentUrl(array $order): string
    {
        $hash = $this->generateOrderHash($order);

        return "https://checkout.kashier.io/?merchantId=".config('kashier.merchant_id').
            "&orderId={$order['orderId']}".
            "&amount={$order['amount']}".
            "&currency={$order['currency']}".
            "&hash={$hash}".
            "&mode=".config('kashier.mode');
    }
}
