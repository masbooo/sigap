<?php

namespace App\Repositories;

use Illuminate\Support\Facades\Schema;

class PembayaranRepository extends LegacyRepository
{
    protected $table = 'pembayaran';

    protected array $columnExistsCache = [];

    public function create(array $data): bool
    {
        $payload = [
            'reservasi_id' => $data['reservasi_id'] ?? null,
            'nominal' => $data['nominal'] ?? 0,
            'metode' => $data['metode'] ?? null,
            'bukti_pembayaran' => $data['bukti_pembayaran'] ?? null,
            'tanggal_bayar' => $data['tanggal_bayar'] ?? null,
            'status_verifikasi' => $data['status_verifikasi'] ?? 'PENDING',
        ];

        $payload = array_filter(
            $payload,
            fn (mixed $value, string $column): bool => $this->hasColumn($this->table, $column),
            ARRAY_FILTER_USE_BOTH
        );

        if ($payload === []) {
            return false;
        }

        return $this->query()->insert($payload);
    }

    public function all(): array
    {
        return $this->rows(
            $this->query()
                ->orderByDesc('id')
                ->get()
        );
    }

    public function deleteByReservationAndProofPath(int $reservationId, string $proofPath): bool
    {
        if (
            !$this->hasColumn($this->table, 'reservasi_id') ||
            !$this->hasColumn($this->table, 'bukti_pembayaran')
        ) {
            return false;
        }

        return $this->query()
            ->where('reservasi_id', $reservationId)
            ->where('bukti_pembayaran', $proofPath)
            ->delete() > 0;
    }

    private function hasColumn(string $table, string $column): bool
    {
        $cacheKey = $table . '.' . $column;
        if (array_key_exists($cacheKey, $this->columnExistsCache)) {
            return $this->columnExistsCache[$cacheKey];
        }

        $exists = Schema::hasColumn($table, $column);
        $this->columnExistsCache[$cacheKey] = $exists;

        return $exists;
    }
}
