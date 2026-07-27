<?php

declare(strict_types=1);

namespace App\Supports\Payment;

use InvalidArgumentException;

class PaymentGatewayManager
{
    public function gateway(): PaymentGateway
    {
        return match (strtolower((string) config('payment.gateway', 'test'))) {
            'test', 'local', 'fake' => app(PaymentTestGateway::class),
            'bpkad' => app(BpkadPaymentGateway::class),
            default => throw new InvalidArgumentException('Payment gateway tidak dikenal.'),
        };
    }
}
