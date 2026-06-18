<?php

class Jadwal
{
    protected PDO $db;
    protected array $columnExistsCache = [];

    public function __construct()
    {
        $this->db = Database::connect();
    }

    public function getFilterData(): array
    {
        $sql = "
            SELECT
                w.region,
                k.id AS district_id,
                k.district,
                g.id AS building_id,
                g.building_name,
                g.capacity
            FROM gedung g
            INNER JOIN kecamatan k
                ON k.id = g.district_id
            INNER JOIN wilayah w
                ON w.region = k.region
            WHERE g.status = 'AKTIF'
            ORDER BY
                FIELD(w.region, 'Pusat', 'Timur', 'Selatan', 'Barat', 'Utara'),
                k.district ASC,
                g.building_name ASC
        ";

        $stmt = $this->db->query($sql);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $grouped = [];

        foreach ($rows as $row) {
            $region = $row['region'] ?? 'Tidak Diketahui';
            $districtId = (int) ($row['district_id'] ?? 0);
            $districtName = $row['district'] ?? 'Tidak Diketahui';
            $buildingId = (int) ($row['building_id'] ?? 0);
            $buildingName = $row['building_name'] ?? 'Tanpa Nama';
            $buildingCapacity = (int) ($row['capacity'] ?? 0);

            if (!isset($grouped[$region])) {
                $grouped[$region] = [
                    'region' => $region,
                    'district_count' => 0,
                    'districts' => [],
                ];
            }

            if (!isset($grouped[$region]['districts'][$districtId])) {
                $grouped[$region]['districts'][$districtId] = [
                    'id' => $districtId,
                    'name' => $districtName,
                    'building_count' => 0,
                    'buildings' => [],
                ];
                $grouped[$region]['district_count']++;
            }

            $grouped[$region]['districts'][$districtId]['buildings'][] = [
                'id' => $buildingId,
                'name' => $buildingName,
                'capacity' => $buildingCapacity,
            ];

            $grouped[$region]['districts'][$districtId]['building_count']++;
        }

        foreach ($grouped as $region => $data) {
            $grouped[$region]['districts'] = array_values($data['districts']);
        }

        return array_values($grouped);
    }

    public function getCalendarEvents(array $statuses = []): array
    {
        $normalizedStatuses = $this->normalizeStatuses($statuses);
        if ($normalizedStatuses === []) {
            $normalizedStatuses = $this->getDefaultCalendarStatuses();
        }

        $queryStatuses = $this->normalizeStatusesForQuery($normalizedStatuses);
        $placeholders = [];
        $params = [];

        foreach ($queryStatuses as $index => $status) {
            $placeholder = ':status_' . $index;
            $placeholders[] = $placeholder;
            $params[$placeholder] = $status;
        }

        $sessionDisplayNameSql = $this->getSessionDisplayNameSql('r');
        $statusSelectSql = $this->getReservationStatusSelectSql('r', 'sr');
        $statusJoinSql = $this->getReservationStatusJoinSql('r', 'sr');
        $statusFilterSql = $this->getReservationStatusFilterSql('r', 'sr');
        $notesSelectSql = $this->getSelectColumnOrDefaultSql('reservasi', 'r', 'notes', "''", 'notes');
        $sql = "
            SELECT
                r.id,
                r.user_id,
                r.start_date,
                r.end_date,
                {$statusSelectSql},
                {$notesSelectSql},
                r.est_person,
                r.building_id,
                r.event_id,
                r.start_time,
                r.end_time,
                g.building_name,
                k.district,
                w.region,
                {$sessionDisplayNameSql} AS session_name,
                a.event_name,
                u.name AS order_name
            FROM reservasi r
            {$statusJoinSql}
            INNER JOIN gedung g
                ON g.id = r.building_id
            INNER JOIN kecamatan k
                ON k.id = g.district_id
            INNER JOIN wilayah w
                ON w.region = k.region
            LEFT JOIN acara a
                ON a.id = r.event_id
            LEFT JOIN user u
                ON u.id = r.user_id
            WHERE {$statusFilterSql} IN (" . implode(', ', $placeholders) . ")
            ORDER BY r.start_date ASC, COALESCE(r.start_time, '00:00:00') ASC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $events = [];

        foreach ($rows as $row) {
            $status = reservation_status_display_key($row['status'] ?? 'RESERVASI BARU');
            $color = $this->getStatusColor($status);

            $startDate = $row['start_date'] ?? null;
            $endDate = $row['end_date'] ?? null;
            $startTime = $row['start_time'] ?? '00:00:00';
            $endTime = $row['end_time'] ?? '23:59:59';

            if (!$startDate || !$endDate) {
                continue;
            }

            $acaraName = $row['event_name'] ?? 'Tanpa Acara';

            $events[] = [
                'id' => (string) ($row['id'] ?? ''),
                'title' => ($row['building_name'] ?? 'Gedung') . ' - ' . $acaraName,
                'start' => $startDate . 'T' . $startTime,
                'end' => $endDate . 'T' . $endTime,
                'allDay' => false,
                'building_id' => (int) ($row['building_id'] ?? 0),
                'region' => $row['region'] ?? '',
                'district' => $row['district'] ?? '',
                'backgroundColor' => $color,
                'borderColor' => $color,
                'extendedProps' => [
                    'building_name' => $row['building_name'] ?? '',
                    'session_name' => $row['session_name'] ?? '',
                    'acara_name' => $acaraName,
                    'status' => $status,
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'user' => $row['order_name'] ?? '',
                    'notes' => $row['notes'] ?? '',
                    'est_person' => (int) ($row['est_person'] ?? 0),
                ],
            ];
        }

        return $events;
    }

