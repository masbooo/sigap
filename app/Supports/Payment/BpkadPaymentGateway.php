<?php

declare(strict_types=1);

namespace App\Supports\Payment;

use DateTimeImmutable;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

class BpkadPaymentGateway implements PaymentGateway
{
    private const METHOD_VA = 'VA';
    private const METHOD_QRIS = 'QRIS';
    private const ACTIVE_STATUS = 'ACTIVE';
    private const EXPIRED_STATUS = 'EXPIRED';
    private const CANCELLED_STATUS = 'CANCELLED';
    private const PAID_STATUS = 'PAID';

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

        $payments = DB::table('pembayaran')
            ->where('reservation_id', $reservationId)
            ->where('status', self::ACTIVE_STATUS)
            ->get();

        foreach ($payments as $payment) {
            $externalReference = trim((string) ($payment->external_id ?? ''));
            if ($externalReference === '') {
                continue;
            }

            try {
                $this->post(
                    'cancel',
                    [
                        'reason' => 'Reservasi dibatalkan',
                        'cancelled_by' => 'sigap',
                    ],
                    ['external_reference' => $externalReference]
                );
            } catch (RuntimeException) {
                // BPKAD cancel is best-effort; local cancellation keeps the user flow moving.
            }
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
            $this->syncPaymentStatus($activePayment);

            return (array) DB::table('pembayaran')->where('id', (int) $activePayment['id'])->first();
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
        $amount = (int) round((float) ($reservation['total_price'] ?? $reservation['amount'] ?? 0));
        if ($method === self::METHOD_QRIS && $amount > 10000000) {
            throw new InvalidArgumentException('Nominal QRIS maksimal Rp 10.000.000.');
        }

        $customerName = trim((string) ($reservation['user_name'] ?? $reservation['name'] ?? ''));
        $customerPhone = trim((string) ($reservation['phone'] ?? $reservation['user_phone'] ?? ''));
        $customerEmail = trim((string) ($reservation['email'] ?? $reservation['user_email'] ?? ''));
        $year = (int) ($reservation['year'] ?? now()->year);
        $startDate = trim((string) ($reservation['start_date'] ?? ''));
        if ($startDate !== '') {
            $startTimestamp = strtotime($startDate);
            if ($startTimestamp !== false) {
                $year = (int) date('Y', $startTimestamp);
            }
        }

        return [
            'external_reference' => $this->buildExternalReference($method, (int) $reservation['id']),
            'service_code' => (string) config('payment.bpkad.service_code'),
            'object_type' => (string) config('payment.bpkad.object_type'),
            'customer_name' => $customerName !== '' ? $customerName : 'Pengguna SIGAP',
            'customer_email' => filter_var($customerEmail, FILTER_VALIDATE_EMAIL) ? $customerEmail : null,
            'customer_phone' => $customerPhone !== '' ? $customerPhone : null,
            'description' => 'Pembayaran reservasi SIGAP GSG',
            'period' => (string) $year,
            'amount' => $amount,
            'year' => $year,
        ];
    }

