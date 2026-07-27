@extends('layouts.admin')

@section('content')
@php
    $roleContext = $roleContext ?? resolve_admin_role_context($admin ?? admin_user() ?? []);
    $pageMeta = resolve_admin_page_meta();
    $summaryCards = $summaryCards ?? [];
    $reportItems = $reportItems ?? [];
    $reportType = strtolower((string) ($reportType ?? 'umkm'));
    $reportLabel = trim((string) ($reportLabel ?? ($reportType === 'gedung' ? 'Gedung' : 'UMKM')));
    $scopeLabel = (string) ($roleContext['scope_label'] ?? 'Seluruh Sistem');

    $formatRatingBadgeClass = static function (?string $tone): string {
        $tone = trim((string) $tone);

        return $tone !== '' ? 'bg-' . $tone . '-subtle text-' . $tone : 'bg-secondary-subtle text-dark';
    };
@endphp

<div class="container-fluid">
    <div class="card bg-info-subtle shadow-none position-relative overflow-hidden mb-4">
        <div class="card-body px-4 py-3">
            <div class="row align-items-center">
                <div class="col-9">
                    <h4 class="fw-semibold mb-8">Laporan Rating {{ $reportLabel }}</h4>
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

    <div class="row g-4 mb-4">
        @foreach ($summaryCards as $card)
            @php
                $tone = $card['tone'] ?? 'primary';
            @endphp
            <div class="col-12 col-md-6 col-xxl-3">
                <div class="card border-0 zoom-in bg-{{ $tone }}-subtle shadow-none h-100">
                    <div class="card-body">
                        <div class="text-center">
                            <i class="{{ $card['icon'] ?? 'ti ti-star' }} fs-7 text-{{ $tone }} mb-3 d-inline-block"></i>
                            <p class="fw-medium fs-3 text-muted mb-1">{{ $card['label'] ?? 'Data' }}</p>
                            <h4 class="fw-semibold text-dark fs-8 mb-0"><b>{{ $card['value'] ?? '-' }}</b></h4>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
                <div>
                    <h5 class="fw-semibold mb-1"><b>LAPORAN RATING {{ strtoupper($reportLabel) }}</b></h5>
                    <p class="text-muted mb-0">
                        @if (($roleContext['scope_type'] ?? 'all') === 'all')
                            Menampilkan rating seluruh data aktif dalam sistem.
                        @else
                            Menampilkan rating data aktif sesuai wilayah {{ $scopeLabel }}.
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
                        data-empty-message="Tidak ada data yang tersedia dalam tabel Laporan Rating {{ $reportLabel }}"
                    >
                        <thead class="table-primary">
                            <tr>
                                <th>No</th>
                                @if ($reportType === 'gedung')
                                    <th>Gedung</th>
                                    <th>Lokasi</th>
                                    <th>Kapasitas</th>
                                @else
                                    <th>UMKM</th>
                                    <th>Produk</th>
                                    <th>Lokasi</th>
                                    <th>Gedung</th>
                                @endif
                                <th>Rating</th>
                                <th>Ulasan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($reportItems as $index => $item)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    @if ($reportType === 'gedung')
                                        <td class="admin-table-stack">
                                            <div class="fw-semibold">{{ $item['name_label'] ?? '-' }}</div>
                                            <div class="text-muted small">{{ $item['name_subtitle'] ?? '-' }}</div>
                                        </td>
                                        <td class="admin-table-stack">
                                            <div class="fw-semibold">{{ $item['location_label'] ?? '-' }}</div>
                                            <div class="text-muted small">{{ $item['location_subtitle'] ?? '-' }}</div>
                                        </td>
                                        <td>{{ $item['capacity_label'] ?? '-' }}</td>
                                    @else
                                        <td class="admin-table-stack">
                                            <div class="fw-semibold">{{ $item['name_label'] ?? '-' }}</div>
                                            <div class="text-muted small">{{ $item['name_subtitle'] ?? '-' }}</div>
                                        </td>
                                        <td class="admin-table-stack">
                                            <div class="fw-semibold">{{ $item['product_label'] ?? '-' }}</div>
                                        </td>
                                        <td class="admin-table-stack">
                                            <div class="fw-semibold">{{ $item['location_label'] ?? '-' }}</div>
                                            <div class="text-muted small">{{ $item['location_subtitle'] ?? '-' }}</div>
                                        </td>
                                        <td class="admin-table-stack">
                                            <div class="fw-semibold">{{ $item['building_count'] ?? 0 }} gedung</div>
                                            <div class="text-muted small">{{ $item['building_summary'] ?? '-' }}</div>
                                        </td>
                                    @endif
                                    <td>
                                        @php
                                            $ratingValue = $item['rating'] ?? null;
                                        @endphp
                                        <span class="badge {{ $formatRatingBadgeClass($item['rating_tone'] ?? null) }} px-3 py-2">
                                            {{ $ratingValue !== null ? $item['rating_label'] : '-' }}
                                            @if ($ratingValue !== null)
                                                <span class="ms-1">&#9733;</span>
                                            @endif
                                        </span>
                                    </td>
                                    <td data-order="{{ (int) ($item['review_count'] ?? 0) }}">
                                        {{ $item['review_count_label'] ?? '0' }}
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
@endsection
