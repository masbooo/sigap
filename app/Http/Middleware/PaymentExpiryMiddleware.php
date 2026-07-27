<?php

namespace App\Http\Middleware;

use App\Supports\Payment\PaymentGateway;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class PaymentExpiryMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->is('assets/*')) {
            try {
                app(PaymentGateway::class)->expireOverduePayments();
            } catch (Throwable) {
                // Expiry sync should not block the requested page.
            }
        }

        /** @var Response $response */
        $response = $next($request);

        return $response;
    }
}
