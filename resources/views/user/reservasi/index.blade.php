@extends('layouts.user')

@section('content')

@php
    $profileName = resolve_user_display_name($user ?? user());
    $selectedDate = $oldInput['start_date'] ?? '';
    $formatReservationDisplayDate = static function (?string $date): string {
        $date = trim((string) $date);
        if ($date === '') {
            return '-';
        }

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

        return date('d', $timestamp) . ' ' . ($monthNames[(int) date('n', $timestamp)] ?? date('F', $timestamp)) . ' ' . date('Y', $timestamp);
    };
    $minBookingDateLabel = $formatReservationDisplayDate($minBookingDate ?? '');
    $selectedBuildingId = (string) ($oldInput['building_id'] ?? '');
    $selectedBuildingName = $selectedBuilding['building_name'] ?? '';
    $selectedBuildingCapacity = (int) ($selectedBuilding['capacity'] ?? 0);
    $selectedBuildingLocation = trim(($selectedBuilding['district'] ?? '') . (($selectedBuilding['region'] ?? '') !== '' ? ' - Surabaya ' . $selectedBuilding['region'] : ''));
    $selectedBuildingLabel = trim($selectedBuildingName . ($selectedBuildingLocation !== '' ? ' (' . $selectedBuildingLocation . ')' : ''));
    $editingReservationId = (int) ($oldInput['reservation_id'] ?? ($editingReservation['id'] ?? 0));
    $isEditMode = $editingReservationId > 0;
    $shouldShowReservationForm = $isEditMode || $error !== '' || !empty($oldInput);
    $shouldAutoLoadReservationPanel = $isEditMode || !empty($oldInput);
    $userAddress = trim((string) ($user['address'] ?? ''));
    $selectedUmkmId = (string) ($oldInput['umkm_id'] ?? '');
    $selectedSessionOption = (string) ($oldInput['session_option'] ?? '');
    $selectedStartTime = trim((string) ($oldInput['start_time'] ?? ''));
    $selectedEndTime = trim((string) ($oldInput['end_time'] ?? ''));
    $isCustomSessionSelected = $selectedSessionOption === 'lainnya';
    $reservationFormAction = $isEditMode ? url('user/reservasi/update') : url('user/reservasi');
    $reservationFormTitle = $isEditMode ? 'UBAH RESERVASI' : 'FORM RESERVASI';
    $reservationFormDescription = $isEditMode
        ? 'Data reservasi yang berstatus Reservasi Baru, Kerjasama UMKM, Berkas Reservasi Tidak Sesuai, atau Berkas Verifikasi Tidak Sesuai dapat diperbarui dan dikirim ulang'
        : 'Tanggal dan gedung akan terisi dari kalender serta filter lokasi yang Anda pilih';
    $reservationSubmitLabel = $isEditMode ? 'Simpan Perubahan' : 'Reservasi';
    $reservationSelectionStatus = $selectedDate !== '' && $selectedBuildingLabel !== ''
        ? ($isEditMode ? 'Siap Diperbarui' : 'Siap Diajukan')
        : 'Menunggu pilihan tanggal dan gedung';
    $identityUploadRequired = !$isEditMode;
    $requestUploadRequired = !$isEditMode;
    $identityUploadFeedback = $identityUploadRequired
        ? 'Unggah file identitas terlebih dahulu'
        : 'Unggah file identitas baru bila ingin mengganti berkas sebelumnya';
    $requestUploadFeedback = $requestUploadRequired
        ? 'Unggah file permohonan terlebih dahulu'
        : 'Unggah file permohonan baru bila ingin mengganti berkas sebelumnya';
    $identityUploadHelper = $identityUploadRequired
        ? 'Format yang didukung: JPG, JPEG, PNG, PDF dan ukuran maks. 1MB'
        : 'Kosongkan bila tidak ada perubahan file. Format yang didukung: JPG, JPEG, PNG, PDF dan ukuran maks. 1MB';
    $requestUploadHelper = $requestUploadRequired
        ? 'Format yang didukung: JPG, JPEG, PNG, PDF dan ukuran maks. 1MB'
        : 'Kosongkan bila tidak ada perubahan file. Format yang didukung: JPG, JPEG, PNG, PDF dan ukuran maks. 1MB';
    $estPersonFeedback = $selectedBuildingCapacity > 0
        ? 'Estimasi orang tidak boleh 0, maksimum ' . number_format($selectedBuildingCapacity, 0, ',', '.') . ' orang'
        : 'Estimasi orang tidak boleh 0 dan wajib menyesuaikan kapasitas gedung';
    $statusClasses = reservation_status_class_lookup();
    $formatStatusLabel = static function (?string $status): string {
        return reservation_status_display_key($status);
    };
    $resolveReservationCode = static function (array $reservation): string {
        $requestCode = trim((string) ($reservation['request_id'] ?? ''));
        $orderCode = trim((string) ($reservation['order_id'] ?? ''));
        $useOrderCode = reservation_status_uses_order_code($reservation['status'] ?? '');
        $code = $useOrderCode ? $orderCode : $requestCode;

        if ($code === '') {
            return $requestCode !== '' ? $requestCode : ($orderCode !== '' ? $orderCode : '-');
        }

        return $code;
    };
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
    $formatDate = static function (?string $date) use ($monthNames): string {
        $date = trim((string) $date);
        if ($date === '') {
            return '-';
        }

        $timestamp = strtotime($date);
        if (!$timestamp) {
            return $date;
        }

        $day = (int) date('d', $timestamp);
        $month = (int) date('n', $timestamp);
        $year = date('Y', $timestamp);

        return $day . ' ' . ($monthNames[$month] ?? date('F', $timestamp)) . ' ' . $year;
    };
    $formatCurrency = static function ($amount): string {
        return 'Rp ' . number_format((float) $amount, 0, ',', '.');
    };
    $formatLocation = static function (array $reservation): string {
        $district = trim((string) ($reservation['district'] ?? ''));
        $region = trim((string) ($reservation['region'] ?? ''));

        return $district !== '' ? $district : ($region !== '' ? $region : '-');
    };
    $formatActivityDate = static function (array $reservation) use ($formatDate): string {
        $startDate = trim((string) ($reservation['start_date'] ?? ''));
        $endDate = trim((string) ($reservation['end_date'] ?? ''));

        if ($startDate === '' && $endDate === '') {
            return '-';
        }

        if ($startDate !== '' && $endDate !== '' && $startDate !== $endDate) {
            return $formatDate($startDate) . ' s/d ' . $formatDate($endDate);
        }

        return $formatDate($startDate !== '' ? $startDate : $endDate);
    };
    $formatSessionLabel = static function (array $sessionOption): string {
        if (!empty($sessionOption['label'])) {
            return (string) $sessionOption['label'];
        }

        if (!empty($sessionOption['display_name'])) {
            return (string) $sessionOption['display_name'];
        }

        $sessionName = trim((string) ($sessionOption['session_name'] ?? ''));
        $normalizedName = strtolower((string) preg_replace('/\s+/', ' ', $sessionName));
        $startTime = substr((string) ($sessionOption['start_time'] ?? ''), 0, 5);
        $endTime = substr((string) ($sessionOption['end_time'] ?? ''), 0, 5);
        $duration = (int) ($sessionOption['duration'] ?? 0);

        if (
            ($normalizedName !== '' && (
                str_contains($normalizedName, '1 hari') ||
                str_contains($normalizedName, 'full day') ||
                str_contains($normalizedName, 'sehari')
            )) ||
            $duration >= 10
        ) {
            return '1 Hari';
        }

        if ($startTime === '08:00' && $endTime === '13:00') {
            return '1 (08.00 - 13.00)';
        }

        if ($startTime === '16:00' && $endTime === '21:00') {
            return '2 (16.00 - 21.00)';
        }

        if ($startTime !== '' && $endTime !== '') {
            return ($sessionName !== '' ? $sessionName : 'Sesi') . ' (' . str_replace(':', '.', $startTime) . ' - ' . str_replace(':', '.', $endTime) . ')';
        }

        return $sessionName !== '' ? $sessionName : '-';
    };
