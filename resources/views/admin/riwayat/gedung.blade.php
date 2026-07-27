@extends('layouts.admin')

@section('content')
@php
    $roleContext = $roleContext ?? resolve_admin_role_context($admin ?? admin_user() ?? []);
    $pageMeta = resolve_admin_page_meta();
    $reservations = $reservations ?? [];
    $summaryCards = $summaryCards ?? [];
    $messages = $messages ?? ['success' => '', 'error' => ''];
    $statusClasses = $statusClasses ?? reservation_status_class_lookup();
    $canDeleteReservation = (bool) ($canDeleteReservation ?? false);
    $scopeLabel = (string) ($roleContext['scope_label'] ?? 'Seluruh Sistem');

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

    $formatPriceBreakdown = static function (array $reservation) use ($formatCurrency): string {
        $hourCount = (int) ($reservation['hour_count'] ?? 0);
        $hourPrice = (float) ($reservation['perhour_price'] ?? 0);

        if ($hourCount <= 0) {
            return '-';
        }

        return $hourCount . ' jam x ' . $formatCurrency($hourPrice);
    };

    $normalizeReservations = array_map(static function (array $reservation) use ($formatLocation, $formatPriceBreakdown) {
        $reservation['location_label'] = $formatLocation($reservation);
        $reservation['price_breakdown'] = $formatPriceBreakdown($reservation);

        return $reservation;
    }, $reservations);

    $registrationOrderedReservations = $normalizeReservations;
    usort($registrationOrderedReservations, static function (array $left, array $right): int {
        $leftTimestamp = strtotime((string) ($left['created_at'] ?? '')) ?: 0;
        $rightTimestamp = strtotime((string) ($right['created_at'] ?? '')) ?: 0;

        if ($leftTimestamp === $rightTimestamp) {
            return (int) ($left['id'] ?? 0) <=> (int) ($right['id'] ?? 0);
        }

        return $leftTimestamp <=> $rightTimestamp;
    });

    $reservationNumbersById = [];
    foreach ($registrationOrderedReservations as $sequence => $reservationItem) {
        $reservationNumbersById[(int) ($reservationItem['id'] ?? 0)] = $sequence + 1;
    }

    foreach ($normalizeReservations as $index => &$reservation) {
        $reservation['reservation_number'] = $reservationNumbersById[(int) ($reservation['id'] ?? 0)] ?? ($index + 1);
    }
    unset($reservation);

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
@endphp

