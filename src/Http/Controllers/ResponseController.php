<?php

namespace Asciisd\Kashier\Http\Controllers;

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
        KashierResponseHandled::dispatch($request->json()->all());

        return view('kashier::receipt', [
            'order' => $request->json()->all()
        ]);
    }
}