    private function getSessionDisplayNameSql(string $tableAlias = 'r'): string
    {
        $prefix = $tableAlias !== '' ? $tableAlias . '.' : '';

        return "
            CASE
                WHEN TIME({$prefix}start_time) = '08:00:00' AND TIME({$prefix}end_time) = '13:00:00'
                    THEN 'Pagi (08.00 - 13.00)'
                WHEN TIME({$prefix}start_time) = '16:00:00' AND TIME({$prefix}end_time) = '21:00:00'
                    THEN 'Malam (16.00 - 21.00)'
                WHEN TIME({$prefix}start_time) = '08:00:00' AND TIME({$prefix}end_time) = '22:00:00'
                    THEN '1 Hari (08.00 - 22.00)'
                WHEN {$prefix}start_time IS NOT NULL AND {$prefix}end_time IS NOT NULL
                    THEN CONCAT(
                        'Lainnya (',
                        DATE_FORMAT({$prefix}start_time, '%H.%i'),
                        ' - ',
                        DATE_FORMAT({$prefix}end_time, '%H.%i'),
                        ')'
                    )
                ELSE '-'
            END
        ";
    }

    private function getReservationStatusSelectSql(string $tableAlias, string $statusAlias): string
    {
        if ($this->hasColumn('reservasi', 'status')) {
            return "{$tableAlias}.status AS status";
        }

        if ($this->usesStatusRelation()) {
            return "COALESCE({$statusAlias}.name, 'Reservasi Baru') AS status";
        }

        return "'Reservasi Baru' AS status";
    }

    private function getReservationStatusJoinSql(string $tableAlias, string $statusAlias): string
    {
        if (!$this->usesStatusRelation()) {
            return '';
        }

        return "LEFT JOIN status_reservasi {$statusAlias}
                ON {$statusAlias}.id = {$tableAlias}.status_id";
    }

    private function getReservationStatusFilterSql(string $tableAlias, string $statusAlias): string
    {
        if ($this->hasColumn('reservasi', 'status')) {
            return "UPPER(TRIM({$tableAlias}.status))";
        }

        if ($this->usesStatusRelation()) {
            return "UPPER(TRIM(COALESCE({$statusAlias}.name, '')))";
        }

        return "UPPER('Reservasi Baru')";
    }

    private function getSelectColumnOrDefaultSql(
        string $table,
        string $tableAlias,
        string $column,
        string $defaultSql,
        ?string $outputAlias = null
    ): string {
        if ($this->hasColumn($table, $column)) {
            $expression = ($tableAlias !== '' ? $tableAlias . '.' : '') . $column;
        } else {
            $expression = $defaultSql;
        }

        if ($outputAlias !== null && $outputAlias !== '') {
            $expression .= ' AS ' . $outputAlias;
        }

        return $expression;
    }

    protected function getStatusColor(string $status): string
    {
        return match (reservation_status_display_key($status)) {
            'RESERVASI BARU' => '#f59e0b',
            'BERKAS RESERVASI TIDAK SESUAI' => '#1f2937',
            'KERJASAMA UMKM' => '#06b6d4',
            'PROSES VERIFIKASI' => '#f59e0b',
            'BERKAS VERIFIKASI TIDAK SESUAI' => '#1f2937',
            'MENUNGGU PEMBAYARAN' => '#5d87ff',
            'CEK PEMBAYARAN' => '#f59e0b',
            'BERKAS PEMBAYARAN TIDAK SESUAI' => '#1f2937',
            'PEMBAYARAN LUNAS' => '#10b981',
            'PERMOHONAN DITOLAK', 'DIBATALKAN PEMOHON' => '#ef4444',
            'ACARA SELESAI' => '#1f2937',
            'BERKAS TIDAK SESUAI' => '#1f2937',
            default => '#64748b',
        };
    }

    private function normalizeStatuses(array $statuses): array
    {
        return reservation_status_filter_values($statuses);
    }

    private function normalizeStatusesForQuery(array $statuses): array
    {
        if ($this->usesStatusRelation() && function_exists('reservation_status_storage_values')) {
            return reservation_status_storage_values($statuses);
        }

        return $this->normalizeStatuses($statuses);
    }

    private function usesStatusRelation(): bool
    {
        return !$this->hasColumn('reservasi', 'status')
            && $this->hasColumn('reservasi', 'status_id')
            && $this->hasTable('status_reservasi');
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

    private function hasTable(string $table): bool
    {
        $cacheKey = '__table__.' . $table;
        if (array_key_exists($cacheKey, $this->columnExistsCache)) {
            return $this->columnExistsCache[$cacheKey];
        }

        $stmt = $this->db->prepare("
            SELECT COUNT(*) AS total
            FROM INFORMATION_SCHEMA.TABLES
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = :table
        ");
        $stmt->execute([
            ':table' => $table,
        ]);

        $exists = (int) (($stmt->fetch()['total'] ?? 0)) > 0;
        $this->columnExistsCache[$cacheKey] = $exists;

        return $exists;
    }

    private function getDefaultCalendarStatuses(): array
    {
        return $this->normalizeStatuses([
            'PEMBAYARAN LUNAS',
            'ACARA SELESAI',
        ]);
    }
}