    private function normalizeCreateResponse(array $reservation, string $method, array $payload, array $response): array
    {
        $data = $this->responseData($response);
        $expiredAt = trim((string) data_get($data, 'expired_at', ''));
        if ($expiredAt === '') {
            $expiredAt = (new DateTimeImmutable('now'))->modify($method === self::METHOD_VA ? '+1 hour' : '+15 minutes')->format('Y-m-d H:i:s');
        }

        return [
            'reservation_id' => (int) $reservation['id'],
            'payment_method' => $method,
            'provider' => 'BPKAD',
            'external_id' => (string) data_get($data, 'external_reference', $payload['external_reference']),
            'payment_code' => $method === self::METHOD_VA
                ? (string) data_get($data, 'virtual_account', data_get($data, 'payment_code', ''))
                : (string) data_get($data, 'bill_number', data_get($data, 'invoice_number', '')),
            'qris_url' => $method === self::METHOD_QRIS ? $this->resolveQrisImageSource($data) : null,
            'amount' => (float) data_get($data, 'amount', $payload['amount']),
            'status' => self::ACTIVE_STATUS,
            'expired_at' => $expiredAt,
            'raw_response' => json_encode($response, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    private function post(string $endpointKey, array $payload, array $pathParams = []): array
    {
        [$url, $path] = $this->endpoint($endpointKey, $pathParams);
        $body = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $timestamp = now()->toIso8601String();

        $response = $this->httpClient('POST', $path, $timestamp, (string) $body)
            ->withHeaders($this->idempotencyHeaders($endpointKey, $payload))
            ->withBody((string) $body, 'application/json')
            ->post($url);

        if (!$response->successful()) {
            throw new RuntimeException('Request payment BPKAD gagal: HTTP ' . $response->status());
        }

        $json = $response->json();
        if (!is_array($json)) {
            throw new RuntimeException('Response payment BPKAD tidak valid.');
        }

        return $json;
    }

    private function get(string $endpointKey, array $pathParams = []): array
    {
        [$url, $path] = $this->endpoint($endpointKey, $pathParams);
        $timestamp = now()->toIso8601String();
        $response = $this->httpClient('GET', $path, $timestamp, '')->get($url);

        if (!$response->successful()) {
            throw new RuntimeException('Request inquiry BPKAD gagal: HTTP ' . $response->status());
        }

        $json = $response->json();
        if (!is_array($json)) {
            throw new RuntimeException('Response inquiry BPKAD tidak valid.');
        }

        return $json;
    }

    private function httpClient(string $method, string $path, string $timestamp, string $body): PendingRequest
    {
        return Http::timeout((int) config('payment.bpkad.timeout', 15))
            ->acceptJson()
            ->withHeaders([
                'Content-Type' => 'application/json',
                'X-Client-Id' => (string) config('payment.bpkad.client_id'),
                'X-Timestamp' => $timestamp,
                'X-Signature' => $this->signature($method, $path, $timestamp, $body),
            ]);
    }

    private function endpoint(string $endpointKey, array $pathParams = []): array
    {
        $baseUrl = rtrim((string) config('payment.bpkad.base_url'), '/');
        $path = '/' . ltrim((string) config('payment.bpkad.endpoints.' . $endpointKey), '/');

        foreach ($pathParams as $key => $value) {
            $path = str_replace('{' . $key . '}', rawurlencode((string) $value), $path);
        }

        $basePath = trim((string) parse_url($baseUrl, PHP_URL_PATH), '/');
        $signaturePath = '/' . trim($basePath . '/' . ltrim($path, '/'), '/');

        return [$baseUrl . $path, $signaturePath];
    }

    private function buildExternalReference(string $method, int $reservationId): string
    {
        return 'SIGAP-GSG-' . now()->format('Y') . '-' . $reservationId . '-' . $method . '-' . now()->format('YmdHis') . '-' . random_int(1000, 9999);
    }

    private function assertConfigured(): void
    {
        foreach (['base_url', 'client_id', 'secret', 'service_code', 'object_type'] as $key) {
            if (trim((string) config('payment.bpkad.' . $key)) === '') {
                throw new RuntimeException('Konfigurasi BPKAD API belum lengkap: payment.bpkad.' . $key);
            }
        }
    }

    private function signature(string $method, string $path, string $timestamp, string $body): string
    {
        $stringToSign = strtoupper($method) . "\n" . $path . "\n" . $timestamp . "\n" . hash('sha256', $body);

        return hash_hmac('sha256', $stringToSign, (string) config('payment.bpkad.secret'));
    }

    private function responseData(array $response): array
    {
        $data = data_get($response, 'data', $response);

        return is_array($data) ? $data : [];
    }

    private function resolveQrisImageSource(array $data): ?string
    {
        $source = trim((string) data_get($data, 'qris_url', data_get($data, 'qr_url', data_get($data, 'qr_image_url', ''))));

        if ($source === '') {
            return null;
        }

        if (str_starts_with($source, 'data:image/') || filter_var($source, FILTER_VALIDATE_URL)) {
            return $source;
        }

        return null;
    }

    private function syncPaymentStatus(array $payment): void
    {
        $externalReference = trim((string) ($payment['external_id'] ?? ''));
        if ($externalReference === '') {
            return;
        }

        try {
            $response = $this->get('inquiry', ['external_reference' => $externalReference]);
        } catch (RuntimeException) {
            return;
        }

        $data = $this->responseData($response);
        $status = strtoupper(trim((string) data_get($data, 'status', '')));
        $mappedStatus = match ($status) {
            'PAID', 'SUCCESS', 'LUNAS', 'SETTLEMENT' => self::PAID_STATUS,
            'CANCELLED', 'CANCELED' => self::CANCELLED_STATUS,
            'EXPIRED' => self::EXPIRED_STATUS,
            'FAILED' => 'FAILED',
            default => '',
        };

        if ($mappedStatus === '') {
            return;
        }

        DB::table('pembayaran')->where('id', (int) $payment['id'])->update([
            'status' => $mappedStatus,
            'paid_at' => $mappedStatus === self::PAID_STATUS ? now() : ($payment['paid_at'] ?? null),
            'last_checked_at' => now(),
            'raw_response' => json_encode($response, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'updated_at' => now(),
        ]);
    }

    private function idempotencyHeaders(string $endpointKey, array $payload): array
    {
        if (!in_array($endpointKey, ['create_va', 'create_qris'], true)) {
            return [];
        }

        return ['Idempotency-Key' => $this->idempotencyKey($endpointKey, $payload)];
    }

    private function idempotencyKey(string $endpointKey, array $payload): string
    {
        $reference = (string) ($payload['external_reference'] ?? Str::uuid());

        return $endpointKey . ':' . $reference;
    }
}
