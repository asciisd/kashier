<?php

namespace Asciisd\Kashier\Http\Controllers;

use Asciisd\Kashier\Enums\OrderStatus;
use Asciisd\Kashier\Events\KashierResponseHandled;
use Asciisd\Kashier\Http\Middleware\VerifyResponseSignature;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class ResponseController extends Controller
{
    public function __construct()
    {
        $this->middleware(VerifyResponseSignature::class);
    }

    public function __invoke(Request $request)
    {
        KashierResponseHandled::dispatch($request->all());

        $payload = $request->all();

        // Inject orderReference if not exists
        if (!isset($payload['orderReference'])) {
            $payload['orderReference'] = $payload['merchantOrderId'];
        }

        // Inject transactionId if not exists
        if (!isset($payload['transactionId'])) {
            $payload['transactionId'] = '';
        }

        // Inject cardBrand if not exists
        if (!isset($payload['cardBrand'])) {
            $payload['cardBrand'] = 'Card';
        }

        $statusStyle = OrderStatus::tryFrom($payload['paymentStatus'])?->styleColor();
        $payload['statusStyle'] = $statusStyle;

        return view('kashier::receipt', [
            'order' => $payload
        ]);
    }
}
