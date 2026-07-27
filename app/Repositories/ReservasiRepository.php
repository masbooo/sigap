<?php

namespace App\Repositories;

use DateTime;
use DateTimeImmutable;
use Illuminate\Support\Facades\Schema;
use PDO;
use Throwable;

class ReservasiRepository extends LegacyRepository
{
    protected $table = 'reservasi';

    protected array $columnExistsCache = [];
    protected array $statusIdCache = [];
    protected array $statusNameCache = [];
    private static bool $reservationCodeSchemaEnsured = false;
    private static bool $pendingApprovalStatusesSynchronized = false;

    private const REQUEST_CODE_PREFIX = 'REQ';
    private const ORDER_CODE_PREFIX = 'GSG';
    private const APPROVED_CODE_STATUSES = [
        'MENUNGGU PEMBAYARAN',
        'CEK PEMBAYARAN',
        'BERKAS PEMBAYARAN TIDAK SESUAI',
        'PEMBAYARAN LUNAS',
        'ACARA SELESAI',
    ];

    private const SESSION_PRESETS = [
        [
            'id' => 'pagi',
            'label' => 'Pagi (08.00 - 13.00)',
            'start_time' => '08:00:00',
            'end_time' => '13:00:00',
            'session_count' => 1,
            'hour_count' => 5,
            'is_custom' => false,
        ],
        [
            'id' => 'malam',
            'label' => 'Malam (16.00 - 21.00)',
            'start_time' => '16:00:00',
            'end_time' => '21:00:00',
            'session_count' => 1,
            'hour_count' => 5,
            'is_custom' => false,
        ],
        [
            'id' => 'full_day',
            'label' => '1 Hari (08.00 - 22.00)',
            'start_time' => '08:00:00',
            'end_time' => '22:00:00',
            'session_count' => 2,
            'hour_count' => 14,
            'is_custom' => false,
        ],
        [
            'id' => 'lainnya',
            'label' => 'Lainnya',
            'start_time' => null,
            'end_time' => null,
            'session_count' => 1,
            'hour_count' => 0,
            'is_custom' => true,
        ],
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->ensureReservationCodeSchema();
    }

    private function ensureReservationCodeSchema(): void
    {
        if (self::$reservationCodeSchemaEnsured) {
            return;
        }

        self::$reservationCodeSchemaEnsured = true;

        try {
            $schemaChanged = false;

            if (!$this->hasColumn('reservasi', 'request_id')) {
                $this->db->exec("ALTER TABLE reservasi ADD COLUMN request_id VARCHAR(30) NULL DEFAULT NULL AFTER id");
                $this->columnExistsCache['reservasi.request_id'] = true;
                $schemaChanged = true;
            }

            if (!$this->hasColumn('reservasi', 'order_id')) {
                $afterColumn = $this->hasColumn('reservasi', 'request_id') ? 'request_id' : 'id';
                $this->db->exec("ALTER TABLE reservasi ADD COLUMN order_id VARCHAR(30) NULL DEFAULT NULL AFTER {$afterColumn}");
                $this->columnExistsCache['reservasi.order_id'] = true;
                $schemaChanged = true;
            }

            if (!$this->hasColumn('reservasi', 'umkm_path')) {
                $afterColumn = $this->hasColumn('reservasi', 'form_path') ? 'form_path' : 'id_path';
                $this->db->exec("ALTER TABLE reservasi ADD COLUMN umkm_path VARCHAR(255) NULL DEFAULT NULL AFTER {$afterColumn}");
                $this->columnExistsCache['reservasi.umkm_path'] = true;
                $schemaChanged = true;
            }

            $this->backfillReservationCodes();
        } catch (Throwable $e) {
            // Keep the app running even if schema changes are not allowed.
        }
    }