@endphp

<div class="container-fluid">
    <div class="card bg-info-subtle shadow-none position-relative overflow-hidden mb-4">
        <div class="card-body px-4 py-3">
            <div class="row align-items-center">
                <div class="col-9">
                    <h4 class="fw-semibold mb-8">Reservasi Saya</h4>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item">
                                <span class="text-muted">Data</span>
                            </li>
                            <li class="breadcrumb-item active" aria-current="page">
                                Reservasi
                            </li>
                        </ol>
                    </nav>
                </div>
                <div class="col-3">
                    <div class="text-center mb-n5">
                        <img src="{{ asset('assets/custom/images/breadcrumb/ChatBc.png') }}" class="img-fluid mb-n4" alt="Breadcrumb">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4 reservation-history-toolbar">
                        <div class="reservation-history-toolbar__summary">
                            <h5 class="fw-semibold mb-1"><b>RIWAYAT RESERVASI SAYA</b></h5>
                            <p class="text-muted mb-0">Data ini memuat seluruh riwayat reservasi yang pernah diajukan</p>
                        </div>
                        <div class="reservation-history-toolbar__action">
                            <button
                                type="button"
                                class="btn btn-primary reservation-history-toolbar__button"
                                id="user-reservation-open-button"
                                aria-controls="user-reservation-panel-container"
                                aria-expanded="false"
                                @if ($profileIncomplete) disabled @endif
                            >
                                <b>RESERVASI SEKARANG</b>
                            </button>
                        </div>
                    </div>

                    <div class="datatables">
                        <div>
                            <table
                                id="zero_config"
                                class="table table-striped align-middle w-100"
                                data-scroll-x="false"
                                data-empty-message="Tidak ada data yang tersedia dalam tabel Reservasi"
                            >
                            <thead class="table-primary">
                                <tr>
                                    <th>No</th>
                                    <th class="text-center">Status</th>
                                    <th>Kode</th>
                                    <th>Gedung</th>
                                    <th>Tanggal Ajuan</th>
                                    <th>Tanggal Acara</th>
                                    <th>Acara</th>
                                    <th>Tarif Sewa</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($myReservations as $index => $reservation)
                                    @php
                                        $displayStatusKey = reservation_status_display_key($reservation['status'] ?? 'RESERVASI BARU');
                                        $statusClass = $statusClasses[$displayStatusKey] ?? 'secondary';
                                        $reservationCode = $resolveReservationCode($reservation);
                                        $buildingName = trim((string) ($reservation['building_name'] ?? '')) !== '' ? (string) $reservation['building_name'] : '-';
                                        $buildingLocation = $formatLocation($reservation);
                                        $formattedCreatedAt = $formatDate($reservation['created_at'] ?? '');
                                        $formattedActivityDate = $formatActivityDate($reservation);
                                        $eventName = trim((string) ($reservation['event_name'] ?? '')) !== '' ? (string) $reservation['event_name'] : '-';
                                        $formattedTotalPrice = $formatCurrency($reservation['total_price'] ?? 0);
                                        $canEditReservation = in_array($displayStatusKey, ['RESERVASI BARU', 'KERJASAMA UMKM', 'BERKAS RESERVASI TIDAK SESUAI', 'BERKAS VERIFIKASI TIDAK SESUAI'], true);
                                        $canDeleteReservation = $displayStatusKey === 'RESERVASI BARU';
                                        $canCancelReservation = in_array($displayStatusKey, ['KERJASAMA UMKM', 'PROSES VERIFIKASI', 'BERKAS VERIFIKASI TIDAK SESUAI', 'MENUNGGU PEMBAYARAN', 'CEK PEMBAYARAN', 'BERKAS PEMBAYARAN TIDAK SESUAI'], true);
                                        $canPrintPaymentReservation = $displayStatusKey === 'MENUNGGU PEMBAYARAN';
                                        $editActionLabel = in_array($displayStatusKey, ['KERJASAMA UMKM', 'BERKAS VERIFIKASI TIDAK SESUAI'], true) ? 'Upload Bukti' : 'Rubah';
                                    @endphp
                                    <tr
                                        id="user-reservation-row-{{ $reservation['id'] }}"
                                        data-reservation-id="{{ $reservation['id'] }}"
                                        data-child-code="{{ $reservationCode }}"
                                        data-child-building="{{ $buildingName }}"
                                        data-child-building-location="{{ $buildingLocation }}"
                                        data-child-submitted-at="{{ $formattedCreatedAt }}"
                                        data-child-event-date="{{ $formattedActivityDate }}"
                                        data-child-event-name="{{ $eventName }}"
                                        data-child-price="{{ $formattedTotalPrice }}"
                                    >
                                        <td class="admin-table-index-cell">{{ $index + 1 }}</td>
                                        @php
                                            $badgeText = reservation_status_html_label($displayStatusKey);
                                        @endphp
                                        <td class="admin-table-status-cell">
                                            <span class="badge text-bg-{{ $statusClass }}">{!! $badgeText !!}</span>
                                        </td>
                                        <td data-order="{{ $reservationCode }}">
                                            <span class="fw-semibold text-nowrap">{{ $reservationCode }}</span>
                                        </td>
                                        <td class="admin-table-stack">
                                            <div class="fw-semibold">{{ $buildingName }}</div>
                                            <div class="text-muted small">{{ $buildingLocation }}</div>
                                        </td>
                                        <td data-order="{{ $reservation['created_at'] ?? '' }}">
                                            <div class="fw-semibold">{{ $formattedCreatedAt }}</div>
                                        </td>
                                        <td data-order="{{ $reservation['start_date'] ?? '' }}">
                                            <div class="fw-semibold">{{ $formattedActivityDate }}</div>
                                        </td>
                                        <td>{{ $eventName }}</td>
                                        <td class="text-end" data-order="{{ (float) ($reservation['total_price'] ?? 0) }}">
                                            {{ $formattedTotalPrice }}
                                        </td>
                                        <td class="text-center admin-table-action-cell">
                                            <div class="admin-table-action-dropdown">
                                                <button
                                                    type="button"
                                                    class="btn btn-sm btn-light-secondary border-0 admin-table-action-toggle"
                                                    aria-label="Buka menu aksi"
                                                    aria-expanded="false"
                                                >
                                                    <i class="ti ti-dots fs-5" aria-hidden="true"></i>
                                                </button>

                                                <div class="admin-table-action-menu" hidden>
                                                    <button
                                                        type="button"
                                                        class="admin-table-action-item js-user-reservation-detail-button"
                                                        data-reservation-id="{{ $reservation['id'] }}"
                                                    >
                                                        <span class="admin-table-action-icon text-primary bg-primary-subtle">
                                                            <i class="ti ti-eye fs-5"></i>
                                                        </span>
                                                        <span class="admin-table-action-label">Detail</span>
                                                    </button>

                                                    @if ($canPrintPaymentReservation)
                                                        <button
                                                            type="button"
                                                            class="admin-table-action-item js-user-reservation-payment-print-button"
                                                            data-print-url="{{ url('user/reservasi/pembayaran/cetak/' . $reservation['id']) }}"
                                                            data-reservation-id="{{ $reservation['id'] }}"
                                                            data-reservation-code="{{ $reservationCode }}"
                                                        >
                                                            <span class="admin-table-action-icon text-success bg-success-subtle js-user-reservation-payment-action-icon">
                                                                <i class="ti ti-cash fs-5"></i>
                                                            </span>
                                                            <span class="admin-table-action-label js-user-reservation-payment-action-label">Bayar</span>
                                                        </button>
                                                    @endif

                                                    @if ($canEditReservation)
                                                        <a
                                                            href="{{ url('user/reservasi/rubah/' . $reservation['id']) }}"
                                                            class="admin-table-action-item"
                                                        >
                                                            <span class="admin-table-action-icon text-warning bg-warning-subtle">
                                                                <i class="ti ti-pencil fs-5"></i>
                                                            </span>
                                                            <span class="admin-table-action-label">{{ $editActionLabel }}</span>
                                                        </a>
                                                    @endif

                                                    @if ($canDeleteReservation)
                                                        <form
                                                            action="{{ url('user/reservasi/hapus') }}"
                                                            method="POST"
                                                            class="admin-table-action-form js-user-reservation-action-form"
                                                            data-confirm-title="<b>HAPUS RESERVASI</b>"
                                                            data-confirm-html="<b class='text-danger'>Reservasi yang telah dihapus tidak dapat dikembalikan</b><br><br>Lanjutkan?"
                                                            data-confirm-button="HAPUS"
                                                            data-confirm-fallback="Reservasi yang telah dihapus tidak dapat dikembalikan. Lanjutkan?"
                                                        >
                                                            {!! csrf_field() !!}
                                                            <input type="hidden" name="reservation_id" value="{{ $reservation['id'] }}">
                                                            <button type="submit" class="admin-table-action-item">
                                                                <span class="admin-table-action-icon text-danger bg-danger-subtle">
                                                                    <i class="ti ti-trash fs-5"></i>
                                                                </span>
                                                                <span class="admin-table-action-label">Hapus</span>
                                                            </button>
                                                        </form>
                                                    @endif

                                                    @if ($canCancelReservation)
                                                        <form
                                                            action="{{ url('user/reservasi/batal') }}"
                                                            method="POST"
                                                            class="admin-table-action-form js-user-reservation-action-form"
                                                            data-confirm-title="<b>BATALKAN RESERVASI</b>"
                                                            data-confirm-html="<b class='text-danger'>Reservasi yang dibatalkan tidak dapat diaktifkan kembali</b><br><br>Lanjutkan?"
                                                            data-confirm-button="BATALKAN"
                                                            data-confirm-fallback="Reservasi yang dibatalkan tidak dapat diaktifkan kembali. Lanjutkan?"
                                                        >
                                                            {!! csrf_field() !!}
                                                            <input type="hidden" name="reservation_id" value="{{ $reservation['id'] }}">
                                                            <button type="submit" class="admin-table-action-item">
                                                                <span class="admin-table-action-icon text-danger bg-danger-subtle">
                                                                    <i class="ti ti-circle-x fs-5"></i>
                                                                </span>
                                                                <span class="admin-table-action-label">Batal</span>
                                                            </button>
                                                        </form>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="text-center text-muted py-4">
                                            Tidak ada data yang tersedia dalam tabel Reservasi
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div
        id="user-reservation-panel-container"
        class="d-none mt-4"
        aria-live="polite"
    ></div>
