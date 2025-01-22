<?php

namespace Asciisd\Kashier\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class VerifyResponseSignature
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next): mixed
    {
        $queryString = "";
        $secret = config('kashier.apikey');

        foreach ($request->json()->all() as $key => $value) {
            if ($key === "signature" || $key === "mode") {
                continue;
            }
            $queryString .= "&".$key."=".$value;
        }

        $queryString = ltrim($queryString, '&');
        $signature = hash_hmac('sha256', $queryString, $secret, false);

        if ($signature !== $request["signature"]) {
            abort(403, 'Invalid signature');
        }

        return $next($request);
    }
}
