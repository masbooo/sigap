<?php

class Pembayaran
{
    protected PDO $db;
    protected array $columnExistsCache = [];

    public function __construct()
    {
        $this->db = Database::connect();
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

        $stmt = $this->db->prepare("
            SELECT COUNT(*) AS total
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = :table
              AND COLUMN_NAME = :column
        ");
        $stmt->execute([
            ':table' => $table,
            ':column' => $column,
        ]);

        $exists = (int) (($stmt->fetch()['total'] ?? 0)) > 0;
        $this->columnExistsCache[$cacheKey] = $exists;

        return $exists;
    }
}
