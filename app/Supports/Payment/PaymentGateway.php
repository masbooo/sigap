<?php

declare(strict_types=1);

namespace App\Supports\Payment;

interface PaymentGateway
{
    public function requestVirtualAccount(array $reservation): array;

    public function requestQris(array $reservation): array;

    public function findActivePayment(int $reservationId, ?string $method = null): ?array;

    public function cancelActivePayments(int $reservationId): bool;

    public function expireOverduePayments(?int $reservationId = null): void;

    public function normalizeMethod(string $method): string;

    public function methodKey(array $payment): string;
}
