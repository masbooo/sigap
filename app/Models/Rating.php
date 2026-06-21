<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PDO;

class Rating
{
    protected PDO $db;
    protected array $columnExistsCache = [];

    private static bool $bootstrapped = false;

    private const ALLOWED_TARGETS = ['GEDUNG', 'UMKM'];
    private const ALLOWED_STATUSES = ['PEMBAYARAN LUNAS', 'ACARA SELESAI'];

    public function __construct()
    {
        $this->db = DB::connection()->getPdo();
        $this->bootstrap();
    }

    public function getUserRatingNotifications(int $userId): array
    {
        $pageData = $this->getUserRatingPageData($userId);

        return [
            'pending_count' => (int) ($pageData['stats']['pending_count'] ?? 0),
            'items' => array_values((array) ($pageData['notifications'] ?? [])),
        ];
    }

    public function getUserRatingPageData(int $userId): array
    {
        if ($userId <= 0) {
            return [
                'groups' => [],
                'notifications' => [],
                'stats' => [
                    'reservation_count' => 0,
                    'target_count' => 0,
                    'pending_count' => 0,
                    'completed_count' => 0,
                ],
            ];
        }

        $reservasiModel = new Reservasi();
        $reservations = array_values(array_filter(
            $reservasiModel->byUserDetailed($userId),
            function (array $reservation): bool {
                return $this->isEligibleReservation($reservation);
            }
        ));

        $ratingsMap = $this->getRatingsMapForReservations($userId, array_column($reservations, 'id'));
        $groups = [];
        $notifications = [];
        $targetCount = 0;
        $pendingCount = 0;
        $completedCount = 0;

        foreach ($reservations as $reservation) {
            $group = $this->buildReservationGroup($reservation, $ratingsMap);

            if ($group === null) {
                continue;
            }

            $groups[] = $group;
            $targetCount += (int) ($group['target_count'] ?? 0);
            $pendingCount += (int) ($group['pending_count'] ?? 0);
            $completedCount += (int) ($group['completed_count'] ?? 0);

            if (!empty($group['pending_count'])) {
                $notifications[] = $group['notification'];
            }
        }

        usort($groups, function (array $left, array $right): int {
            $leftDate = (string) ($left['end_date'] ?? '');
            $rightDate = (string) ($right['end_date'] ?? '');

            if ($leftDate === $rightDate) {
                return (int) ($right['reservation_id'] ?? 0) <=> (int) ($left['reservation_id'] ?? 0);
            }

            return strcmp($rightDate, $leftDate);
        });

        usort($notifications, function (array $left, array $right): int {
            $leftDate = (string) ($left['end_date'] ?? '');
            $rightDate = (string) ($right['end_date'] ?? '');

            if ($leftDate === $rightDate) {
                return (int) ($right['reservation_id'] ?? 0) <=> (int) ($left['reservation_id'] ?? 0);
            }

            return strcmp($leftDate, $rightDate);
        });

        return [
            'groups' => $groups,
            'notifications' => $notifications,
            'stats' => [
                'reservation_count' => count($groups),
                'target_count' => $targetCount,
                'pending_count' => $pendingCount,
                'completed_count' => $completedCount,
            ],
        ];
    }

