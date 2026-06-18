<?php

declare(strict_types=1);

function normalize_reservation_status_key($status): string
{
    $status = strtoupper(trim((string) $status));
    $status = str_replace(['-', '_'], ' ', $status);

    return preg_replace('/\s+/', ' ', $status) ?? $status;
}

function reservation_status_groups(): array
{
    return [
        'RESERVASI BARU' => ['RESERVASI BARU', 'PROSES', 'PROSES RESERVASI'],
        'BERKAS RESERVASI TIDAK SESUAI' => ['BERKAS RESERVASI TIDAK SESUAI', 'REVISI RESERVASI'],
        'KERJASAMA UMKM' => ['KERJASAMA UMKM', 'KERJA SAMA UMKM'],
        'PROSES VERIFIKASI' => ['PROSES VERIFIKASI', 'VERIFIKASI'],
        'BERKAS VERIFIKASI TIDAK SESUAI' => ['BERKAS VERIFIKASI TIDAK SESUAI', 'REVISI KERJASAMA UMKM', 'REVISI KERJA SAMA UMKM'],
        'MENUNGGU PEMBAYARAN' => ['MENUNGGU PEMBAYARAN', 'SETUJU', 'DISETUJUI'],
        'CEK PEMBAYARAN' => ['CEK PEMBAYARAN', 'PROSES PEMBAYARAN'],
        'BERKAS PEMBAYARAN TIDAK SESUAI' => ['BERKAS PEMBAYARAN TIDAK SESUAI', 'REVISI PEMBAYARAN'],
        'PERMOHONAN DITOLAK' => ['PERMOHONAN DITOLAK', 'TOLAK', 'DITOLAK'],
        'DIBATALKAN PEMOHON' => ['DIBATALKAN PEMOHON', 'BATAL'],
        'PEMBAYARAN LUNAS' => ['PEMBAYARAN LUNAS', 'LUNAS'],
        'ACARA SELESAI' => ['ACARA SELESAI', 'SELESAI'],
        'BERKAS TIDAK SESUAI' => ['BERKAS TIDAK SESUAI', 'BERKAS TIDAK LENGKAP', 'KEMBALI'],
    ];
}

function reservation_status_display_key($status): string
{
    $normalizedStatus = normalize_reservation_status_key($status);

    if ($normalizedStatus === '') {
        return 'RESERVASI BARU';
    }

    foreach (reservation_status_groups() as $displayKey => $aliases) {
        if (in_array($normalizedStatus, $aliases, true)) {
            return $displayKey;
        }
    }

    return $normalizedStatus;
}

function reservation_status_label($status): string
{
    $displayKey = reservation_status_display_key($status);

    return match ($displayKey) {
        'RESERVASI BARU' => 'Reservasi Baru',
        'BERKAS RESERVASI TIDAK SESUAI' => 'Berkas Reservasi Tidak Sesuai',
        'KERJASAMA UMKM', 'KERJA SAMA UMKM' => 'Kerjasama UMKM',
        'PROSES VERIFIKASI' => 'Proses Verifikasi',
        'BERKAS VERIFIKASI TIDAK SESUAI' => 'Berkas Verifikasi Tidak Sesuai',
        'MENUNGGU PEMBAYARAN' => 'Menunggu Pembayaran',
        'CEK PEMBAYARAN' => 'Cek Pembayaran',
        'BERKAS PEMBAYARAN TIDAK SESUAI' => 'Berkas Pembayaran Tidak Sesuai',
        'PERMOHONAN DITOLAK' => 'Permohonan Ditolak',
        'DIBATALKAN PEMOHON' => 'Dibatalkan Pemohon',
        'PEMBAYARAN LUNAS' => 'Pembayaran Lunas',
        'ACARA SELESAI' => 'Acara Selesai',
        'BERKAS TIDAK SESUAI' => 'Berkas Tidak Sesuai',
        default => $displayKey !== '' ? ucwords(strtolower($displayKey)) : '-',
    };
}

function reservation_status_storage_value($status): string
{
    $normalizedStatus = normalize_reservation_status_key($status);

    return match ($normalizedStatus) {
        'PROSES', 'PROSES RESERVASI', 'RESERVASI BARU' => 'Reservasi Baru',
        'BERKAS RESERVASI TIDAK SESUAI', 'REVISI RESERVASI' => 'Revisi Reservasi',
        'KERJASAMA UMKM' => 'Kerjasama UMKM',
        'VERIFIKASI', 'PROSES VERIFIKASI' => 'Proses Verifikasi',
        'BERKAS VERIFIKASI TIDAK SESUAI', 'REVISI KERJASAMA UMKM', 'REVISI KERJA SAMA UMKM' => 'Revisi Kerjasama UMKM',
        'MENUNGGU PEMBAYARAN', 'SETUJU', 'DISETUJUI' => 'Menunggu Pembayaran',
        'CEK PEMBAYARAN', 'PROSES PEMBAYARAN' => 'Proses Pembayaran',
        'BERKAS PEMBAYARAN TIDAK SESUAI', 'REVISI PEMBAYARAN' => 'Proses Pembayaran',
        'PERMOHONAN DITOLAK', 'TOLAK', 'DITOLAK' => 'Permohonan Ditolak',
        'DIBATALKAN PEMOHON', 'BATAL' => 'Dibatalkan Pemohon',
        'PEMBAYARAN LUNAS', 'LUNAS' => 'Pembayaran Lunas',
        'ACARA SELESAI', 'SELESAI' => 'Acara Selesai',
        'BERKAS TIDAK SESUAI', 'BERKAS TIDAK LENGKAP', 'KEMBALI' => 'Berkas Tidak Sesuai',
        default => trim((string) $status),
    };
}