<div class="container-fluid">
    <div class="card bg-info-subtle shadow-none position-relative overflow-hidden mb-4">
        <div class="card-body px-4 py-3">
            <div class="row align-items-center">
                <div class="col-9">
                    <h4 class="fw-semibold mb-8">Data Riwayat Reservasi Gedung</h4>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            @foreach (($pageMeta['breadcrumbs'] ?? []) as $index => $crumb)
                                @php
                                    $isLast = $index === count($pageMeta['breadcrumbs'] ?? []) - 1;
                                @endphp
                                <li class="breadcrumb-item{{ $isLast ? ' active' : '' }}" @if ($isLast) aria-current="page" @endif>
                                    @if (!$isLast && !empty($crumb['href']))
                                        <a class="text-muted text-decoration-none" href="{{ $crumb['href'] }}">{{ $crumb['label'] }}</a>
                                    @else
                                        {{ $crumb['label'] }}
                                    @endif
                                </li>
                            @endforeach
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

    @include('admin.riwayat.partials.carousel', [
        'summaryCards' => $summaryCards,
        'carouselId' => 'adminRiwayatGedungSummaryCarousel',
    ])

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
                <div>
                    <h5 class="fw-semibold mb-1"><b>RIWAYAT RESERVASI GEDUNG</b></h5>
                    <p class="text-muted mb-0">
                        @if (($roleContext['scope_type'] ?? 'all') === 'all')
                            Menampilkan seluruh riwayat reservasi gedung serbaguna dalam sistem tanpa filter wilayah
                        @else
                            Menampilkan seluruh riwayat reservasi gedung serbaguna sesuai wilayah {{ $scopeLabel }}
                        @endif
                    </p>
                </div>
            </div>
            <div class="datatables">
                <div>
                    <table
                        id="zero_config"
                        class="table table-striped align-middle w-100"
                        data-scroll-x="auto"
                        data-empty-message="Tidak ada data yang tersedia dalam tabel Riwayat Gedung"
                    >
                        <thead class="table-primary">
                            <tr>
                                <th>No</th>
                                <th class="text-center">Status</th>
                                <th>Kode</th>
                                <th>Pemohon</th>
                                <th>Gedung</th>
                                <th>Tanggal Ajuan</th>
                                <th>Tanggal Acara</th>
                                <th>Acara</th>
                                <th>Tarif Sewa</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($normalizeReservations as $index => $reservation)
                                @php
                                    $statusKey = reservation_status_display_key($reservation['status'] ?? 'RESERVASI BARU');
                                    $statusClass = $statusClasses[$statusKey] ?? 'secondary';
                                    $reservationCode = $resolveReservationCode($reservation);
                                    $requesterName = trim((string) ($reservation['user_name'] ?? '')) !== ''
                                        ? (string) $reservation['user_name']
                                        : ((string) ($reservation['username'] ?? '-') ?: '-');
                                @endphp
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    @php
                                        $badgeText = reservation_status_html_label($statusKey);
                                    @endphp
                                    <td class="admin-table-status-cell">
                                        <span class="badge text-bg-{{ $statusClass }}">{!! $badgeText !!}</span>
                                    </td>
                                    <td data-order="{{ $reservationCode }}">
                                        <span class="fw-semibold text-nowrap">{{ $reservationCode }}</span>
                                    </td>
                                    <td class="admin-table-stack">
                                        <div class="fw-semibold">{{ $requesterName }}</div>
                                        <div class="text-muted small">{{ $reservation['user_phone'] ?? '-' }}</div>
                                    </td>
                                    <td class="admin-table-stack">
                                        <div class="fw-semibold">{{ $reservation['building_name'] ?? '-' }}</div>
                                        <div class="text-muted small">{{ $reservation['location_label'] }}</div>
                                    </td>
                                    <td data-order="{{ $reservation['created_at'] ?? '' }}">
                                        <div class="fw-semibold">{{ $formatDate($reservation['created_at'] ?? '') }}</div>
                                    </td>
                                    <td data-order="{{ $reservation['start_date'] ?? '' }}">
                                        <div class="fw-semibold">{{ $formatActivityDate($reservation) }}</div>
                                    </td>
                                    <td>{{ $reservation['event_name'] ?? '-' }}</td>
                                    <td class="text-end" data-order="{{ (float) ($reservation['total_price'] ?? 0) }}">{{ $formatCurrency($reservation['total_price'] ?? 0) }}</td>
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
                                                    class="admin-table-action-item js-admin-reservation-detail-button"
                                                    data-reservation-id="{{ $reservation['id'] }}"
                                                    data-reservation-number="{{ $reservation['reservation_number'] }}"
                                                >
                                                    <span class="admin-table-action-icon text-primary bg-primary-subtle">
                                                        <i class="ti ti-eye fs-5"></i>
                                                    </span>
                                                    <span class="admin-table-action-label">Detail</span>
                                                </button>

                                                @if ($canDeleteReservation)
                                                    <form
                                                        action="{{ url('admin/riwayat/gedung/hapus') }}"
                                                        method="POST"
                                                        class="admin-table-action-form js-admin-reservation-delete-form"
                                                        data-reservation-label="{{ $requesterName }}"
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
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="adminReservationDetailModal" tabindex="-1" aria-labelledby="adminReservationDetailModalLabel" aria-hidden="true" data-reservation-context="gedung">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden admin-reservation-detail-modal-content">
            <div class="modal-header border-0 admin-reservation-detail-modal-header">
                <div>
                    <h5 class="modal-title text-light fw-bold mb-1" id="adminReservationDetailModalLabel"><b>DETAIL RIWAYAT RESERVASI GSG</b></h5>
                    <div class="small admin-reservation-detail-modal-subtitle" id="adminReservationDetailCode">Kode : -</div>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <span class="badge admin-reservation-detail-status-badge" id="adminReservationDetailStatusBadge">STATUS</span>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
            </div>

            <div class="modal-body p-4 admin-reservation-detail-modal-body">
                <div class="row g-3">
                    <div class="col-12">
                        <div class="border rounded-3 p-3">
                            <h6 class="fw-semibold mb-3">Detail Reservasi</h6>
                            <div class="row g-3">
                                @include('partials.modal.detail-info-card', ['detailInfoColClass' => 'col-md-6', 'detailInfoLabel' => 'NIK Pemohon', 'detailInfoValueId' => 'adminReservationDetailNik', 'detailInfoIcon' => 'ti ti-id', 'detailInfoTone' => 'bg-warning-subtle text-warning'])
                                @include('partials.modal.detail-info-card', ['detailInfoColClass' => 'col-md-6', 'detailInfoLabel' => 'Nama Pemohon', 'detailInfoValueId' => 'adminReservationDetailRequester', 'detailInfoIcon' => 'ti ti-user', 'detailInfoTone' => 'bg-success-subtle text-success'])
                                @include('partials.modal.detail-info-card', ['detailInfoColClass' => 'col-md-6', 'detailInfoLabel' => 'Alamat', 'detailInfoValueId' => 'adminReservationDetailUserAddress', 'detailInfoIcon' => 'ti ti-map-2', 'detailInfoTone' => 'bg-info-subtle text-info'])
                                @include('partials.modal.detail-info-card', ['detailInfoColClass' => 'col-md-6', 'detailInfoLabel' => 'Telp / HP', 'detailInfoValueId' => 'adminReservationDetailPhone', 'detailInfoIcon' => 'ti ti-phone', 'detailInfoTone' => 'bg-danger-subtle text-danger'])
                                @include('partials.modal.detail-info-card', ['detailInfoColClass' => 'col-md-6', 'detailInfoLabel' => 'Gedung', 'detailInfoValueId' => 'adminReservationDetailBuilding', 'detailInfoIcon' => 'ti ti-building', 'detailInfoTone' => 'bg-danger-subtle text-danger'])
                                @include('partials.modal.detail-info-card', ['detailInfoColClass' => 'col-md-6', 'detailInfoLabel' => 'Alamat Gedung', 'detailInfoValueId' => 'adminReservationDetailBuildingAddress', 'detailInfoIcon' => 'ti ti-map-pin', 'detailInfoTone' => 'bg-warning-subtle text-warning'])
                                @include('partials.modal.detail-info-card', ['detailInfoColClass' => 'col-md-6', 'detailInfoLabel' => 'Tanggal Acara', 'detailInfoValueId' => 'adminReservationDetailDate', 'detailInfoIcon' => 'ti ti-calendar-event', 'detailInfoTone' => 'bg-success-subtle text-success'])
                                @include('partials.modal.detail-info-card', ['detailInfoColClass' => 'col-md-6', 'detailInfoLabel' => 'Sesi', 'detailInfoValueId' => 'adminReservationDetailSession', 'detailInfoIcon' => 'ti ti-clock-hour-4', 'detailInfoTone' => 'bg-primary-subtle text-primary'])
                                @include('partials.modal.detail-info-card', ['detailInfoColClass' => 'col-md-6', 'detailInfoLabel' => 'Acara', 'detailInfoValueId' => 'adminReservationDetailEvent', 'detailInfoIcon' => 'ti ti-ticket', 'detailInfoTone' => 'bg-secondary-subtle text-secondary'])
                                @include('partials.modal.detail-info-card', ['detailInfoColClass' => 'col-md-6', 'detailInfoLabel' => 'Estimasi Orang', 'detailInfoValueId' => 'adminReservationDetailEstPerson', 'detailInfoIcon' => 'ti ti-users', 'detailInfoTone' => 'bg-success-subtle text-success'])
                                @include('partials.modal.detail-info-card', ['detailInfoColClass' => 'col-md-6', 'detailInfoLabel' => 'Tarif Sewa', 'detailInfoValueId' => 'adminReservationDetailTotalPrice', 'detailInfoIcon' => 'ti ti-receipt-2', 'detailInfoTone' => 'bg-danger-subtle text-danger'])
                                @include('partials.modal.detail-info-card', ['detailInfoColClass' => 'col-md-6', 'detailInfoLabel' => 'UMKM', 'detailInfoValueId' => 'adminReservationDetailUmkm', 'detailInfoIcon' => 'ti ti-building-store', 'detailInfoTone' => 'bg-primary-subtle text-primary'])
                                @include('partials.modal.detail-info-card', ['detailInfoColClass' => 'col-12', 'detailInfoLabel' => 'Catatan Admin', 'detailInfoValueId' => 'adminReservationDetailNotes', 'detailInfoIcon' => 'ti ti-notebook', 'detailInfoTone' => 'bg-warning-subtle text-warning', 'detailInfoValueClass' => 'fw-semibold admin-reservation-detail-notes'])
                            </div>
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="border rounded-3 p-3">
                            <h6 class="fw-semibold mb-3">Kelengkapan Berkas</h6>
                            <div class="row g-3">
                                @include('partials.modal.detail-info-card', ['detailInfoColClass' => 'col-md-6', 'detailInfoLabel' => 'KTP', 'detailInfoValueId' => 'adminReservationDetailKtpFile', 'detailInfoIcon' => 'ti ti-id-badge-2', 'detailInfoTone' => 'bg-success-subtle text-success'])
                                @include('partials.modal.detail-info-card', ['detailInfoColClass' => 'col-md-6', 'detailInfoLabel' => 'Permohonan', 'detailInfoValueId' => 'adminReservationDetailApplicationFile', 'detailInfoIcon' => 'ti ti-file-description', 'detailInfoTone' => 'bg-warning-subtle text-warning'])
                                @include('partials.modal.detail-info-card', ['detailInfoColClass' => 'col-md-6', 'detailInfoWrapperId' => 'adminReservationDetailUmkmFileWrapper', 'detailInfoLabel' => 'Kerjasama UMKM', 'detailInfoValueId' => 'adminReservationDetailUmkmFile', 'detailInfoIcon' => 'ti ti-building-store', 'detailInfoTone' => 'bg-info-subtle text-info'])
                                @include('partials.modal.detail-info-card', ['detailInfoColClass' => 'col-md-6', 'detailInfoWrapperId' => 'adminReservationDetailPaymentFileWrapper', 'detailInfoLabel' => 'Bukti Bayar', 'detailInfoValueId' => 'adminReservationDetailPaymentFile', 'detailInfoIcon' => 'ti ti-cash', 'detailInfoTone' => 'bg-primary-subtle text-primary'])
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="application/json" id="admin-gedung-reservation-config">
{!! json_encode([
    'messages' => $messages,
    'reservations' => $normalizeReservations,
    'canDeleteReservation' => $canDeleteReservation,
], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
</script>
@endsection