    public function saveUserRating(int $userId, array $input): array
    {
        $reservationId = (int) ($input['reservation_id'] ?? 0);
        $targetType = strtoupper(trim((string) ($input['target_type'] ?? '')));
        $targetId = (int) ($input['target_id'] ?? 0);
        $rating = (int) ($input['rating'] ?? 0);
        $review = trim((string) ($input['review'] ?? ''));

        if ($userId <= 0 || $reservationId <= 0) {
            return $this->failResult('Data rating tidak valid.');
        }

        if (!in_array($targetType, self::ALLOWED_TARGETS, true)) {
            return $this->failResult('Jenis rating tidak valid.');
        }

        if ($rating < 1 || $rating > 5) {
            return $this->failResult('Rating harus berada di antara 1 sampai 5 bintang.');
        }

        $reservation = $this->findEligibleReservationById($userId, $reservationId);
        if ($reservation === null) {
            return $this->failResult('Reservasi yang akan dinilai tidak ditemukan atau belum layak dinilai.');
        }

        $expectedTargetId = $targetType === 'GEDUNG'
            ? (int) ($reservation['building_id'] ?? 0)
            : (int) ($reservation['umkm_id'] ?? 0);

        if ($expectedTargetId <= 0) {
            return $this->failResult('Target rating tidak tersedia untuk reservasi ini.');
        }

        if ($targetId > 0 && $targetId !== $expectedTargetId) {
            return $this->failResult('Target rating tidak sesuai dengan data reservasi.');
        }

        $upsertStmt = $this->db->prepare("
            INSERT INTO rating_ulasan (
                user_id,
                reservation_id,
                target_type,
                target_id,
                rating,
                review,
                created_at,
                updated_at
            ) VALUES (
                :user_id,
                :reservation_id,
                :target_type,
                :target_id,
                :rating,
                :review,
                NOW(),
                NOW()
            )
            ON DUPLICATE KEY UPDATE
                target_id = VALUES(target_id),
                rating = VALUES(rating),
                review = VALUES(review),
                updated_at = NOW()
        ");

        $upsertStmt->execute([
            ':user_id' => $userId,
            ':reservation_id' => $reservationId,
            ':target_type' => $targetType,
            ':target_id' => $expectedTargetId,
            ':rating' => $rating,
            ':review' => $review !== '' ? $review : null,
        ]);

        $this->refreshAggregateRating($targetType, $expectedTargetId);

        return [
            'success' => true,
            'message' => $targetType === 'GEDUNG'
                ? 'Rating gedung berhasil disimpan.'
                : 'Rating UMKM berhasil disimpan.',
            'reservation_id' => $reservationId,
            'target_type' => strtolower($targetType),
            'anchor' => 'rating-' . $reservationId . '-' . strtolower($targetType),
        ];
    }

    private function bootstrap(): void
    {
        if (self::$bootstrapped) {
            return;
        }

        $this->ensureTable();
        self::$bootstrapped = true;
    }

    private function ensureTable(): void
    {
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS rating_ulasan (
                id INT(11) NOT NULL AUTO_INCREMENT,
                user_id INT(11) NOT NULL,
                reservation_id INT(11) NOT NULL,
                target_type VARCHAR(20) NOT NULL,
                target_id INT(11) NOT NULL,
                rating TINYINT(1) NOT NULL DEFAULT 0,
                review TEXT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY uniq_rating_user_reservation_target (user_id, reservation_id, target_type),
                KEY idx_rating_target (target_type, target_id),
                KEY idx_rating_user (user_id),
                KEY idx_rating_reservation (reservation_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        ");
    }

    private function getRatingsMapForReservations(int $userId, array $reservationIds): array
    {
        $reservationIds = array_values(array_unique(array_filter(array_map('intval', $reservationIds), static function (int $id): bool {
            return $id > 0;
        })));

        if ($reservationIds === []) {
            return [];
        }

        $placeholders = [];
        $params = [
            ':user_id' => $userId,
        ];

        foreach ($reservationIds as $index => $reservationId) {
            $placeholder = ':reservation_id_' . $index;
            $placeholders[] = $placeholder;
            $params[$placeholder] = $reservationId;
        }

        $stmt = $this->db->prepare("
            SELECT
                reservation_id,
                target_type,
                target_id,
                rating,
                review,
                created_at,
                updated_at
            FROM rating_ulasan
            WHERE user_id = :user_id
              AND reservation_id IN (" . implode(', ', $placeholders) . ")
        ");

        $stmt->execute($params);

        $map = [];

        foreach ($stmt->fetchAll() ?: [] as $row) {
            $reservationId = (int) ($row['reservation_id'] ?? 0);
            $targetType = strtoupper(trim((string) ($row['target_type'] ?? '')));

            if ($reservationId <= 0 || !in_array($targetType, self::ALLOWED_TARGETS, true)) {
                continue;
            }

            $map[$reservationId][$targetType] = $row;
        }

        return $map;
    }

    private function buildReservationGroup(array $reservation, array $ratingsMap): ?array
    {
        $reservationId = (int) ($reservation['id'] ?? 0);
        if ($reservationId <= 0) {
            return null;
        }

        $targets = [];
        $targets[] = $this->buildTargetItem($reservation, $ratingsMap, $reservationId, 'GEDUNG');

        if ((int) ($reservation['umkm_id'] ?? 0) > 0 && trim((string) ($reservation['umkm_name'] ?? '')) !== '') {
            $targets[] = $this->buildTargetItem($reservation, $ratingsMap, $reservationId, 'UMKM');
        }

        $targets = array_values(array_filter($targets));

        if ($targets === []) {
            return null;
        }

        $pendingTargets = array_values(array_filter($targets, static function (array $target): bool {
            return empty($target['is_completed']);
        }));

        $reservationTitle = trim((string) ($reservation['event_name'] ?? '')) !== ''
            ? (string) $reservation['event_name']
            : 'Reservasi #' . $reservationId;
        $reservationDateLabel = $this->formatDateRangeLabel(
            (string) ($reservation['start_date'] ?? ''),
            (string) ($reservation['end_date'] ?? '')
        );
        $reservationStatus = reservation_status_label($reservation['status'] ?? '');
        $reservationLocation = $this->buildReservationLocationLabel($reservation);

        $group = [
            'reservation_id' => $reservationId,
            'reservation_title' => $reservationTitle,
            'reservation_subtitle' => trim((string) ($reservation['building_name'] ?? '-')),
            'reservation_date_label' => $reservationDateLabel,
            'reservation_status' => $reservationStatus !== '' ? $reservationStatus : '-',
            'reservation_status_tone' => $this->resolveStatusTone($reservationStatus),
            'reservation_location' => $reservationLocation,
            'start_date' => (string) ($reservation['start_date'] ?? ''),
            'end_date' => (string) ($reservation['end_date'] ?? ''),
            'end_date_sort' => (string) ($reservation['end_date'] ?? ''),
            'targets' => $targets,
            'target_count' => count($targets),
            'pending_count' => count($pendingTargets),
            'completed_count' => count($targets) - count($pendingTargets),
            'notification' => [
                'reservation_id' => $reservationId,
                'title' => $reservationTitle,
                'subtitle' => $this->buildNotificationSubtitle($reservation, $pendingTargets),
                'message' => $this->buildPendingLabel($pendingTargets),
                'href' => base_url('user/rating' . ($pendingTargets !== [] ? '#' . ($pendingTargets[0]['anchor'] ?? '') : '')),
                'pending_count' => count($pendingTargets),
                'end_date' => (string) ($reservation['end_date'] ?? ''),
            ],
        ];

        return $group;
    }

    private function buildTargetItem(array $reservation, array $ratingsMap, int $reservationId, string $targetType): ?array
    {
        $targetType = strtoupper(trim($targetType));
        if (!in_array($targetType, self::ALLOWED_TARGETS, true)) {
            return null;
        }

        $targetId = $targetType === 'GEDUNG'
            ? (int) ($reservation['building_id'] ?? 0)
            : (int) ($reservation['umkm_id'] ?? 0);

        if ($targetId <= 0) {
            return null;
        }

        $existingRating = $ratingsMap[$reservationId][$targetType] ?? [];
        $ratingValue = (int) ($existingRating['rating'] ?? 0);
        $reviewValue = trim((string) ($existingRating['review'] ?? ''));
        $isCompleted = $ratingValue >= 1;
        $anchor = 'rating-' . $reservationId . '-' . strtolower($targetType);
        $defaultThumb = asset_url('assets/custom/images/backgrounds/profilebg.jpg');

        if ($targetType === 'GEDUNG') {
            $nameLabel = trim((string) ($reservation['building_name'] ?? 'Gedung'));
            $subtitle = $this->buildGedungSubtitle($reservation);
            $thumb = resolve_public_upload_url($reservation['building_photo'] ?? '', $defaultThumb);
        } else {
            $nameLabel = trim((string) ($reservation['umkm_name'] ?? 'UMKM'));
            $subtitle = $this->buildUmkmSubtitle($reservation);
            $thumb = resolve_public_upload_url($reservation['umkm_photo'] ?? '', $defaultThumb);
        }

        return [
            'anchor' => $anchor,
            'target_type' => $targetType,
            'target_type_label' => $targetType === 'GEDUNG' ? 'Gedung' : 'UMKM',
            'target_id' => $targetId,
            'name_label' => $nameLabel !== '' ? $nameLabel : ($targetType === 'GEDUNG' ? 'Gedung' : 'UMKM'),
            'name_subtitle' => $subtitle,
            'thumbnail_url' => $thumb,
            'rating' => $ratingValue > 0 ? $ratingValue : null,
            'rating_value' => $ratingValue > 0 ? $ratingValue : '',
            'rating_label' => $ratingValue > 0 ? number_format($ratingValue, 0, ',', '.') . '/5' : 'Pilih rating',
            'rating_tone' => $this->resolveRatingTone($ratingValue > 0 ? (float) $ratingValue : null),
            'review' => $reviewValue,
            'is_completed' => $isCompleted,
            'submit_label' => $isCompleted ? 'Perbarui Ulasan' : 'Simpan Ulasan',
        ];
    }

    private function buildGedungSubtitle(array $reservation): string
    {
        $location = $this->buildReservationLocationLabel($reservation);
        $session = trim((string) ($reservation['session_display_name'] ?? ''));
        $eventDate = $this->formatDateRangeLabel(
            (string) ($reservation['start_date'] ?? ''),
            (string) ($reservation['end_date'] ?? '')
        );

        $parts = array_values(array_filter([
            $location !== '-' ? $location : '',
            $session !== '' ? $session : '',
            $eventDate !== '-' ? $eventDate : '',
        ]));

        return !empty($parts) ? implode(' • ', $parts) : '-';
    }

    private function buildUmkmSubtitle(array $reservation): string
    {
        $owner = trim((string) ($reservation['umkm_owner'] ?? ''));
        $location = $this->buildUmkmLocationLabel($reservation);

        $parts = array_values(array_filter([
            $owner !== '' ? 'Pemilik: ' . $owner : '',
            $location !== '-' ? $location : '',
        ]));

        return !empty($parts) ? implode(' • ', $parts) : '-';
    }

    private function buildReservationLocationLabel(array $reservation): string
    {
        $region = trim((string) ($reservation['region'] ?? ''));
        $district = trim((string) ($reservation['district'] ?? ''));

        if ($region !== '' && $district !== '') {
            return $region . ' - ' . $district;
        }

        if ($district !== '') {
            return $district;
        }

        if ($region !== '') {
            return $region;
        }

        return '-';
    }

    private function buildUmkmLocationLabel(array $reservation): string
    {
        $region = trim((string) ($reservation['home_region'] ?? ''));
        $district = trim((string) ($reservation['home_district'] ?? ''));

        if ($region !== '' && $district !== '') {
            return $region . ' - ' . $district;
        }

        if ($district !== '') {
            return $district;
        }

        if ($region !== '') {
            return $region;
        }

        return '-';
    }

    private function buildPendingLabel(array $pendingTargets): string
    {
        if ($pendingTargets === []) {
            return 'Semua rating sudah diberikan';
        }

        $labels = array_map(static function (array $target): string {
            return (string) ($target['target_type_label'] ?? '');
        }, $pendingTargets);

        $labels = array_values(array_filter($labels));

        if ($labels === []) {
            return 'Rating menunggu diberikan';
        }

        if (count($labels) === 1) {
            return $labels[0] . ' belum dinilai';
        }

        return implode(' & ', $labels) . ' belum dinilai';
    }

    private function buildNotificationSubtitle(array $reservation, array $pendingTargets): string
    {
        $building = trim((string) ($reservation['building_name'] ?? ''));
        $eventDate = $this->formatDateRangeLabel(
            (string) ($reservation['start_date'] ?? ''),
            (string) ($reservation['end_date'] ?? '')
        );
        $labels = array_map(static function (array $target): string {
            return (string) ($target['target_type_label'] ?? '');
        }, $pendingTargets);

        $labels = array_values(array_filter($labels));
        $targetLabel = $labels !== [] ? implode(' & ', $labels) : 'rating';

        $parts = array_values(array_filter([
            $building !== '' ? $building : '',
            $eventDate !== '-' ? $eventDate : '',
        ]));

        $subtitle = !empty($parts) ? implode(' • ', $parts) : '-';

        return trim($targetLabel . ' • ' . $subtitle);
    }

    private function findEligibleReservationById(int $userId, int $reservationId): ?array
    {
        $reservasiModel = new Reservasi();
        $reservations = $reservasiModel->byUserDetailed($userId);

        foreach ($reservations as $reservation) {
            if ((int) ($reservation['id'] ?? 0) !== $reservationId) {
                continue;
            }

            if ($this->isEligibleReservation($reservation)) {
                return $reservation;
            }
        }

        return null;
    }

    private function refreshAggregateRating(string $targetType, int $targetId): void
    {
        $stmt = $this->db->prepare("
            SELECT
                AVG(rating) AS average_rating,
                COUNT(*) AS total_reviews
            FROM rating_ulasan
            WHERE target_type = :target_type
              AND target_id = :target_id
        ");
        $stmt->execute([
            ':target_type' => $targetType,
            ':target_id' => $targetId,
        ]);

        $row = $stmt->fetch() ?: [];
        $averageRating = round((float) ($row['average_rating'] ?? 0), 1);
        $reviewCount = (int) ($row['total_reviews'] ?? 0);

        if ($targetType === 'GEDUNG') {
            $payload = [];
            if ($this->hasColumn('gedung', 'rating')) {
                $payload['rating'] = $averageRating;
            }
            if ($this->hasColumn('gedung', 'rating_avg')) {
                $payload['rating_avg'] = $averageRating;
            }
            if ($this->hasColumn('gedung', 'review_count')) {
                $payload['review_count'] = $reviewCount;
            }
            $this->updateTargetAggregate('gedung', $targetId, $payload);
            return;
        }

        $payload = [];
        if ($this->hasColumn('umkm', 'rating_avg')) {
            $payload['rating_avg'] = $averageRating;
        }
        if ($this->hasColumn('umkm', 'rating')) {
            $payload['rating'] = $averageRating;
        }
        if ($this->hasColumn('umkm', 'review_count')) {
            $payload['review_count'] = $reviewCount;
        }
        $this->updateTargetAggregate('umkm', $targetId, $payload);
    }

    private function updateTargetAggregate(string $table, int $targetId, array $payload): void
    {
        if ($payload === [] || $targetId <= 0) {
            return;
        }

        $assignments = [];
        $params = [
            ':id' => $targetId,
        ];

        foreach ($payload as $column => $value) {
            $assignments[] = $column . ' = :' . $column;
            $params[':' . $column] = $value;
        }

        if ($assignments === []) {
            return;
        }

        $stmt = $this->db->prepare("
            UPDATE {$table}
            SET " . implode(', ', $assignments) . "
            WHERE id = :id
        ");

        $stmt->execute($params);
    }

    private function isEligibleReservation(array $reservation): bool
    {
        $status = strtoupper(trim((string) ($reservation['status'] ?? '')));
        if (!reservation_status_matches($status, self::ALLOWED_STATUSES)) {
            return false;
        }

        $endDate = trim((string) ($reservation['end_date'] ?? ''));
        if ($endDate === '') {
            $endDate = trim((string) ($reservation['start_date'] ?? ''));
        }

        if ($endDate === '') {
            return false;
        }

        return $endDate < date('Y-m-d');
    }

    private function formatDateRangeLabel(string $startDate, string $endDate): string
    {
        $startDate = trim($startDate);
        $endDate = trim($endDate);

        if ($startDate === '' && $endDate === '') {
            return '-';
        }

        $startLabel = $this->formatIndonesianDate($startDate !== '' ? $startDate : $endDate);
        $endLabel = $this->formatIndonesianDate($endDate !== '' ? $endDate : $startDate);

        if ($startDate !== '' && $endDate !== '' && $startDate !== $endDate) {
            return $startLabel . ' s/d ' . $endLabel;
        }

        return $startLabel;
    }

    private function formatIndonesianDate(string $date): string
    {
        $timestamp = strtotime($date);
        if (!$timestamp) {
            return $date;
        }

        $monthNames = [
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember',
        ];

        $day = (int) date('d', $timestamp);
        $month = (int) date('n', $timestamp);
        $year = date('Y', $timestamp);

        return $day . ' ' . ($monthNames[$month] ?? date('F', $timestamp)) . ' ' . $year;
    }

    private function resolveStatusTone(string $status): string
    {
        return reservation_status_tone($status);
    }

    private function resolveRatingTone(?float $rating): string
    {
        if ($rating === null) {
            return 'secondary';
        }

        if ($rating >= 4.5) {
            return 'success';
        }

        if ($rating >= 4.0) {
            return 'primary';
        }

        if ($rating >= 3.0) {
            return 'warning';
        }

        return 'danger';
    }

    private function failResult(string $message): array
    {
        return [
            'success' => false,
            'message' => $message,
        ];
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