</div>

<div class="modal fade" id="reservationHistoryDetailModal" tabindex="-1" aria-labelledby="reservationHistoryDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden admin-reservation-detail-modal-content">
            <div class="modal-header border-0 admin-reservation-detail-modal-header">
                <div>
                    <h5 class="modal-title text-light fw-bold mb-1" id="reservationHistoryDetailModalLabel"><b>DETAIL RIWAYAT RESERVASI</b></h5>
                    <div class="small admin-reservation-detail-modal-subtitle" id="historyDetailReservationCode">Kode : -</div>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <span class="badge admin-reservation-detail-status-badge" id="historyDetailStatusBadge">STATUS</span>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
            </div>

            <div class="modal-body p-4 admin-reservation-detail-modal-body">
                <div class="row g-3">
                    <div class="col-12">
                        <div class="history-detail-collapse-actions">
                            <button class="btn bg-primary-subtle text-primary history-detail-collapse-actions__button" type="button" data-bs-toggle="collapse" data-bs-target="#multiCollapseRiwayat" aria-expanded="false" aria-controls="multiCollapseRiwayat">
                                Riwayat Reservasi
                            </button>
                            <button class="btn bg-success-subtle text-success history-detail-collapse-actions__button" type="button" data-bs-toggle="collapse" data-bs-target="#multiCollapseDetail" aria-expanded="false" aria-controls="multiCollapseDetail">
                                Detail Reservasi
                            </button>
                            <button class="btn bg-warning-subtle text-warning history-detail-collapse-actions__button" type="button" data-bs-toggle="collapse" data-bs-target="#multiCollapseDokumen" aria-expanded="false" aria-controls="multiCollapseDokumen">
                                Kelengkapan Berkas
                            </button>
                            <button class="btn bg-danger-subtle text-danger history-detail-collapse-actions__button" type="button" data-bs-toggle="collapse" data-bs-target=".multi-collapse" aria-expanded="false" aria-controls="multiCollapseRiwayat multiCollapseDetail multiCollapseDokumen">
                                Tampilkan Semua
                            </button>
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="collapse multi-collapse history-detail-section" id="multiCollapseRiwayat">
                            <div class="border rounded-3 p-3 history-detail-collapse-card">
                                <h6 class="fw-semibold mb-3">Riwayat Reservasi</h6>
                                <div class="row g-3">
                                    <div class="table-responsive">
                                        <table class="table">
                                            <thead class="bg-inverse text-white">
                                                <tr>
                                                    <th>No.</th>
                                                    <th>Tanggal</th>
                                                    <th>Dari</th>
                                                    <th>Menuju</th>
                                                    {{-- <th>Keterangan</th> --}}
                                                    <th>Catatan</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td>1</td>
                                                    <td>20 Mei 2026<br>08:04:01</td>
                                                    <td><b>Reservasi Baru</b><br>(User)</td>
                                                    <td><b>Proses Reservasi</b><br>(Operator)</td>
                                                    {{-- <td>Data reservasi sedang diperiksa operator</td> --}}
                                                    <td>-</td>
                                                </tr>
                                                <tr>
                                                    <td>2</td>
                                                    <td>20 Mei 2026<br>10:05:02</td>
                                                    <td><b>Proses Reservasi</b><br>(Operator)</td>
                                                    <td><b>Revisi Reservasi</b><br>(User)</td>
                                                    {{-- <td>Data reservasi perlu diperbaiki pemohon</td> --}}
                                                    <td>Data di permohonan tidak sesuai dan belum ditandatangani</td>
                                                </tr>
                                                <tr>
                                                    <td>3</td>
                                                    <td>20 Mei 2026<br>14:20:32</td>
                                                    <td><b>Revisi Reservasi</b><br>(User)</td>
                                                    <td><b>Proses Reservasi</b><br>(Operator)</td>
                                                    {{-- <td>Data reservasi sedang diperiksa operator</td> --}}
                                                    <td>-</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="collapse multi-collapse history-detail-section" id="multiCollapseDetail">
                            <div class="border rounded-3 p-3 history-detail-collapse-card">
                                <h6 class="fw-semibold mb-3">Detail Reservasi</h6>
                                <div class="row g-3">
                                    @include('partials.modal.detail-info-card', ['detailInfoColClass' => 'col-md-6', 'detailInfoLabel' => 'NIK Pemohon', 'detailInfoValueId' => 'historyDetailNik', 'detailInfoIcon' => 'ti ti-id', 'detailInfoTone' => 'bg-warning-subtle text-warning'])
                                    @include('partials.modal.detail-info-card', ['detailInfoColClass' => 'col-md-6', 'detailInfoLabel' => 'Nama Pemohon', 'detailInfoValueId' => 'historyDetailRequester', 'detailInfoIcon' => 'ti ti-user', 'detailInfoTone' => 'bg-success-subtle text-success'])
                                    @include('partials.modal.detail-info-card', ['detailInfoColClass' => 'col-md-6', 'detailInfoLabel' => 'Alamat', 'detailInfoValueId' => 'historyDetailUserAddress', 'detailInfoIcon' => 'ti ti-map-2', 'detailInfoTone' => 'bg-info-subtle text-info'])
                                    @include('partials.modal.detail-info-card', ['detailInfoColClass' => 'col-md-6', 'detailInfoLabel' => 'Telp / HP', 'detailInfoValueId' => 'historyDetailPhone', 'detailInfoIcon' => 'ti ti-phone', 'detailInfoTone' => 'bg-danger-subtle text-danger'])
                                    @include('partials.modal.detail-info-card', ['detailInfoColClass' => 'col-md-6', 'detailInfoLabel' => 'Gedung', 'detailInfoValueId' => 'historyDetailBuilding', 'detailInfoIcon' => 'ti ti-building', 'detailInfoTone' => 'bg-danger-subtle text-danger'])
                                    @include('partials.modal.detail-info-card', ['detailInfoColClass' => 'col-md-6', 'detailInfoLabel' => 'Alamat Gedung', 'detailInfoValueId' => 'historyDetailBuildingAddress', 'detailInfoIcon' => 'ti ti-map-pin', 'detailInfoTone' => 'bg-warning-subtle text-warning'])
                                    @include('partials.modal.detail-info-card', ['detailInfoColClass' => 'col-md-6', 'detailInfoLabel' => 'Tanggal Acara', 'detailInfoValueId' => 'historyDetailDate', 'detailInfoIcon' => 'ti ti-calendar-event', 'detailInfoTone' => 'bg-success-subtle text-success'])
                                    @include('partials.modal.detail-info-card', ['detailInfoColClass' => 'col-md-6', 'detailInfoLabel' => 'Sesi', 'detailInfoValueId' => 'historyDetailSession', 'detailInfoIcon' => 'ti ti-clock-hour-4', 'detailInfoTone' => 'bg-primary-subtle text-primary'])
                                    @include('partials.modal.detail-info-card', ['detailInfoColClass' => 'col-md-6', 'detailInfoLabel' => 'Acara', 'detailInfoValueId' => 'historyDetailEvent', 'detailInfoIcon' => 'ti ti-ticket', 'detailInfoTone' => 'bg-secondary-subtle text-secondary'])
                                    @include('partials.modal.detail-info-card', ['detailInfoColClass' => 'col-md-6', 'detailInfoLabel' => 'Estimasi Orang', 'detailInfoValueId' => 'historyDetailEstPerson', 'detailInfoIcon' => 'ti ti-users', 'detailInfoTone' => 'bg-success-subtle text-success'])
                                    @include('partials.modal.detail-info-card', ['detailInfoColClass' => 'col-md-6', 'detailInfoLabel' => 'Tarif Sewa', 'detailInfoValueId' => 'historyDetailTotalPrice', 'detailInfoIcon' => 'ti ti-receipt-2', 'detailInfoTone' => 'bg-danger-subtle text-danger'])
                                    @include('partials.modal.detail-info-card', ['detailInfoColClass' => 'col-md-6', 'detailInfoLabel' => 'UMKM', 'detailInfoValueId' => 'historyDetailUmkm', 'detailInfoIcon' => 'ti ti-building-store', 'detailInfoTone' => 'bg-primary-subtle text-primary'])
                                    @include('partials.modal.detail-info-card', ['detailInfoColClass' => 'col-12', 'detailInfoLabel' => 'Catatan Admin', 'detailInfoValueId' => 'historyDetailNotes', 'detailInfoIcon' => 'ti ti-notebook', 'detailInfoTone' => 'bg-warning-subtle text-warning', 'detailInfoValueClass' => 'fw-semibold admin-reservation-detail-notes'])
                                </div>
                            </div>
                        </div>

                        <div class="collapse multi-collapse history-detail-section" id="multiCollapseDokumen">
                            <div class="border rounded-3 p-3 history-detail-collapse-card">
                                <h6 class="fw-semibold mb-3">Kelengkapan Berkas</h6>
                                <div class="row g-3">
                                    @include('partials.modal.detail-info-card', ['detailInfoColClass' => 'col-md-6', 'detailInfoLabel' => 'KTP', 'detailInfoValueId' => 'historyDetailKtpFile', 'detailInfoIcon' => 'ti ti-id-badge-2', 'detailInfoTone' => 'bg-success-subtle text-success'])
                                    @include('partials.modal.detail-info-card', ['detailInfoColClass' => 'col-md-6', 'detailInfoLabel' => 'Permohonan', 'detailInfoValueId' => 'historyDetailApplicationFile', 'detailInfoIcon' => 'ti ti-file-description', 'detailInfoTone' => 'bg-warning-subtle text-warning'])
                                    @include('partials.modal.detail-info-card', ['detailInfoColClass' => 'col-md-6', 'detailInfoWrapperId' => 'historyDetailUmkmFileWrapper', 'detailInfoLabel' => 'Kerjasama UMKM', 'detailInfoValueId' => 'historyDetailUmkmFile', 'detailInfoIcon' => 'ti ti-building-store', 'detailInfoTone' => 'bg-info-subtle text-info'])
                                    @include('partials.modal.detail-info-card', ['detailInfoColClass' => 'col-md-6', 'detailInfoWrapperId' => 'historyDetailPaymentFileWrapper', 'detailInfoLabel' => 'Bukti Bayar', 'detailInfoValueId' => 'historyDetailPaymentFile', 'detailInfoIcon' => 'ti ti-cash', 'detailInfoTone' => 'bg-primary-subtle text-primary'])
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="eventDetailModal" tabindex="-1" aria-labelledby="eventDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div
                class="modal-header text-white border-0 reservation-event-detail-header"
                id="eventDetailHeader"
            >
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <h5 class="modal-title text-light mb-0 fw-bold" id="eventDetailModalLabel">DETAIL RESERVASI</h5>
                    <span id="eventDetailStatusBadge" class="status-pill-modal">STATUS</span>
                </div>

                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body p-4">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="detail-info-card">
                            <div class="detail-info-icon bg-danger-subtle text-danger">
                                <i class="ti ti-building"></i>
                            </div>
                            <div class="detail-info-text">
                                <small>Nama Gedung</small>
                                <div id="detailBuildingName" class="fw-semibold">-</div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="detail-info-card">
                            <div class="detail-info-icon bg-warning-subtle text-warning">
                                <i class="ti ti-calendar-event"></i>
                            </div>
                            <div class="detail-info-text">
                                <small>Acara</small>
                                <div id="detailAcaraName" class="fw-semibold">-</div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="detail-info-card">
                            <div class="detail-info-icon bg-success-subtle text-success">
                                <i class="ti ti-calendar-time"></i>
                            </div>
                            <div class="detail-info-text">
                                <small>Tanggal Acara</small>
                                <div id="detailRentalDate" class="fw-semibold">-</div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="detail-info-card">
                            <div class="detail-info-icon bg-info-subtle text-info">
                                <i class="ti ti-clock-hour-3"></i>
                            </div>
                            <div class="detail-info-text">
                                <small>Sesi</small>
                                <div id="detailSessionName" class="fw-semibold">-</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="reservationPaymentVaModal" tabindex="-1" aria-labelledby="reservationPaymentVaModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable reservation-payment-va-modal__dialog">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden reservation-payment-va-modal__content">
            <div class="reservation-payment-va-modal__hero">
                <div class="reservation-payment-modal__hero-row">
                    <div class="reservation-payment-modal__hero-main">
                        <h5 class="modal-title text-light fw-bold mb-1 reservation-payment-va-modal__title" id="reservationPaymentVaModalLabel">VIRTUAL ACCOUNT (VA) SIAP DIGUNAKAN</h5>
                        <div class="small admin-reservation-detail-modal-subtitle reservation-payment-va-modal__subtitle" id="reservationPaymentVaCode">Kode : -</div>
                    </div>
                    <div class="reservation-payment-modal__hero-actions">
                        <div class="reservation-payment-modal__badge reservation-payment-modal__badge--va">Metode Pembayaran VA</div>
                        <button type="button" class="btn-close btn-close-white reservation-payment-modal__close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                </div>
            </div>
            <div class="modal-body reservation-payment-va-modal__body">
                <div class="reservation-payment-va-modal__viewer">
                    <iframe
                        id="reservationPaymentVaFrame"
                        src="about:blank"
                        title="Cetak Virtual Account (VA)"
                        class="w-100 border-0 reservation-payment-va-modal__frame"
                    ></iframe>
                </div>
            </div>
            <div class="modal-footer reservation-payment-va-modal__footer flex-column align-items-stretch">
                <p class="reservation-payment-va-modal__footer-note mb-3 text-center">
                    Gunakan Buka atau Unduh PDF untuk melanjutkan pembayaran. Jika ingin mengganti metode, gunakan menu Revisi pada aksi reservasi
                </p>
                <div class="reservation-payment-va-modal__actions">
                    <a
                        href="#"
                        class="btn btn-warning reservation-modal-action-btn"
                        id="reservationPaymentVaOpenButton"
                        target="_blank"
                        rel="noopener"
                    >
                        BUKA PDF
                    </a>
                    <a
                        href="#"
                        class="btn btn-success reservation-modal-action-btn"
                        id="reservationPaymentVaDownloadButton"
                        target="_blank"
                        rel="noopener"
                        download
                    >
                        UNDUH PDF
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="reservationPaymentQrisModal" tabindex="-1" aria-labelledby="reservationPaymentQrisModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg reservation-payment-qris-modal__dialog">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden reservation-payment-qris-modal__content">
            <div class="reservation-payment-qris-modal__hero">
                <div class="reservation-payment-modal__hero-row">
                    <div class="reservation-payment-modal__hero-main">
                        <h5 class="modal-title text-light fw-bold mb-1 reservation-payment-qris-modal__title" id="reservationPaymentQrisModalLabel">KODE QR (QRIS) SIAP DIGUNAKAN</h5>
                        <div class="small admin-reservation-detail-modal-subtitle reservation-payment-qris-modal__subtitle" id="reservationPaymentQrisBookingCode">Kode : -</div>
                    </div>
                    <div class="reservation-payment-modal__hero-actions">
                        <div class="reservation-payment-modal__badge reservation-payment-modal__badge--qris">Metode Pembayaran QRIS</div>
                        <button type="button" class="btn-close btn-close-white reservation-payment-modal__close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                </div>
            </div>

            <div class="modal-body reservation-payment-qris-modal__body">
                <div class="reservation-payment-qris-modal__grid">
                    <div class="reservation-payment-qris-modal__meta-card">
                        <span class="reservation-payment-qris-modal__meta-label">Kode Pembayaran</span>
                        <strong class="reservation-payment-qris-modal__meta-value reservation-payment-qris-modal__meta-value--code" id="reservationPaymentQrisPaymentCode">-</strong>
                    </div>
                    <div class="reservation-payment-qris-modal__meta-card">
                        <span class="reservation-payment-qris-modal__meta-label">Total Pembayaran</span>
                        <strong class="reservation-payment-qris-modal__meta-value" id="reservationPaymentQrisTotal">-</strong>
                    </div>
                </div>

                <div class="reservation-payment-qris-modal__qr-card">
                    <div class="reservation-payment-qris-modal__countdown-wrap reservation-payment-qris-modal__countdown-wrap--qr">
                        <span class="reservation-payment-qris-modal__countdown-label">Masa Berlaku</span>
                        <strong class="reservation-payment-qris-modal__countdown-line">
                            <span id="reservationPaymentQrisExpiry">-</span>
                            <span class="reservation-payment-qris-modal__countdown" id="reservationPaymentQrisCountdown">(00 : 15 : 00)</span>
                        </strong>
                    </div>
                    <div class="reservation-payment-qris-modal__qr-frame">
                        <img
                            id="reservationPaymentQrisImage"
                            src="{{ asset('assets/custom/images/payment/qris-sample-qr.png') }}"
                            alt="QRIS Pembayaran"
                            class="img-fluid reservation-payment-qris-modal__qr-image"
                        >
                    </div>
                    <div class="reservation-payment-qris-modal__actions">
                        <a
                            href="#"
                            class="btn btn-success reservation-modal-action-btn reservation-payment-qris-modal__download-btn"
                            id="reservationPaymentQrisDownloadButton"
                            rel="noopener"
                            download
                        >
                            UNDUH KODE QR
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="application/json" id="user-reservation-config">
{!! json_encode([
    'filterData' => $filterData ?? [],
    'events' => $events ?? [],
    'minBookingDate' => $minBookingDate ?? null,
    'messages' => [
        'success' => $success ?? '',
        'error' => $error ?? '',
    ],
    'historyReservations' => $myReservations ?? [],
    'reservation' => [
        'panelUrl' => $reservationPanelUrl ?? url('user/reservasi/panel'),
        'panelContainerId' => 'user-reservation-panel-container',
        'autoLoadPanel' => $shouldAutoLoadReservationPanel,
        'formId' => 'userReservationForm',
        'buildingInputId' => 'reservation-building-id',
        'buildingDisplayId' => 'reservation-building-display',
        'startInputId' => 'reservation-start-date',
        'endInputId' => 'reservation-end-date',
        'dateDisplayId' => 'reservation-date-display',
        'selectedDateTextId' => 'reservation-selected-date-text',
        'selectedBuildingTextId' => 'reservation-selected-building-text',
        'selectionStatusId' => 'reservation-selection-status',
        'selectionHintId' => 'reservation-selection-hint',
        'startTimeId' => 'reservation-start-time',
        'endTimeId' => 'reservation-end-time',
        'customTimeGroupId' => 'reservation-custom-time-group',
        'umkmSelectId' => 'reservation-umkm-id',
        'requestFileId' => 'reservation-request-file',
        'openButtonId' => 'user-reservation-open-button',
        'printButtonId' => 'reservation-print-button',
        'printUrl' => $reservationPrintUrl ?? url('user/reservasi/permohonan/cetak'),
        'paymentProcessUrl' => url('user/reservasi/pembayaran/proses'),
        'paymentRevisionUrl' => url('user/reservasi/pembayaran/revisi'),
        'csrfToken' => csrf_token(),
        'detailRowId' => 'user-reservation-detail-row',
        'formColumnId' => 'user-reservation-form-column',
        'summaryColumnId' => 'user-reservation-summary-column',
    ],
], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
</script>

@endsection
