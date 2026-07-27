<?php

declare(strict_types=1);

namespace App\Supports\Payment;

use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use PDO;
use RuntimeException;
use Throwable;

class PaymentTestGateway implements PaymentGateway
{
    private const PROVIDER = 'BANK JATIM';
    private const METHOD_VA = 'VA';
    private const METHOD_QRIS = 'QRIS';
    private const ACTIVE_STATUS = 'ACTIVE';
    private const CANCELLED_STATUS = 'CANCELLED';
    private const EXPIRED_STATUS = 'EXPIRED';
    private const VA_PREFIX = '1030801';

    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? DB::connection()->getPdo();
    }

    public function createVirtualAccount(array $reservation): array
    {
        $reservationId = (int) ($reservation['id'] ?? 0);
        if ($reservationId <= 0) {
            throw new InvalidArgumentException('Reservasi tidak valid untuk pembayaran VA.');
        }

        $this->expireOverduePayments($reservationId);

        $paymentCode = $this->generateUniqueVirtualAccountCode((string) ($reservation['start_date'] ?? ''));
        $requestedAt = $this->currentDatabaseDateTime();
        $expiredAt = $requestedAt->modify('+1 hour');
        $amount = (float) ($reservation['total_price'] ?? $reservation['amount'] ?? 0);
        $externalId = $this->generateExternalId(self::METHOD_VA, $reservationId);
        $rawResponse = [
            'mode' => 'testing',
            'provider' => self::PROVIDER,
            'payment_method' => self::METHOD_VA,
            'payment_code' => $paymentCode,
            'external_id' => $externalId,
            'expired_at' => $expiredAt->format('Y-m-d H:i:s'),
            'generated_at' => $requestedAt->format('Y-m-d H:i:s'),
        ];

        $stmt = $this->db->prepare("
            INSERT INTO pembayaran
                (reservation_id, payment_method, provider, external_id, payment_code, qris_url, amount, status, expired_at, raw_response)
            VALUES
                (:reservation_id, :payment_method, :provider, :external_id, :payment_code, NULL, :amount, :status, :expired_at, :raw_response)
        ");

        $stmt->execute([
            ':reservation_id' => $reservationId,
            ':payment_method' => self::METHOD_VA,
            ':provider' => self::PROVIDER,
            ':external_id' => $externalId,
            ':payment_code' => $paymentCode,
            ':amount' => $amount,
            ':status' => self::ACTIVE_STATUS,
            ':expired_at' => $expiredAt->format('Y-m-d H:i:s'),
            ':raw_response' => json_encode($rawResponse, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        ]);

        return $this->findById((int) $this->db->lastInsertId()) ?? [
            'reservation_id' => $reservationId,
            'payment_method' => self::METHOD_VA,
            'provider' => self::PROVIDER,
            'external_id' => $externalId,
            'payment_code' => $paymentCode,
            'amount' => $amount,
            'status' => self::ACTIVE_STATUS,
            'expired_at' => $expiredAt->format('Y-m-d H:i:s'),
        ];
    }

    public function requestVirtualAccount(array $reservation): array
    {
        $reservationId = (int) ($reservation['id'] ?? 0);
        if ($reservationId <= 0) {
            throw new InvalidArgumentException('Reservasi tidak valid untuk pembayaran VA.');
        }

        $this->expireOverduePayments($reservationId);

        $activePayment = $this->findActivePayment($reservationId, self::METHOD_VA);
        if ($activePayment !== null) {
            return $activePayment;
        }

        return $this->createVirtualAccount($reservation);
    }

    public function requestQris(array $reservation): array
    {
        $reservationId = (int) ($reservation['id'] ?? 0);
        if ($reservationId <= 0) {
            throw new InvalidArgumentException('Reservasi tidak valid untuk pembayaran QRIS.');
        }

        $this->expireOverduePayments($reservationId);

        $activePayment = $this->findActivePayment($reservationId, self::METHOD_QRIS);
        if ($activePayment !== null) {
            return $activePayment;
        }

        return $this->createQris($reservation);
    }

    private function createQris(array $reservation): array
    {
        $reservationId = (int) ($reservation['id'] ?? 0);
        $requestedAt = $this->currentDatabaseDateTime();
        $expiredAt = $requestedAt->modify('+15 minutes');
        $amount = (float) ($reservation['total_price'] ?? $reservation['amount'] ?? 0);
        $externalId = $this->generateExternalId(self::METHOD_QRIS, $reservationId);
        $qrisUrl = asset('assets/custom/images/payment/qris-sample-qr.png');
        $rawResponse = [
            'mode' => 'testing',
            'provider' => self::PROVIDER,
            'payment_method' => self::METHOD_QRIS,
            'qris_url' => $qrisUrl,
            'external_id' => $externalId,
            'expired_at' => $expiredAt->format('Y-m-d H:i:s'),
            'generated_at' => $requestedAt->format('Y-m-d H:i:s'),
        ];

        $stmt = $this->db->prepare("
            INSERT INTO pembayaran
                (reservation_id, payment_method, provider, external_id, payment_code, qris_url, amount, status, expired_at, raw_response)
            VALUES
                (:reservation_id, :payment_method, :provider, :external_id, NULL, :qris_url, :amount, :status, :expired_at, :raw_response)
        ");

        $stmt->execute([
            ':reservation_id' => $reservationId,
            ':payment_method' => self::METHOD_QRIS,
            ':provider' => self::PROVIDER,
            ':external_id' => $externalId,
            ':qris_url' => $qrisUrl,
            ':amount' => $amount,
            ':status' => self::ACTIVE_STATUS,
            ':expired_at' => $expiredAt->format('Y-m-d H:i:s'),
            ':raw_response' => json_encode($rawResponse, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        ]);

        return $this->findById((int) $this->db->lastInsertId()) ?? [
            'reservation_id' => $reservationId,
            'payment_method' => self::METHOD_QRIS,
            'provider' => self::PROVIDER,
            'external_id' => $externalId,
            'qris_url' => $qrisUrl,
            'amount' => $amount,
            'status' => self::ACTIVE_STATUS,
            'expired_at' => $expiredAt->format('Y-m-d H:i:s'),
        ];
    }

    public function findActivePayment(int $reservationId, ?string $method = null): ?array
    {
        if ($reservationId <= 0) {
            return null;
        }

        $this->expireOverduePayments($reservationId);

        $params = [
            ':reservation_id' => $reservationId,
            ':status' => self::ACTIVE_STATUS,
        ];
        $methodSql = '';
        $normalizedMethod = $this->normalizeMethod($method ?? '');

        if ($normalizedMethod !== '') {
            $methodSql = ' AND payment_method = :payment_method';
            $params[':payment_method'] = $normalizedMethod;
        }

        $stmt = $this->db->prepare("
            SELECT *
            FROM pembayaran
            WHERE reservation_id = :reservation_id
              AND status = :status
              AND expired_at > NOW()
              {$methodSql}
            ORDER BY id DESC
            LIMIT 1
        ");
        $stmt->execute($params);

        $payment = $stmt->fetch();

        return is_array($payment) ? $payment : null;
    }

    public function cancelActivePayments(int $reservationId): bool
    {
        if ($reservationId <= 0) {
            return false;
        }

        $stmt = $this->db->prepare("
            UPDATE pembayaran
            SET status = :cancelled_status,
                cancelled_at = COALESCE(cancelled_at, NOW()),
                updated_at = NOW()
            WHERE reservation_id = :reservation_id
              AND status = :active_status
        ");

        return $stmt->execute([
            ':cancelled_status' => self::CANCELLED_STATUS,
            ':reservation_id' => $reservationId,
            ':active_status' => self::ACTIVE_STATUS,
        ]);
    }

    public function expireOverduePayments(?int $reservationId = null): void
    {
        if ($reservationId !== null && $reservationId <= 0) {
            return;
        }

        $conditions = [
            'status = :active_status',
            'expired_at <= NOW()',
        ];
        $params = [
            ':expired_status' => self::EXPIRED_STATUS,
            ':active_status' => self::ACTIVE_STATUS,
        ];

        if ($reservationId !== null) {
            $conditions[] = 'reservation_id = :reservation_id';
            $params[':reservation_id'] = $reservationId;
        }

        $stmt = $this->db->prepare("
            UPDATE pembayaran
            SET status = :expired_status,
                updated_at = NOW()
            WHERE " . implode(' AND ', $conditions) . "
        ");

        $stmt->execute($params);
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

    private function findById(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }

        $stmt = $this->db->prepare("SELECT * FROM pembayaran WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $payment = $stmt->fetch();

        return is_array($payment) ? $payment : null;
    }

    private function currentDatabaseDateTime(): DateTimeImmutable
    {
        $stmt = $this->db->query('SELECT NOW() AS db_now');
        $value = trim((string) ($stmt->fetch()['db_now'] ?? ''));

        return $value !== ''
            ? new DateTimeImmutable($value)
            : new DateTimeImmutable('now');
    }

    private function generateUniqueVirtualAccountCode(string $reservationDate): string
    {
        for ($attempt = 0; $attempt < 20; $attempt++) {
            $code = self::VA_PREFIX . $this->formatReservationDatePart($reservationDate) . $this->randomSixDigits();

            if (!$this->paymentCodeExists($code)) {
                return $code;
            }
        }

        throw new RuntimeException('Kode VA unik gagal dibuat. Silakan coba lagi.');
    }

    private function formatReservationDatePart(string $reservationDate): string
    {
        $reservationDate = trim($reservationDate);

        try {
            $date = $reservationDate !== '' ? new DateTimeImmutable($reservationDate) : new DateTimeImmutable('now');
        } catch (Throwable $exception) {
            $date = new DateTimeImmutable('now');
        }

        return $date->format('ymd');
    }

    private function randomSixDigits(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    private function paymentCodeExists(string $code): bool
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) AS total FROM pembayaran WHERE payment_code = :payment_code");
        $stmt->execute([':payment_code' => $code]);

        return (int) ($stmt->fetch()['total'] ?? 0) > 0;
    }

    private function generateExternalId(string $method, int $reservationId): string
    {
        $normalizedMethod = $this->normalizeMethod($method);
        if ($normalizedMethod === '') {
            throw new InvalidArgumentException('Metode pembayaran tidak valid.');
        }

        $prefix = $normalizedMethod . '-' . $reservationId . '-';
        $stmt = $this->db->prepare("
            SELECT external_id
            FROM pembayaran
            WHERE reservation_id = :reservation_id
              AND payment_method = :payment_method
              AND external_id LIKE :external_id_prefix
        ");
        $stmt->execute([
            ':reservation_id' => $reservationId,
            ':payment_method' => $normalizedMethod,
            ':external_id_prefix' => $prefix . '%',
        ]);

        $lastSequence = 0;
        $pattern = '/^' . preg_quote($prefix, '/') . '(\d+)$/';

        foreach ($stmt->fetchAll() as $row) {
            $externalId = trim((string) ($row['external_id'] ?? ''));

            if (preg_match($pattern, $externalId, $matches) !== 1) {
                continue;
            }

            $lastSequence = max($lastSequence, (int) $matches[1]);
        }

        return $prefix . str_pad((string) ($lastSequence + 1), 4, '0', STR_PAD_LEFT);
    }
}
