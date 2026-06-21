<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PDO;

class Pembayaran
{
    protected PDO $db;
    protected array $columnExistsCache = [];

    public function __construct()
    {
        $this->db = DB::connection()->getPdo();
    }

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

        $columns = [];
        $placeholders = [];
        $params = [];

        foreach ($payload as $column => $value) {
            if (!$this->hasColumn('pembayaran', $column)) {
                continue;
            }

            $columns[] = $column;
            $placeholders[] = ':' . $column;
            $params[':' . $column] = $value;
        }

        if ($columns === []) {
            return false;
        }

        $sql = "INSERT INTO pembayaran 
            (" . implode(', ', $columns) . ")
            VALUES
            (" . implode(', ', $placeholders) . ")";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    public function all(): array
    {
        $stmt = $this->db->query("SELECT * FROM pembayaran ORDER BY id DESC");
        return $stmt->fetchAll();
    }

    public function deleteByReservationAndProofPath(int $reservationId, string $proofPath): bool
    {
        if (
            !$this->hasColumn('pembayaran', 'reservasi_id') ||
            !$this->hasColumn('pembayaran', 'bukti_pembayaran')
        ) {
            return false;
        }

        $stmt = $this->db->prepare("
            DELETE FROM pembayaran
            WHERE reservasi_id = :reservasi_id
              AND bukti_pembayaran = :bukti_pembayaran
        ");

        return $stmt->execute([
            ':reservasi_id' => $reservationId,
            ':bukti_pembayaran' => $proofPath,
        ]);
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
