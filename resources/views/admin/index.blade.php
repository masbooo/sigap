@extends('layouts.admin')

@section('content')
@php
    $roleContext = $roleContext ?? resolve_admin_role_context($admin ?? admin_user() ?? []);
    $pageMeta = resolve_admin_page_meta();
    $reservations = $reservations ?? [];
    $summaryCards = $summaryCards ?? [];
    $dashboardCards = $dashboardCards ?? [];
    $messages = $messages ?? ['success' => '', 'error' => ''];
    $statusClasses = $statusClasses ?? reservation_status_class_lookup();
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
                    <h4 class="fw-semibold mb-8">Data Infografis</h4>
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

    <div class="row">
        @foreach ($dashboardCards as $card)
            @php
                $tone = $card['tone'] ?? 'primary';
                $textTone = match ($tone) {
                    'dark' => 'light',
                    'light' => 'dark',
                    default => $tone,
                };
                $value = $card['value'] ?? 0;
                $rawIcon = trim((string) ($card['icon'] ?? ''));
                $isAbsoluteIconUrl = preg_match('~^(?:https?:)?//|^data:|^/~i', $rawIcon) === 1;
                $isImagePath = preg_match('~\.(svg|png|jpe?g|gif|webp)(\?.*)?$~i', $rawIcon) === 1;
                $iconSrc = asset('assets/custom/images/svgs/icon-connect.svg');
                $iconClass = $rawIcon !== '' && !$isAbsoluteIconUrl && !$isImagePath
                    ? $rawIcon
                    : 'ti ti-layout-dashboard';

                if ($rawIcon !== '') {
                    if ($isAbsoluteIconUrl) {
                        $iconSrc = $rawIcon;
                    } elseif ($isImagePath) {
                        $iconSrc = url(ltrim(preg_replace('~^\./~', '', $rawIcon), '/'));
                    }
                }

                $displayValue = is_numeric($value)
                    ? number_format((float) $value, 0, ',', '.')
                    : (string) $value;
            @endphp

            <div class="col-lg-2 col-md-3 col-6">
                <div class="card border-0 zoom-in bg-{{ $tone }}-subtle shadow-none">
                    <div class="card-body">
                        <div class="text-center">
                            @if ($isAbsoluteIconUrl || $isImagePath)
                                <img src="{{ $iconSrc }}" class="mb-3" alt="{{ $card['label'] ?? 'Data' }}"/>
                            @else
                                <i class="{{ $iconClass }} d-inline-block text-{{ $textTone }} mb-3" style="font-size: 2.25rem;"></i>
                            @endif
                            <p class="fw-medium fs-3 text-{{ $textTone }} mb-1">{{ $card['label'] ?? 'Data' }}</p>
                            <h4 class="fw-semibold text-dark fs-8 mb-0"><b>{{ $displayValue }}</b></h4>
                        </div>
                    </div>
                </div>
            </div>

            {{-- <div class="col-lg-2 col-md-3 col-6">
                <div class="card border-bottom border-{{ $tone }}">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <h4 class="fs-8"><b>{{ $card['value'] ?? 0 }}</b></h4>
                                <p class="fw-medium fs-4 text-{{ $tone }} mb-0">{{ $card['label'] ?? 'Data' }}</p>
                            </div>
                            <span class="text-{{ $tone }} display-6">
                                <i class="{{ $card['icon'] ?? 'ti ti-layout-dashboard' }}"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div> --}}

        @endforeach
    </div>

    <div class="row">
        <div class="col-xl-7 d-flex align-items-stretch">
            <div class="card w-100 shadow-sm">
                <div class="card-body">
                    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
                        <div>
                            <h5 class="card-title fw-semibold mb-1">Ringkasan Status Reservasi</h5>
                            <p class="card-subtitle mb-0 text-muted">
                                Statistik reservasi sesuai cakupan akses {{ strtolower($roleContext['role_label'] ?? 'admin') }}.
                            </p>
                        </div>

                        <span class="badge bg-primary-subtle text-primary px-3 py-2">
                            Total {{ $reservationStats['total'] ?? 0 }} reservasi
                        </span>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="border rounded-3 p-3 h-100">
                                <div class="text-warning fw-semibold mb-1">ALUR AKTIF / MENUNGGU TINDAK LANJUT</div>
                                <div class="fs-6 fw-bold">{{ $reservationStats['proses'] ?? 0 }}</div>
                                <div class="small text-muted">Mencakup reservasi baru, perbaikan berkas, verifikasi, dan proses pembayaran.</div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="border rounded-3 p-3 h-100">
                                <div class="text-success fw-semibold mb-1">LUNAS / SELESAI</div>
                                <div class="fs-6 fw-bold">{{ $reservationStats['selesai'] ?? 0 }}</div>
                                <div class="small text-muted">Reservasi yang sudah tuntas diproses.</div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="border rounded-3 p-3 h-100">
                                <div class="text-danger fw-semibold mb-1">DITOLAK / DIBATALKAN</div>
                                <div class="fs-6 fw-bold">{{ $reservationStats['batal'] ?? 0 }}</div>
                                <div class="small text-muted">Reservasi yang dibatalkan dalam cakupan akses.</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-5 d-flex align-items-stretch">
            <div class="card w-100 shadow-sm">
                <div class="card-body">
                    <h5 class="card-title fw-semibold mb-1">Fokus Akses {{ $roleContext['role_label'] ?? 'Admin' }}</h5>
                    <p class="card-subtitle text-muted mb-4">
                        Ringkasan tugas utama yang saat ini tersedia untuk role ini.
                    </p>

                    @foreach (($roleContext['focus_items'] ?? []) as $focusItem)
                        <div class="d-flex align-items-start gap-3 border rounded-3 p-3 mb-3">
                            <span class="d-flex align-items-center justify-content-center rounded-circle bg-primary-subtle text-primary" style="width:42px;height:42px;">
                                <i class="ti ti-checklist fs-6"></i>
                            </span>
                            <div class="small text-dark">{{ $focusItem }}</div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <h5 class="card-title fw-semibold mb-1">Akses Menu Saat Ini</h5>
            <p class="card-subtitle text-muted mb-4">
                Struktur menu yang tampil pada sidebar disesuaikan otomatis berdasarkan `role_id` admin.
            </p>

            <div class="row g-3">
                @foreach (($roleContext['sidebar_sections'] ?? []) as $section)
                    <div class="col-md-6 col-xl-3">
                        <div class="border rounded-3 h-100 p-3">
                            <div class="fw-semibold text-primary mb-2">{{ $section['heading'] ?? 'Menu' }}</div>

                            @foreach (($section['items'] ?? []) as $item)
                                <div class="d-flex align-items-center justify-content-between gap-2 py-2 border-top">
                                    <div class="small text-dark">{{ $item['label'] ?? 'Menu' }}</div>

                                    @if (!empty($item['badge']))
                                        <span class="badge {{ $item['badge_class'] ?? 'bg-light-subtle text-dark' }}">
                                            {{ $item['badge'] }}
                                        </span>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endsection