    private function backfillReservationCodes(): void
    {
        if (!$this->hasColumn('reservasi', 'request_id') || !$this->hasColumn('reservasi', 'order_id')) {
            return;
        }

        $statusSelectSql = $this->getReservationStatusSelectSql('r', 'sr');
        $statusJoinSql = $this->getReservationStatusJoinSql('r', 'sr');
        $rows = $this->db->query("
            SELECT
                r.id,
                r.created_at,
                {$statusSelectSql},
                r.request_id,
                r.order_id
            FROM reservasi r
            {$statusJoinSql}
            ORDER BY r.created_at ASC, r.id ASC
        ")->fetchAll();

        if ($rows === []) {
            return;
        }

        $requestSequence = 0;
        $orderSequence = 0;
        $update = $this->db->prepare("
            UPDATE reservasi
            SET request_id = :request_id,
                order_id = :order_id
            WHERE id = :id
        ");

        foreach ($rows as $row) {
            $id = (int) ($row['id'] ?? 0);
            if ($id <= 0) {
                continue;
            }

            $createdAt = trim((string) ($row['created_at'] ?? ''));
            $datePart = $this->formatReservationCodeDate($createdAt);
            $requestCode = trim((string) ($row['request_id'] ?? ''));
            $orderCode = trim((string) ($row['order_id'] ?? ''));
            $status = strtoupper(trim((string) ($row['status'] ?? '')));

            $requestSequence = $this->resolveReservationCodeSequence($requestCode, $requestSequence);
            $expectedRequestCode = $this->buildReservationCode(
                self::REQUEST_CODE_PREFIX,
                $datePart,
                $requestSequence
            );

            $expectedOrderCode = null;
            if ($this->isApprovedReservationStatus($status)) {
                $orderSequence = $this->resolveReservationCodeSequence($orderCode, $orderSequence);
                $expectedOrderCode = $this->buildReservationCode(
                    self::ORDER_CODE_PREFIX,
                    $datePart,
                    $orderSequence
                );
            }

            if ($requestCode === $expectedRequestCode && $orderCode === ($expectedOrderCode ?? '')) {
                continue;
            }

            $update->execute([
                ':request_id' => $expectedRequestCode,
                ':order_id' => $expectedOrderCode,
                ':id' => $id,
            ]);
        }
    }

    public function getActiveSessions(): array
    {
        return array_map(function (array $preset): array {
            return [
                'id' => $preset['id'],
                'label' => $preset['label'],
                'display_name' => $preset['label'],
                'start_time' => $preset['start_time'],
                'end_time' => $preset['end_time'],
                'is_custom' => $preset['is_custom'],
            ];
        }, self::SESSION_PRESETS);
    }

    public function getSessionOptionIdByTimes(?string $startTime, ?string $endTime): string
    {
        $normalizedStart = $this->normalizeTimeValue($startTime);
        $normalizedEnd = $this->normalizeTimeValue($endTime);

        if ($normalizedStart === null || $normalizedEnd === null) {
            return '';
        }

        foreach (self::SESSION_PRESETS as $preset) {
            if ($preset['is_custom']) {
                continue;
            }

            if ($preset['start_time'] === $normalizedStart && $preset['end_time'] === $normalizedEnd) {
                return $preset['id'];
            }
        }

        return 'lainnya';
    }

    public function resolveSessionSelection(string $sessionOptionId, ?string $startTime = null, ?string $endTime = null): ?array
    {
        $normalizedOptionId = $this->normalizeSessionOptionId($sessionOptionId);
        $normalizedStart = $this->normalizeTimeValue($startTime);
        $normalizedEnd = $this->normalizeTimeValue($endTime);

        if ($normalizedOptionId !== 'lainnya') {
            $preset = $this->findSessionPresetById($normalizedOptionId);
            if ($preset === null) {
                return null;
            }

            return $this->buildSessionSelectionResult(
                $preset['id'],
                $preset['label'],
                (string) $preset['start_time'],
                (string) $preset['end_time'],
                (int) $preset['session_count'],
                (int) $preset['hour_count'],
                false
            );
        }

        if (!$this->isValidTimeRange($normalizedStart, $normalizedEnd)) {
            return null;
        }

        $matchedPresetId = $this->getSessionOptionIdByTimes($normalizedStart, $normalizedEnd);
        if ($matchedPresetId !== '' && $matchedPresetId !== 'lainnya') {
            return $this->resolveSessionSelection($matchedPresetId);
        }

        $durationHours = $this->calculateRoundedDurationHours($normalizedStart, $normalizedEnd);

        return $this->buildSessionSelectionResult(
            'lainnya',
            'Lainnya (' . $this->formatTimeForDisplay($normalizedStart) . ' - ' . $this->formatTimeForDisplay($normalizedEnd) . ')',
            $normalizedStart,
            $normalizedEnd,
            1,
            $durationHours,
            true
        );
    }

    public function calculateReservationPricing(array $building, array $sessionSelection): array
    {
        $perHourUnitPrice = (float) ($building['perhour_price'] ?? 0);
        $hourCount = $this->calculateRoundedDurationHours(
            (string) ($sessionSelection['start_time'] ?? ''),
            (string) ($sessionSelection['end_time'] ?? '')
        );

        return [
            'hour_count' => $hourCount,
            'hour_price' => $perHourUnitPrice,
            'perhour_price' => $perHourUnitPrice,
            'total_price' => $hourCount * $perHourUnitPrice,
        ];
    }

    public function getActiveEvents(): array
    {
        return $this->hydrateReservationStatuses(
            $this->rows(
                $this->table('acara')
                    ->select('id', 'event_name')
                    ->where('status', 'AKTIF')
                    ->orderBy('event_name')
                    ->get()
            )
        );
    }

    public function findActiveBuildingById(int $buildingId): ?array
    {
        $perHourPriceSql = $this->getAliasedColumnSql('gedung', 'g', 'perhour_price', 'overtime_price', '0');
        $sessionPriceSql = $this->getSelectColumnOrDefaultSql('gedung', 'g', 'session_price', '0', 'session_price');

        return $this->row(
            $this->table('gedung as g')
                ->select([
                    'g.id',
                    'g.building_name',
                    'g.address',
                    'g.district_id',
                    'g.subdistrict_id',
                    'g.capacity',
                    'k.district',
                    'w.region',
                    'kl.subdistrict',
                ])
                ->selectRaw($sessionPriceSql)
                ->selectRaw($perHourPriceSql)
                ->leftJoin('kecamatan as k', 'k.id', '=', 'g.district_id')
                ->leftJoin('kelurahan as kl', 'kl.id', '=', 'g.subdistrict_id')
                ->leftJoin('wilayah as w', 'w.region', '=', 'k.region')
                ->where('g.id', $buildingId)
                ->where('g.status', 'AKTIF')
                ->first()
        );
    }

    public function findActiveEventById(int $eventId): ?array
    {
        return $this->row(
            $this->table('acara')
                ->select('id', 'event_name')
                ->where('id', $eventId)
                ->where('status', 'AKTIF')
                ->first()
        );
    }

    public function hasScheduleConflict(
        int $buildingId,
        string $startDate,
        string $endDate,
        string $startTime,
        string $endTime,
        ?int $excludeId = null
    ): bool {
        $excludedStatuses = $this->normalizeStatusesForQuery([
            'BATAL',
            'DIBATALKAN PEMOHON',
            'TOLAK',
            'DITOLAK',
            'PERMOHONAN DITOLAK',
        ]);
        $excludedPlaceholders = [];
        $params = [];

        foreach ($excludedStatuses as $index => $status) {
            $placeholder = ':excluded_status_' . $index;
            $excludedPlaceholders[] = $placeholder;
            $params[$placeholder] = $status;
        }

        $statusJoinSql = $this->getReservationStatusJoinSql('r', 'sr');
        $statusFilterSql = $this->getReservationStatusFilterSql('r', 'sr');
        $sql = "
            SELECT COUNT(*) AS total
            FROM reservasi r
            {$statusJoinSql}
            WHERE r.building_id = :building_id
              AND {$statusFilterSql} NOT IN (" . implode(', ', $excludedPlaceholders) . ")
              AND :start_date <= r.end_date
              AND :end_date >= r.start_date
              AND COALESCE(r.start_time, '00:00:00') < :requested_end_time
              AND COALESCE(r.end_time, '23:59:59') > :requested_start_time
        ";

        if ($excludeId !== null && $excludeId > 0) {
            $sql .= " AND r.id <> :exclude_id";
        }

        $stmt = $this->db->prepare($sql);

        $params += [
            ':building_id' => $buildingId,
            ':start_date' => $startDate,
            ':end_date' => $endDate,
            ':requested_start_time' => $this->normalizeTimeValue($startTime) ?? '00:00:00',
            ':requested_end_time' => $this->normalizeTimeValue($endTime) ?? '23:59:59',
        ];

        if ($excludeId !== null && $excludeId > 0) {
            $params[':exclude_id'] = $excludeId;
        }

        $stmt->execute($params);

        return (int) ($stmt->fetch()['total'] ?? 0) > 0;
    }

    public function create(array $data): bool
    {
        $status = trim((string) ($data['status'] ?? ''));
        if ($status === '') {
            $status = 'RESERVASI BARU';
        }
        $status = reservation_status_storage_value($status);

        $payloadData = [
            'request_id' => $data['request_id'] ?? $this->generateReservationCode('request_id', self::REQUEST_CODE_PREFIX),
            'order_id' => $data['order_id'] ?? null,
            'user_id' => $data['user_id'] ?? null,
            'district_id' => $data['district_id'] ?? null,
            'building_id' => $data['building_id'] ?? null,
            'start_date' => $data['start_date'] ?? null,
            'end_date' => $data['end_date'] ?? null,
            'event_id' => $data['event_id'] ?? null,
            'est_person' => $data['est_person'] ?? null,
            'umkm_id' => $data['umkm_id'] ?? null,
            'start_time' => $this->normalizeTimeValue($data['start_time'] ?? null),
            'end_time' => $this->normalizeTimeValue($data['end_time'] ?? null),
            'hour_count' => $data['hour_count'] ?? 0,
            'total_price' => $data['total_price'] ?? 0,
            'return_form' => $data['return_form'] ?? 0,
            'id_path' => $data['id_path'] ?? null,
            'form_path' => $data['form_path'] ?? null,
            'umkm_path' => $data['umkm_path'] ?? null,
            'notes' => $data['notes'] ?? null,
        ];
        $this->applyStatusPayload($payloadData, $status);

        $payload = $this->filterExistingColumns('reservasi', $payloadData);

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

    public function find(int $id): ?array
    {
        return $this->hydrateReservationStatus(
            $this->row(
                $this->query()
                    ->where('id', $id)
                    ->first()
            )
        );
    }

    public function byUser(int $userId): array
    {
        return $this->hydrateReservationStatuses(
            $this->rows(
                $this->query()
                    ->where('user_id', $userId)
                    ->orderByDesc('created_at')
                    ->orderByDesc('id')
                    ->get()
            )
        );
    }

    public function findByUserId(int $reservationId, int $userId): ?array
    {
        $this->ensurePendingApprovalStatusesSynchronized();

        return $this->hydrateReservationStatus(
            $this->row(
                $this->query()
                    ->where('id', $reservationId)
                    ->where('user_id', $userId)
                    ->first()
            )
        );
    }

    public function byUserDetailed(int $userId): array
    {
        $this->ensurePendingApprovalStatusesSynchronized();

        $perHourPriceExpr = $this->getColumnExpressionSql('gedung', 'g', 'perhour_price', 'overtime_price', '0');
        $hourCountSql = $this->getExtraHourCountSql('r');
        $paymentProofSelectSql = $this->getPaymentProofSelectSql('pay');
        $paymentProofJoinSql = $this->getLatestPaymentProofJoinSql('r', 'pay');
        $statusSelectSql = $this->getReservationStatusSelectSql('r', 'sr');
        $statusJoinSql = $this->getReservationStatusJoinSql('r', 'sr');
        $notesSelectSql = $this->getSelectColumnOrDefaultSql('reservasi', 'r', 'notes', "''", 'notes');
        $stmt = $this->db->prepare("
            SELECT
                r.id,
                r.district_id,
                r.building_id,
                r.start_date,
                r.end_date,
                r.event_id,
                r.est_person,
                " . $this->getSelectColumnOrDefaultSql('reservasi', 'r', 'start_time', 'NULL', 'start_time') . ",
                " . $this->getSelectColumnOrDefaultSql('reservasi', 'r', 'end_time', 'NULL', 'end_time') . ",
                " . $this->getReservationCodeSelectSql('r', 'request_id', self::REQUEST_CODE_PREFIX) . ",
                " . $this->getReservationCodeSelectSql('r', 'order_id', self::ORDER_CODE_PREFIX) . ",
                {$this->getSessionCountSql('r')} AS session_count,
                " . $this->getSelectColumnOrDefaultSql('gedung', 'g', 'session_price', '0', 'session_price') . ",
                {$hourCountSql} AS hour_count,
                {$perHourPriceExpr} AS perhour_price,
                ({$hourCountSql} * {$perHourPriceExpr}) AS total_price,
                {$statusSelectSql},
                r.id_path,
                " . $this->getSelectColumnOrDefaultSql('reservasi', 'r', 'form_path', 'NULL', 'form_path') . ",
                " . $this->getSelectColumnOrDefaultSql('reservasi', 'r', 'umkm_path', 'NULL', 'umkm_path') . ",
                {$paymentProofSelectSql},
                {$notesSelectSql},
                r.created_at,
                u.username,
                u.name AS user_name,
                u.address AS user_address,
                u.phone AS user_phone,
                u.nik AS user_nik,
                g.building_name,
                g.address AS building_address,
                fg.image_path AS building_photo,
                k.district,
                w.region,
                a.event_name,
                um.umkm_name,
                um.owner AS umkm_owner,
                um.address AS umkm_address,
                um.pic_path AS umkm_photo,
                p.type AS umkm_type,
                {$this->getSessionDisplayNameSql('r')} AS session_display_name
            FROM reservasi r
            {$statusJoinSql}
            LEFT JOIN user u
                ON u.id = r.user_id
            INNER JOIN gedung g
                ON g.id = r.building_id
            LEFT JOIN (
                SELECT
                    building_id,
                    MIN(image_path) AS image_path
                FROM foto_gedung
                WHERE is_thumbnail = 1
                GROUP BY building_id
            ) fg
                ON fg.building_id = g.id
            LEFT JOIN kecamatan k
                ON k.id = COALESCE(r.district_id, g.district_id)
            LEFT JOIN wilayah w
                ON w.region = k.region
            LEFT JOIN acara a
                ON a.id = r.event_id
            LEFT JOIN umkm um
                ON um.id = r.umkm_id
            LEFT JOIN produk p
                ON p.id = um.product_id
            {$paymentProofJoinSql}
            WHERE r.user_id = :user_id
            ORDER BY r.created_at DESC, r.id DESC
        ");

        $stmt->execute([
            ':user_id' => $userId,
        ]);

        return $stmt->fetchAll();
    }

    public function allDetailed(?int $districtId = null, array $statuses = []): array
    {
        $this->ensurePendingApprovalStatusesSynchronized();

        $perHourPriceExpr = $this->getColumnExpressionSql('gedung', 'g', 'perhour_price', 'overtime_price', '0');
        $hourCountSql = $this->getExtraHourCountSql('r');
        $paymentProofSelectSql = $this->getPaymentProofSelectSql('pay');
        $paymentProofJoinSql = $this->getLatestPaymentProofJoinSql('r', 'pay');
        $statusSelectSql = $this->getReservationStatusSelectSql('r', 'sr');
        $statusJoinSql = $this->getReservationStatusJoinSql('r', 'sr');
        $statusFilterSql = $this->getReservationStatusFilterSql('r', 'sr');
        $notesSelectSql = $this->getSelectColumnOrDefaultSql('reservasi', 'r', 'notes', "''", 'notes');
        $sql = "
            SELECT
                r.id,
                r.user_id,
                r.district_id,
                r.building_id,
                r.umkm_id,
                r.start_date,
                r.end_date,
                r.event_id,
                r.est_person,
                " . $this->getSelectColumnOrDefaultSql('reservasi', 'r', 'start_time', 'NULL', 'start_time') . ",
                " . $this->getSelectColumnOrDefaultSql('reservasi', 'r', 'end_time', 'NULL', 'end_time') . ",
                " . $this->getReservationCodeSelectSql('r', 'request_id', self::REQUEST_CODE_PREFIX) . ",
                " . $this->getReservationCodeSelectSql('r', 'order_id', self::ORDER_CODE_PREFIX) . ",
                {$this->getSessionCountSql('r')} AS session_count,
                " . $this->getSelectColumnOrDefaultSql('gedung', 'g', 'session_price', '0', 'session_price') . ",
                {$hourCountSql} AS hour_count,
                {$perHourPriceExpr} AS perhour_price,
                ({$hourCountSql} * {$perHourPriceExpr}) AS total_price,
                {$statusSelectSql},
                r.id_path,
                " . $this->getSelectColumnOrDefaultSql('reservasi', 'r', 'form_path', 'NULL', 'form_path') . ",
                " . $this->getSelectColumnOrDefaultSql('reservasi', 'r', 'umkm_path', 'NULL', 'umkm_path') . ",
                {$paymentProofSelectSql},
                {$notesSelectSql},
                r.created_at,
                u.username,
                u.name AS user_name,
                u.address AS user_address,
                u.phone AS user_phone,
                u.nik AS user_nik,
                g.building_name,
                g.address AS building_address,
                g.capacity,
                k.district,
                w.region,
                a.event_name,
                um.umkm_name,
                um.owner AS umkm_owner,
                um.address AS umkm_address,
                p.type AS umkm_type,
                {$this->getSessionDisplayNameSql('r')} AS session_display_name
            FROM reservasi r
            {$statusJoinSql}
            LEFT JOIN user u
                ON u.id = r.user_id
            LEFT JOIN gedung g
                ON g.id = r.building_id
            LEFT JOIN kecamatan k
                ON k.id = COALESCE(r.district_id, g.district_id)
            LEFT JOIN wilayah w
                ON w.region = k.region
            LEFT JOIN acara a
                ON a.id = r.event_id
            LEFT JOIN umkm um
                ON um.id = r.umkm_id
            LEFT JOIN produk p
                ON p.id = um.product_id
            {$paymentProofJoinSql}
        ";

        $params = [];
        $conditions = [];

        if ($districtId !== null && $districtId > 0) {
            $conditions[] = "COALESCE(r.district_id, g.district_id) = :district_id";
            $params[':district_id'] = $districtId;
        }

        $normalizedStatuses = $this->normalizeStatusesForQuery($statuses);
        if ($normalizedStatuses !== []) {
            $placeholders = [];

            foreach ($normalizedStatuses as $index => $status) {
                $placeholder = ':status_' . $index;
                $placeholders[] = $placeholder;
                $params[$placeholder] = $status;
            }

            $conditions[] = $statusFilterSql . ' IN (' . implode(', ', $placeholders) . ')';
        }

        if ($conditions !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }

        $sql .= " ORDER BY r.created_at DESC, r.id DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll() ?: [];
    }

    public function findDetailed(int $id): ?array
    {
        $this->ensurePendingApprovalStatusesSynchronized();

        $perHourPriceExpr = $this->getColumnExpressionSql('gedung', 'g', 'perhour_price', 'overtime_price', '0');
        $hourCountSql = $this->getExtraHourCountSql('r');
        $paymentProofSelectSql = $this->getPaymentProofSelectSql('pay');
        $paymentProofJoinSql = $this->getLatestPaymentProofJoinSql('r', 'pay');
        $statusSelectSql = $this->getReservationStatusSelectSql('r', 'sr');
        $statusJoinSql = $this->getReservationStatusJoinSql('r', 'sr');
        $notesSelectSql = $this->getSelectColumnOrDefaultSql('reservasi', 'r', 'notes', "''", 'notes');
        $stmt = $this->db->prepare("
            SELECT
                r.id,
                r.user_id,
                r.district_id,
                r.building_id,
                r.start_date,
                r.end_date,
                r.event_id,
                r.est_person,
                " . $this->getSelectColumnOrDefaultSql('reservasi', 'r', 'start_time', 'NULL', 'start_time') . ",
                " . $this->getSelectColumnOrDefaultSql('reservasi', 'r', 'end_time', 'NULL', 'end_time') . ",
                " . $this->getReservationCodeSelectSql('r', 'request_id', self::REQUEST_CODE_PREFIX) . ",
                " . $this->getReservationCodeSelectSql('r', 'order_id', self::ORDER_CODE_PREFIX) . ",
                {$this->getSessionCountSql('r')} AS session_count,
                " . $this->getSelectColumnOrDefaultSql('gedung', 'g', 'session_price', '0', 'session_price') . ",
                {$hourCountSql} AS hour_count,
                {$perHourPriceExpr} AS perhour_price,
                ({$hourCountSql} * {$perHourPriceExpr}) AS total_price,
                {$statusSelectSql},
                r.id_path,
                " . $this->getSelectColumnOrDefaultSql('reservasi', 'r', 'form_path', 'NULL', 'form_path') . ",
                " . $this->getSelectColumnOrDefaultSql('reservasi', 'r', 'umkm_path', 'NULL', 'umkm_path') . ",
                {$paymentProofSelectSql},
                {$notesSelectSql},
                r.created_at,
                u.username,
                u.name AS user_name,
                u.address AS user_address,
                u.phone AS user_phone,
                u.nik AS user_nik,
                g.building_name,
                g.address AS building_address,
                g.capacity,
                k.district,
                w.region,
                a.event_name,
                um.umkm_name,
                um.owner AS umkm_owner,
                um.address AS umkm_address,
                p.type AS umkm_type,
                {$this->getSessionDisplayNameSql('r')} AS session_display_name
            FROM reservasi r
            {$statusJoinSql}
            LEFT JOIN user u
                ON u.id = r.user_id
            LEFT JOIN gedung g
                ON g.id = r.building_id
            LEFT JOIN kecamatan k
                ON k.id = COALESCE(r.district_id, g.district_id)
            LEFT JOIN wilayah w
                ON w.region = k.region
            LEFT JOIN acara a
                ON a.id = r.event_id
            LEFT JOIN umkm um
                ON um.id = r.umkm_id
            LEFT JOIN produk p
                ON p.id = um.product_id
            {$paymentProofJoinSql}
            WHERE r.id = :id
            LIMIT 1
        ");

        $stmt->execute([
            ':id' => $id,
        ]);

        $reservation = $stmt->fetch();

        return $this->hydrateReservationStatus($reservation ?: null);
    }

    public function updateByUserId(int $reservationId, int $userId, array $data): bool
    {
        $payloadData = [
            'district_id' => $data['district_id'] ?? null,
            'building_id' => $data['building_id'] ?? null,
            'start_date' => $data['start_date'] ?? null,
            'end_date' => $data['end_date'] ?? null,
            'event_id' => $data['event_id'] ?? null,
            'umkm_id' => $data['umkm_id'] ?? null,
            'est_person' => $data['est_person'] ?? null,
            'start_time' => $this->normalizeTimeValue($data['start_time'] ?? null),
            'end_time' => $this->normalizeTimeValue($data['end_time'] ?? null),
            'hour_count' => $data['hour_count'] ?? 0,
            'total_price' => $data['total_price'] ?? 0,
            'id_path' => $data['id_path'] ?? null,
            'form_path' => $data['form_path'] ?? null,
            'umkm_path' => $data['umkm_path'] ?? null,
            'notes' => $data['notes'] ?? null,
        ];

        if (array_key_exists('status', $data)) {
            $status = trim((string) $data['status']);
            $this->applyStatusPayload($payloadData, $status !== '' ? $status : 'RESERVASI BARU');
        }

        if (array_key_exists('return_form', $data)) {
            $payloadData['return_form'] = $data['return_form'];
        }

        $payload = $this->filterExistingColumns('reservasi', $payloadData);

        if ($payload === []) {
            return false;
        }

        $assignments = [];
        foreach (array_keys($payload) as $column) {
            $assignments[] = $column . ' = :' . $column;
        }

        $sql = "
            UPDATE reservasi
            SET " . implode(', ', $assignments) . "
            WHERE id = :reservation_id
              AND user_id = :user_id
        ";

        $params = $payload;
        $params['reservation_id'] = $reservationId;
        $params['user_id'] = $userId;

        $stmt = $this->db->prepare($sql);

        return $stmt->execute($params);
    }

    public function deleteByUserId(int $reservationId, int $userId): bool
    {
        $stmt = $this->db->prepare("
            DELETE FROM reservasi
            WHERE id = :id
              AND user_id = :user_id
        ");

        return $stmt->execute([
            ':id' => $reservationId,
            ':user_id' => $userId,
        ]);
    }

    public function cancelByUserId(int $reservationId, int $userId, ?string $notes = null): bool
    {
        $assignments = [];
        $params = [
            ':reservation_id' => $reservationId,
            ':user_id' => $userId,
        ];
        $this->addStatusAssignment($assignments, $params, 'BATAL');

        if ($notes !== null && $this->hasColumn('reservasi', 'notes')) {
            $assignments[] = 'notes = :notes';
            $params[':notes'] = $notes;
        }

        if ($this->hasColumn('reservasi', 'return_form')) {
            $assignments[] = 'return_form = :return_form';
            $params[':return_form'] = 0;
        }

        $stmt = $this->db->prepare("
            UPDATE reservasi
            SET " . implode(', ', $assignments) . "
            WHERE id = :reservation_id
              AND user_id = :user_id
        ");

        return $stmt->execute($params);
    }

    public function deleteById(int $reservationId): bool
    {
        $stmt = $this->db->prepare("
            DELETE FROM reservasi
            WHERE id = :id
        ");

        return $stmt->execute([
            ':id' => $reservationId,
        ]);
    }

    public function updateStatus(int $id, string $status): bool
    {
        return $this->updateStatusWithMetadata($id, $status);
    }

    public function updateStatusWithMetadata(
        int $id,
        string $status,
        ?string $notes = null,
        ?int $chance = null,
        ?int $returnForm = null
    ): bool
    {
        $storedStatus = reservation_status_storage_value($status);
        $assignments = [];
        $params = [':id' => $id];
        $this->addStatusAssignment($assignments, $params, $status);

        if ($notes !== null) {
            $assignments[] = 'notes = :notes';
            $params[':notes'] = $notes;
        }

        if ($chance !== null && $this->hasColumn('reservasi', 'chance')) {
            $assignments[] = 'chance = :chance';
            $params[':chance'] = $chance;
        }

        if ($returnForm !== null && $this->hasColumn('reservasi', 'return_form')) {
            $assignments[] = 'return_form = :return_form';
            $params[':return_form'] = $returnForm;
        }

        if ($this->isApprovedReservationStatus($storedStatus) && $this->hasColumn('reservasi', 'order_id')) {
            $codeRow = $this->db->prepare("
                SELECT created_at, order_id
                FROM reservasi
                WHERE id = :id
                LIMIT 1
            ");
            $codeRow->execute([
                ':id' => $id,
            ]);

            $codeRowData = $codeRow->fetch() ?: [];
            $existingOrderId = trim((string) ($codeRowData['order_id'] ?? ''));

            if ($existingOrderId === '') {
                $assignments[] = 'order_id = :order_id';
                $params[':order_id'] = $this->generateReservationCode(
                    'order_id',
                    self::ORDER_CODE_PREFIX,
                    (string) ($codeRowData['created_at'] ?? null),
                    self::APPROVED_CODE_STATUSES
                );
            }
        }

        $stmt = $this->db->prepare("
            UPDATE reservasi
            SET " . implode(', ', $assignments) . "
            WHERE id = :id
        ");

        return $stmt->execute($params);
    }

    private function buildSessionSelectionResult(
        string $id,
        string $label,
        string $startTime,
        string $endTime,
        int $sessionCount,
        int $hourCount,
        bool $isCustom
    ): array {
        return [
            'id' => $id,
            'label' => $label,
            'display_name' => $label,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'session_count' => $sessionCount,
            'hour_count' => $hourCount,
            'is_custom' => $isCustom,
        ];
    }

    private function ensurePendingApprovalStatusesSynchronized(): void
    {
        if (self::$pendingApprovalStatusesSynchronized) {
            return;
        }

        self::$pendingApprovalStatusesSynchronized = true;

        try {
            $this->synchronizeLegacyStatusAliases();
            $this->synchronizeLegacyReturnedStatuses();
            $this->synchronizeMissingPendingStatuses();
            $this->cancelOverdueApprovedReservations();
            $this->cancelApprovedReservationsForPaidSlots();
            $this->completePastPaidReservations();
        } catch (Throwable $e) {
            // Keep read flows working even if auto-cancel synchronization fails.
        }
    }

    private function synchronizeMissingPendingStatuses(): int
    {
        if (!$this->hasColumn('reservasi', 'status')) {
            return 0;
        }

        $stmt = $this->db->prepare("
            UPDATE reservasi
            SET status = :status
            WHERE status IS NULL
               OR TRIM(status) = ''
        ");

        $stmt->execute([
            ':status' => reservation_status_storage_value('RESERVASI BARU'),
        ]);

        return $stmt->rowCount();
    }

    private function synchronizeLegacyStatusAliases(): int
    {
        if (!$this->hasColumn('reservasi', 'status')) {
            return 0;
        }

        $updates = [
            ['aliases' => ['PROSES'], 'status' => 'RESERVASI BARU'],
            ['aliases' => ['VERIFIKASI'], 'status' => 'PROSES VERIFIKASI'],
            ['aliases' => ['SETUJU', 'DISETUJUI'], 'status' => 'MENUNGGU PEMBAYARAN'],
            ['aliases' => ['TOLAK', 'DITOLAK'], 'status' => 'PERMOHONAN DITOLAK'],
            ['aliases' => ['BATAL'], 'status' => 'DIBATALKAN PEMOHON'],
            ['aliases' => ['LUNAS'], 'status' => 'PEMBAYARAN LUNAS'],
            ['aliases' => ['SELESAI'], 'status' => 'ACARA SELESAI'],
        ];

        $updatedRows = 0;

        foreach ($updates as $update) {
            $aliases = array_map(static function (string $alias): string {
                return "'" . $alias . "'";
            }, $update['aliases']);

            $stmt = $this->db->prepare("
                UPDATE reservasi
                SET status = :status
                WHERE UPPER(TRIM(status)) IN (" . implode(', ', $aliases) . ")
            ");

            $stmt->execute([
                ':status' => reservation_status_storage_value($update['status']),
            ]);

            $updatedRows += $stmt->rowCount();
        }

        return $updatedRows;
    }

    private function synchronizeLegacyReturnedStatuses(): int
    {
        if (!$this->hasColumn('reservasi', 'status')) {
            return 0;
        }

        $legacyReturnedStatuses = "'KEMBALI', 'BERKAS TIDAK LENGKAP', 'BERKAS TIDAK SESUAI'";
        $updatedRows = 0;
        $hasOrderColumn = $this->hasColumn('reservasi', 'order_id');
        $hasUmkmPathColumn = $this->hasColumn('reservasi', 'umkm_path');

        if ($hasOrderColumn) {
            $stmt = $this->db->prepare("
                UPDATE reservasi
                SET status = :status
                WHERE UPPER(TRIM(status)) IN ({$legacyReturnedStatuses})
                  AND TRIM(COALESCE(order_id, '')) <> ''
            ");

            $stmt->execute([
                ':status' => reservation_status_storage_value('BERKAS PEMBAYARAN TIDAK SESUAI'),
            ]);

            $updatedRows += $stmt->rowCount();
        }

        if ($hasUmkmPathColumn) {
            $verificationConditions = [];
            if ($hasOrderColumn) {
                $verificationConditions[] = "TRIM(COALESCE(order_id, '')) = ''";
            }
            $verificationConditions[] = "TRIM(COALESCE(umkm_path, '')) <> ''";

            $stmt = $this->db->prepare("
                UPDATE reservasi
                SET status = :status
                WHERE UPPER(TRIM(status)) IN ({$legacyReturnedStatuses})
                  AND " . implode("\n                  AND ", $verificationConditions) . "
            ");

            $stmt->execute([
                ':status' => reservation_status_storage_value('BERKAS VERIFIKASI TIDAK SESUAI'),
            ]);

            $updatedRows += $stmt->rowCount();
        }

        $reservationConditions = [];
        if ($hasOrderColumn) {
            $reservationConditions[] = "TRIM(COALESCE(order_id, '')) = ''";
        }
        if ($hasUmkmPathColumn) {
            $reservationConditions[] = "TRIM(COALESCE(umkm_path, '')) = ''";
        }

        $stmt = $this->db->prepare("
            UPDATE reservasi
            SET status = :status
            WHERE UPPER(TRIM(status)) IN ({$legacyReturnedStatuses})
            " . ($reservationConditions !== []
                ? "  AND " . implode("\n              AND ", $reservationConditions)
                : '') . "
        ");

        $stmt->execute([
            ':status' => reservation_status_storage_value('BERKAS RESERVASI TIDAK SESUAI'),
        ]);

        $updatedRows += $stmt->rowCount();

        return $updatedRows;
    }

    private function cancelOverdueApprovedReservations(): int
    {
        if (!$this->hasColumn('reservasi', 'status')) {
            return 0;
        }

        $assignments = ['status = :status'];
        $params = [
            ':status' => reservation_status_storage_value('PERMOHONAN DITOLAK'),
            ':current_time' => (new DateTimeImmutable('now'))->format('Y-m-d H:i:s'),
        ];

        if ($this->hasColumn('reservasi', 'notes')) {
            $assignments[] = 'notes = :notes';
            $params[':notes'] = 'Permohonan dibatalkan karena tidak melakukan pembayaran sampai batas waktu sudah yang ditentukan (VA Kedaluwarsa)';
        }

        if ($this->hasColumn('reservasi', 'return_form')) {
            $assignments[] = 'return_form = :return_form';
            $params[':return_form'] = 0;
        }

        $stmt = $this->db->prepare("
            UPDATE reservasi
            SET " . implode(', ', $assignments) . "
            WHERE UPPER(TRIM(status)) IN ('SETUJU', 'DISETUJUI', 'MENUNGGU PEMBAYARAN')
              AND start_date IS NOT NULL
              AND :current_time >= DATE_ADD(DATE_SUB(start_date, INTERVAL 6 DAY), INTERVAL 1 SECOND)
        ");

        $stmt->execute($params);

        return $stmt->rowCount();
    }

    private function cancelApprovedReservationsForPaidSlots(): int
    {
        if (!$this->hasColumn('reservasi', 'status')) {
            return 0;
        }

        $assignments = ['pending.status = :status'];
        $params = [
            ':status' => reservation_status_storage_value('BATAL'),
        ];

        if ($this->hasColumn('reservasi', 'notes')) {
            $assignments[] = 'pending.notes = :notes';
            $params[':notes'] = 'Reservasi dibatalkan otomatis karena sesi yang sama sudah dibayar lebih dahulu oleh pemesan lain.';
        }

        if ($this->hasColumn('reservasi', 'return_form')) {
            $assignments[] = 'pending.return_form = :return_form';
            $params[':return_form'] = 0;
        }

        $stmt = $this->db->prepare("
            UPDATE reservasi pending
            INNER JOIN reservasi paid
                ON paid.id <> pending.id
               AND paid.building_id = pending.building_id
               AND paid.start_date <= pending.end_date
               AND paid.end_date >= pending.start_date
               AND COALESCE(paid.start_time, '00:00:00') < COALESCE(pending.end_time, '23:59:59')
               AND COALESCE(paid.end_time, '23:59:59') > COALESCE(pending.start_time, '00:00:00')
               AND UPPER(TRIM(paid.status)) IN ('LUNAS', 'PEMBAYARAN LUNAS', 'SELESAI', 'ACARA SELESAI')
            SET " . implode(', ', $assignments) . "
            WHERE UPPER(TRIM(pending.status)) IN ('SETUJU', 'DISETUJUI', 'MENUNGGU PEMBAYARAN', 'CEK PEMBAYARAN', 'BERKAS PEMBAYARAN TIDAK SESUAI')
        ");

        $stmt->execute($params);

        return $stmt->rowCount();
    }

    private function completePastPaidReservations(): int
    {
        if (!$this->hasColumn('reservasi', 'status')) {
            return 0;
        }

        $assignments = ['status = :status'];
        $params = [
            ':status' => reservation_status_storage_value('ACARA SELESAI'),
            ':today' => (new DateTimeImmutable('today'))->format('Y-m-d'),
        ];

        $stmt = $this->db->prepare("
            UPDATE reservasi
            SET " . implode(', ', $assignments) . "
            WHERE UPPER(TRIM(status)) IN ('LUNAS', 'PEMBAYARAN LUNAS')
              AND end_date IS NOT NULL
              AND end_date < :today
        ");

        $stmt->execute($params);

        return $stmt->rowCount();
    }

    private function findSessionPresetById(string $sessionOptionId): ?array
    {
        $normalizedOptionId = $this->normalizeSessionOptionId($sessionOptionId);

        foreach (self::SESSION_PRESETS as $preset) {
            if ($preset['id'] === $normalizedOptionId) {
                return $preset;
            }
        }

        return null;
    }

    private function normalizeSessionOptionId(string $sessionOptionId): string
    {
        $normalized = strtolower(trim($sessionOptionId));

        return match ($normalized) {
            '1hari', '1_hari', '1-hari', 'full_day', 'full-day', 'fullday', 'sehari' => 'full_day',
            'morning', 'sesi1', 'sesi_1', 'session1', 'session_1' => 'pagi',
            'night', 'sesi2', 'sesi_2', 'session2', 'session_2' => 'malam',
            'custom', 'other' => 'lainnya',
            default => $normalized,
        };
    }

    private function normalizeTimeValue(?string $time): ?string
    {
        $time = trim((string) $time);
        if ($time === '') {
            return null;
        }

        $parsed = DateTime::createFromFormat('H:i:s', $time);
        if ($parsed instanceof DateTime) {
            return $parsed->format('H:i:s');
        }

        $parsed = DateTime::createFromFormat('H:i', $time);
        if ($parsed instanceof DateTime) {
            return $parsed->format('H:i:s');
        }

        return null;
    }

    private function isValidTimeRange(?string $startTime, ?string $endTime): bool
    {
        $normalizedStart = $this->normalizeTimeValue($startTime);
        $normalizedEnd = $this->normalizeTimeValue($endTime);

        if ($normalizedStart === null || $normalizedEnd === null) {
            return false;
        }

        return $this->toSeconds($normalizedEnd) > $this->toSeconds($normalizedStart);
    }

    private function calculateRoundedDurationHours(?string $startTime, ?string $endTime): int
    {
        $normalizedStart = $this->normalizeTimeValue($startTime);
        $normalizedEnd = $this->normalizeTimeValue($endTime);

        if (!$this->isValidTimeRange($normalizedStart, $normalizedEnd)) {
            return 0;
        }

        $durationSeconds = $this->toSeconds($normalizedEnd) - $this->toSeconds($normalizedStart);

        return (int) ceil($durationSeconds / 3600);
    }

    private function toSeconds(string $time): int
    {
        [$hours, $minutes, $seconds] = array_map('intval', explode(':', $time));

        return ($hours * 3600) + ($minutes * 60) + $seconds;
    }

    private function formatTimeForDisplay(string $time): string
    {
        return str_replace(':', '.', substr($time, 0, 5));
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

    private function getSessionCountSql(string $tableAlias = 'r'): string
    {
        $prefix = $tableAlias !== '' ? $tableAlias . '.' : '';

        return "
            CASE
                WHEN {$prefix}start_time IS NULL OR {$prefix}end_time IS NULL THEN 0
                WHEN TIME({$prefix}start_time) = '08:00:00' AND TIME({$prefix}end_time) = '22:00:00' THEN 2
                ELSE 1
            END
        ";
    }

    private function getExtraHourCountSql(string $tableAlias = 'r'): string
    {
        $prefix = $tableAlias !== '' ? $tableAlias . '.' : '';

        return "
            CASE
                WHEN {$prefix}start_time IS NULL OR {$prefix}end_time IS NULL THEN 0
                WHEN TIME_TO_SEC(TIMEDIFF({$prefix}end_time, {$prefix}start_time)) <= 0 THEN 0
                ELSE CEILING(TIME_TO_SEC(TIMEDIFF({$prefix}end_time, {$prefix}start_time)) / 3600)
            END
        ";
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

    private function applyStatusPayload(array &$payloadData, string $status): void
    {
        if ($this->hasColumn('reservasi', 'status')) {
            $payloadData['status'] = reservation_status_storage_value($status);
            return;
        }

        if ($this->hasColumn('reservasi', 'status_id')) {
            $payloadData['status_id'] = $this->resolveStatusId($status);
        }
    }

    private function addStatusAssignment(
        array &$assignments,
        array &$params,
        string $status,
        string $tableAlias = '',
        string $paramName = 'status'
    ): void {
        $columnPrefix = $tableAlias !== '' ? $tableAlias . '.' : '';

        if ($this->hasColumn('reservasi', 'status')) {
            $assignments[] = $columnPrefix . 'status = :' . $paramName;
            $params[':' . $paramName] = reservation_status_storage_value($status);
            return;
        }

        if ($this->hasColumn('reservasi', 'status_id')) {
            $assignments[] = $columnPrefix . 'status_id = :' . $paramName . '_id';
            $params[':' . $paramName . '_id'] = $this->resolveStatusId($status);
        }
    }

    private function resolveStatusId(string $status): int
    {
        $storageValue = reservation_status_storage_value($status);
        $storageKey = normalize_reservation_status_key($storageValue);
        $statusKey = normalize_reservation_status_key($status);
        $cacheKey = $storageKey !== '' ? $storageKey : $statusKey;

        if ($cacheKey !== '' && isset($this->statusIdCache[$cacheKey])) {
            return $this->statusIdCache[$cacheKey];
        }

        if (!$this->hasTable('status_reservasi')) {
            return 1;
        }

        $stmt = $this->db->prepare("
            SELECT id
            FROM status_reservasi
            WHERE UPPER(TRIM(name)) = :storage_name
               OR UPPER(REPLACE(TRIM(code), '_', ' ')) = :storage_code
               OR UPPER(REPLACE(TRIM(code), '_', ' ')) = :status_code
            LIMIT 1
        ");
        $stmt->execute([
            ':storage_name' => $storageKey,
            ':storage_code' => $storageKey,
            ':status_code' => $statusKey,
        ]);

        $id = (int) ($stmt->fetchColumn() ?: 0);
        if ($id <= 0 && $storageKey !== 'RESERVASI BARU') {
            $id = $this->resolveStatusId('RESERVASI BARU');
        }

        $id = $id > 0 ? $id : 1;
        if ($cacheKey !== '') {
            $this->statusIdCache[$cacheKey] = $id;
        }

        return $id;
    }

    private function getStatusNameById(int $statusId): string
    {
        if ($statusId <= 0 || !$this->hasTable('status_reservasi')) {
            return 'Reservasi Baru';
        }

        if (isset($this->statusNameCache[$statusId])) {
            return $this->statusNameCache[$statusId];
        }

        $stmt = $this->db->prepare("
            SELECT name
            FROM status_reservasi
            WHERE id = :id
            LIMIT 1
        ");
        $stmt->execute([':id' => $statusId]);

        $name = trim((string) ($stmt->fetchColumn() ?: ''));
        $this->statusNameCache[$statusId] = $name !== '' ? $name : 'Reservasi Baru';

        return $this->statusNameCache[$statusId];
    }

    private function hydrateReservationStatus(?array $reservation): ?array
    {
        if ($reservation === null) {
            return null;
        }

        if (!array_key_exists('status', $reservation) || trim((string) $reservation['status']) === '') {
            $reservation['status'] = $this->getStatusNameById((int) ($reservation['status_id'] ?? 0));
        }

        return $reservation;
    }

    private function hydrateReservationStatuses(array $reservations): array
    {
        return array_map(fn (array $reservation): array => $this->hydrateReservationStatus($reservation) ?? $reservation, $reservations);
    }

    private function isApprovedReservationStatus(string $status): bool
    {
        return reservation_status_uses_order_code($status);
    }

    private function formatReservationCodeDate(?string $dateTime): string
    {
        $timestamp = strtotime(trim((string) $dateTime));

        if (!$timestamp) {
            $timestamp = time();
        }

        return date('dmy', $timestamp);
    }

    private function buildReservationCode(string $prefix, string $datePart, int $sequence): string
    {
        return strtoupper(trim($prefix)) . '-' . $datePart . '-' . str_pad((string) max(0, $sequence), 4, '0', STR_PAD_LEFT);
    }

    private function extractReservationCodeSequence(string $code): int
    {
        $normalized = trim($code);
        if ($normalized === '') {
            return 0;
        }

        if (preg_match('/-(\d{4})$/', $normalized, $matches)) {
            return (int) $matches[1];
        }

        return 0;
    }

    private function resolveReservationCodeSequence(string $code, int $currentSequence): int
    {
        $existingSequence = $this->extractReservationCodeSequence($code);
        if ($existingSequence > 0) {
            return max($currentSequence, $existingSequence);
        }

        return $currentSequence + 1;
    }

    private function getLatestReservationCodeSequence(string $column, string $prefix, array $statuses = []): int
    {
        if (!$this->hasColumn('reservasi', $column)) {
            return 0;
        }

        $prefix = strtoupper(trim($prefix));
        $fallbackSql = "CONCAT('{$prefix}-', DATE_FORMAT(r.created_at, '%d%m%y'), '-', LPAD(r.id, 4, '0'))";
        $codeExpression = "COALESCE(NULLIF(r.{$column}, ''), {$fallbackSql})";
        $statusJoinSql = $this->getReservationStatusJoinSql('r', 'sr');
        $statusFilterSql = $this->getReservationStatusFilterSql('r', 'sr');
        $sql = "
            SELECT COALESCE(
                MAX(CAST(SUBSTRING_INDEX({$codeExpression}, '-', -1) AS UNSIGNED)),
                0
            ) AS total
            FROM reservasi r
            {$statusJoinSql}
            WHERE {$codeExpression} LIKE :prefix
        ";

        $params = [
            ':prefix' => $prefix . '-%',
        ];

        $normalizedStatuses = $this->normalizeStatusesForQuery($statuses);
        if ($normalizedStatuses !== []) {
            $placeholders = [];

            foreach ($normalizedStatuses as $index => $status) {
                $placeholder = ':status_' . $index;
                $placeholders[] = $placeholder;
                $params[$placeholder] = $status;
            }

            $sql .= ' AND ' . $statusFilterSql . ' IN (' . implode(', ', $placeholders) . ')';
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return (int) ($stmt->fetchColumn() ?: 0);
    }

    private function generateReservationCode(string $column, string $prefix, ?string $dateTime = null, array $statuses = []): string
    {
        $datePart = $this->formatReservationCodeDate($dateTime);
        $sequence = $this->getLatestReservationCodeSequence($column, $prefix, $statuses) + 1;

        return $this->buildReservationCode($prefix, $datePart, $sequence);
    }

    private function getReservationCodeSelectSql(string $tableAlias, string $column, string $prefix): string
    {
        $prefix = strtoupper(trim($prefix));
        $fallbackSql = "CONCAT('{$prefix}-', DATE_FORMAT({$tableAlias}.created_at, '%d%m%y'), '-', LPAD({$tableAlias}.id, 4, '0'))";

        if ($this->hasColumn('reservasi', $column)) {
            return "COALESCE(NULLIF({$tableAlias}.{$column}, ''), {$fallbackSql}) AS {$column}";
        }

        return "{$fallbackSql} AS {$column}";
    }

    private function filterExistingColumns(string $table, array $data): array
    {
        $filtered = [];

        foreach ($data as $column => $value) {
            if ($this->hasColumn($table, $column)) {
                $filtered[$column] = $value;
            }
        }

        return $filtered;
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

    private function getColumnExpressionSql(
        string $table,
        string $tableAlias,
        string $preferredColumn,
        ?string $legacyColumn = null,
        string $defaultSql = '0'
    ): string {
        $columnName = $this->resolveColumnName($table, $preferredColumn, $legacyColumn);

        if ($columnName === null) {
            return $defaultSql;
        }

        return ($tableAlias !== '' ? $tableAlias . '.' : '') . $columnName;
    }

    private function getAliasedColumnSql(
        string $table,
        string $tableAlias,
        string $preferredColumn,
        ?string $legacyColumn = null,
        string $defaultSql = '0'
    ): string {
        $columnName = $this->resolveColumnName($table, $preferredColumn, $legacyColumn);

        if ($columnName === null) {
            return $defaultSql . ' AS ' . $preferredColumn;
        }

        $expression = ($tableAlias !== '' ? $tableAlias . '.' : '') . $columnName;

        if ($columnName !== $preferredColumn) {
            $expression .= ' AS ' . $preferredColumn;
        }

        return $expression;
    }

    private function getPaymentProofSelectSql(string $tableAlias): string
    {
        if (!$this->hasTable('pembayaran') || !$this->hasColumn('pembayaran', 'bukti_pembayaran')) {
            return 'NULL AS payment_proof_path';
        }

        return ($tableAlias !== '' ? $tableAlias . '.' : '') . 'bukti_pembayaran AS payment_proof_path';
    }

    private function getLatestPaymentProofJoinSql(string $reservationAlias, string $paymentAlias): string
    {
        if (!$this->hasTable('pembayaran') || !$this->hasColumn('pembayaran', 'bukti_pembayaran')) {
            return '';
        }

        return "
            LEFT JOIN (
                SELECT payment_current.reservasi_id, payment_current.bukti_pembayaran
                FROM pembayaran payment_current
                INNER JOIN (
                    SELECT reservasi_id, MAX(id) AS latest_id
                    FROM pembayaran
                    GROUP BY reservasi_id
                ) payment_latest
                    ON payment_latest.latest_id = payment_current.id
            ) {$paymentAlias}
                ON {$paymentAlias}.reservasi_id = {$reservationAlias}.id
        ";
    }

    private function resolveColumnName(string $table, string $preferredColumn, ?string $legacyColumn = null): ?string
    {
        if ($this->hasColumn($table, $preferredColumn)) {
            return $preferredColumn;
        }

        if ($legacyColumn !== null && $this->hasColumn($table, $legacyColumn)) {
            return $legacyColumn;
        }

        return null;
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

    private function hasTable(string $table): bool
    {
        $cacheKey = '__table__.' . $table;
        if (array_key_exists($cacheKey, $this->columnExistsCache)) {
            return $this->columnExistsCache[$cacheKey];
        }

        $exists = Schema::hasTable($table);
        $this->columnExistsCache[$cacheKey] = $exists;

        return $exists;
    }
}
