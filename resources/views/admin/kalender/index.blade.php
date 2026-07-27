@extends('layouts.admin')

@section('content')
@php
    $roleContext = $roleContext ?? resolve_admin_role_context($admin ?? admin_user() ?? []);
    $pageMeta = resolve_admin_page_meta();
    $dashboardCards = $dashboardCards ?? [];
    $reservationStats = $reservationStats ?? [
        'total' => 0,
        'proses' => 0,
        'selesai' => 0,
        'batal' => 0,
    ];
@endphp

<div class="container-fluid">
    <div class="card bg-info-subtle shadow-none position-relative overflow-hidden mb-4">
        <div class="card-body px-4 py-3">
            <div class="row align-items-center">
                <div class="col-9">
                    <h4 class="fw-semibold mb-8">Data Kalender</h4>
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
            @endphp

            <div class="col-sm-6 col-xl-3 d-flex align-items-stretch">
                <div class="card w-100 border-0 bg-{{ $tone }}-subtle shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <span class="d-flex align-items-center justify-content-center rounded-circle bg-white text-{{ $tone }}" style="width:48px;height:48px;">
                                <i class="{{ $card['icon'] ?? 'ti ti-layout-dashboard' }} fs-7"></i>
                            </span>
                            <span class="badge bg-white text-{{ $tone }}">{{ $card['label'] ?? 'Data' }}</span>
                        </div>

                        <h3 class="fw-bold mb-1">{{ $card['value'] ?? 0 }}</h3>
                        <p class="mb-0 text-muted">{{ $card['description'] ?? '' }}</p>
                    </div>
                </div>
            </div>
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
