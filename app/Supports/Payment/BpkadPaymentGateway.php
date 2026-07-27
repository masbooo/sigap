<?php

declare(strict_types=1);

namespace App\Supports\Payment;

use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use RuntimeException;

class BpkadPaymentGateway implements PaymentGateway
{
    private const METHOD_VA = 'VA';
    private const METHOD_QRIS = 'QRIS';
    private const ACTIVE_STATUS = 'ACTIVE';
    private const EXPIRED_STATUS = 'EXPIRED';
    private const CANCELLED_STATUS = 'CANCELLED';

    public function requestVirtualAccount(array $reservation): array
    {
        return $this->requestPayment($reservation, self::METHOD_VA);
    }

    public function requestQris(array $reservation): array
    {
        return $this->requestPayment($reservation, self::METHOD_QRIS);
    }

    public function findActivePayment(int $reservationId, ?string $method = null): ?array
    {
        if ($reservationId <= 0) {
            return null;
        }

        $this->expireOverduePayments($reservationId);

        $query = DB::table('pembayaran')
            ->where('reservation_id', $reservationId)
            ->where('status', self::ACTIVE_STATUS)
            ->where('expired_at', '>', now())
            ->orderByDesc('id');

        $normalizedMethod = $this->normalizeMethod($method ?? '');
        if ($normalizedMethod !== '') {
            $query->where('payment_method', $normalizedMethod);
        }

        $payment = $query->first();

        return $payment !== null ? (array) $payment : null;
    }

    public function cancelActivePayments(int $reservationId): bool
    {
        if ($reservationId <= 0) {
            return false;
        }

        $updated = DB::table('pembayaran')
            ->where('reservation_id', $reservationId)
            ->where('status', self::ACTIVE_STATUS)
            ->update([
                'status' => self::CANCELLED_STATUS,
                'cancelled_at' => now(),
                'updated_at' => now(),
            ]);

        return $updated >= 0;
    }

    public function expireOverduePayments(?int $reservationId = null): void
    {
        $query = DB::table('pembayaran')
            ->where('status', self::ACTIVE_STATUS)
            ->where('expired_at', '<=', now());

        if ($reservationId !== null && $reservationId > 0) {
            $query->where('reservation_id', $reservationId);
        }

        $query->update([
            'status' => self::EXPIRED_STATUS,
            'updated_at' => now(),
        ]);
    }

    public function normalizeMethod(string $method): string
    {
        $normalized = strtoupper(trim($method));

        return in_array($normalized, [self::METHOD_VA, self::METHOD_QRIS], true) ? $normalized : '';
    }

    public function methodKey(array $payment): string
    {
        return strtolower($this->normalizeMethod((string) ($payment['payment_method'] ?? '')));
    }

    private function requestPayment(array $reservation, string $method): array
    {
        $reservationId = (int) ($reservation['id'] ?? 0);
        if ($reservationId <= 0) {
            throw new InvalidArgumentException('Reservasi tidak valid untuk pembayaran.');
        }

        $this->assertConfigured();
        $this->expireOverduePayments($reservationId);

        $activePayment = $this->findActivePayment($reservationId, $method);
        if ($activePayment !== null) {
            return $activePayment;
        }

        $endpointKey = $method === self::METHOD_VA ? 'create_va' : 'create_qris';
        $payload = $this->buildCreatePayload($reservation, $method);
        $response = $this->post($endpointKey, $payload);
        $payment = $this->normalizeCreateResponse($reservation, $method, $payload, $response);

        $paymentId = DB::table('pembayaran')->insertGetId($payment);

        return (array) DB::table('pembayaran')->where('id', $paymentId)->first();
    }

    private function buildCreatePayload(array $reservation, string $method): array
    {
        return [
            'external_id' => $this->buildExternalId($method, (int) $reservation['id']),
            'reservation_id' => (int) $reservation['id'],
            'request_id' => (string) ($reservation['request_id'] ?? ''),
            'order_id' => (string) ($reservation['order_id'] ?? ''),
            'method' => $method,
            'amount' => (float) ($reservation['total_price'] ?? $reservation['amount'] ?? 0),
            'description' => 'Pembayaran reservasi SIGAP',
            'customer' => [
                'name' => (string) ($reservation['user_name'] ?? $reservation['name'] ?? ''),
                'nik' => (string) ($reservation['nik'] ?? ''),
                'phone' => (string) ($reservation['phone'] ?? ''),
            ],
            'callback_url' => route('payment.callback.bpkad'),
        ];
    }

    private function normalizeCreateResponse(array $reservation, string $method, array $payload, array $response): array
    {
        $expiredAt = trim((string) data_get($response, 'expired_at', ''));
        if ($expiredAt === '') {
            $expiredAt = (new DateTimeImmutable('now'))->modify($method === self::METHOD_VA ? '+1 hour' : '+15 minutes')->format('Y-m-d H:i:s');
        }

        return [
            'reservation_id' => (int) $reservation['id'],
            'payment_method' => $method,
            'provider' => (string) data_get($response, 'provider', 'BPKAD'),
            'external_id' => (string) data_get($response, 'external_id', $payload['external_id']),
            'payment_code' => $method === self::METHOD_VA ? (string) data_get($response, 'payment_code', '') : null,
            'qris_url' => $method === self::METHOD_QRIS ? (string) data_get($response, 'qris_url', data_get($response, 'qr_url', '')) : null,
            'amount' => (float) data_get($response, 'amount', $payload['amount']),
            'status' => self::ACTIVE_STATUS,
            'expired_at' => $expiredAt,
            'raw_response' => json_encode($response, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    private function post(string $endpointKey, array $payload): array
    {
        $url = $this->endpointUrl($endpointKey);
        $body = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $signature = $this->signature((string) $body);

        $response = Http::timeout((int) config('payment.bpkad.timeout', 15))
            ->acceptJson()
            ->asJson()
            ->withHeaders([
                'X-Client-Id' => (string) config('payment.bpkad.client_id'),
                'X-Signature' => $signature,
            ])
            ->post($url, $payload);

        if (!$response->successful()) {
            throw new RuntimeException('Request payment BPKAD gagal: HTTP ' . $response->status());
        }

        $json = $response->json();
        if (!is_array($json)) {
            throw new RuntimeException('Response payment BPKAD tidak valid.');
        }

        return $json;
    }

    private function endpointUrl(string $endpointKey): string
    {
        $baseUrl = rtrim((string) config('payment.bpkad.base_url'), '/');
        $path = '/' . ltrim((string) config('payment.bpkad.endpoints.' . $endpointKey), '/');

        return $baseUrl . $path;
    }

    private function buildExternalId(string $method, int $reservationId): string
    {
        return $method . '-' . $reservationId . '-' . now()->format('YmdHis') . '-' . random_int(1000, 9999);
    }

    private function assertConfigured(): void
    {
        foreach (['base_url', 'client_id', 'secret'] as $key) {
            if (trim((string) config('payment.bpkad.' . $key)) === '') {
                throw new RuntimeException('Konfigurasi BPKAD API belum lengkap: payment.bpkad.' . $key);
            }
        }
    }

    private function signature(string $body): string
    {
        return hash_hmac('sha256', $body, (string) config('payment.bpkad.secret'));
    }
}