function reservation_status_storage_values(array $statuses): array
{
    $values = [];

    foreach (reservation_status_filter_values($statuses) as $status) {
        $storageValue = reservation_status_storage_value($status);

        if (trim($storageValue) !== '') {
            $values[] = strtoupper($storageValue);
        }
    }

    return array_values(array_unique($values));
}

function reservation_status_html_label($status): string
{
    $displayKey = reservation_status_display_key($status);

    return match ($displayKey) {
        'RESERVASI BARU' => 'Reservasi<br>Baru',
        'BERKAS RESERVASI TIDAK SESUAI' => 'Berkas Reservasi<br>Tidak Sesuai',
        'KERJASAMA UMKM' => 'Kerjasama<br>UMKM',
        'PROSES VERIFIKASI' => 'Proses<br>Verifikasi',
        'BERKAS VERIFIKASI TIDAK SESUAI' => 'Berkas Verifikasi<br>Tidak Sesuai',
        'MENUNGGU PEMBAYARAN' => 'Menunggu<br>Pembayaran',
        'CEK PEMBAYARAN' => 'Cek<br>Pembayaran',
        'BERKAS PEMBAYARAN TIDAK SESUAI' => 'Berkas Pembayaran<br>Tidak Sesuai',
        'PERMOHONAN DITOLAK' => 'Permohonan<br>Ditolak',
        'DIBATALKAN PEMOHON' => 'Dibatalkan<br>Pemohon',
        'PEMBAYARAN LUNAS' => 'Pembayaran<br>Lunas',
        'ACARA SELESAI' => 'Acara<br>Selesai',
        'BERKAS TIDAK SESUAI' => 'Berkas Tidak<br>Sesuai',
        default => reservation_status_label($displayKey),
    };
}

function reservation_status_tone($status): string
{
    return match (reservation_status_display_key($status)) {
        'RESERVASI BARU' => 'warning',
        'BERKAS RESERVASI TIDAK SESUAI' => 'dark',
        'KERJASAMA UMKM' => 'info',
        'PROSES VERIFIKASI' => 'warning',
        'BERKAS VERIFIKASI TIDAK SESUAI' => 'dark',
        'MENUNGGU PEMBAYARAN' => 'primary',
        'CEK PEMBAYARAN' => 'warning',
        'BERKAS PEMBAYARAN TIDAK SESUAI' => 'dark',
        'PERMOHONAN DITOLAK' => 'danger',
        'DIBATALKAN PEMOHON' => 'danger',
        'PEMBAYARAN LUNAS' => 'success',
        'ACARA SELESAI' => 'dark',
        'BERKAS TIDAK SESUAI' => 'dark',
        default => 'secondary',
    };
}

function reservation_status_matches($status, array $expectedStatuses): bool
{
    $normalizedStatus = reservation_status_display_key($status);
    if ($normalizedStatus === '') {
        return false;
    }

    foreach ($expectedStatuses as $expectedStatus) {
        if ($normalizedStatus === reservation_status_display_key($expectedStatus)) {
            return true;
        }
    }

    return false;
}

function reservation_status_filter_values(array $statuses): array
{
    $values = [];
    $groups = reservation_status_groups();

    foreach ($statuses as $status) {
        $displayKey = reservation_status_display_key($status);
        $groupValues = $groups[$displayKey] ?? [];

        if ($groupValues === []) {
            $normalizedStatus = normalize_reservation_status_key($status);
            if ($normalizedStatus !== '') {
                $values[] = $normalizedStatus;
            }

            continue;
        }

        foreach ($groupValues as $groupValue) {
            $values[] = $groupValue;
        }
    }

    return array_values(array_unique(array_filter($values, static function ($value): bool {
        return trim((string) $value) !== '';
    })));
}

function reservation_status_uses_order_code($status): bool
{
    return in_array(
        reservation_status_display_key($status),
        ['MENUNGGU PEMBAYARAN', 'CEK PEMBAYARAN', 'BERKAS PEMBAYARAN TIDAK SESUAI', 'PEMBAYARAN LUNAS', 'ACARA SELESAI'],
        true
    );
}

function reservation_status_class_lookup(): array
{
    $lookup = [];

    foreach (reservation_status_groups() as $displayKey => $aliases) {
        $tone = reservation_status_tone($displayKey);
        $lookup[$displayKey] = $tone;

        foreach ($aliases as $alias) {
            $lookup[$alias] = $tone;
        }
    }

    return $lookup;
}
