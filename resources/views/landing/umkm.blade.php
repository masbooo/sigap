@extends('layouts.landing')

@section('content')

@php
    $items = $umkmItems ?? [];
    $filters = $umkmFilters ?? [
        'product' => '',
        'region' => '',
        'district' => '',
        'rating_min' => '',
    ];
    $options = $umkmFilterOptions ?? [
        'productTypes' => [],
        'regions' => [],
        'regionDistrictMap' => [],
        'allDistricts' => [],
        'ratingOptions' => [],
    ];
    $regionDistrictMap = $options['regionDistrictMap'] ?? [];
    $currentDistrictOptions = $filters['region'] !== ''
        ? ($regionDistrictMap[$filters['region']] ?? [])
        : [];
    $filteredCount = (int) ($umkmFilteredItemsCount ?? count($items));
    $allCount = (int) ($umkmAllItemsCount ?? count($items));
    $pagination = $umkmPagination ?? [
        'perPage' => 6,
        'currentPage' => 1,
        'totalPages' => 1,
        'totalItems' => $filteredCount,
        'from' => $filteredCount > 0 ? 1 : 0,
        'to' => count($items),
        'hasPreviousPage' => false,
        'hasNextPage' => false,
        'previousPage' => 1,
        'nextPage' => 1,
        'firstPage' => 1,
        'lastPage' => 1,
    ];

    $productUi = static function (string $label): array {
        if (stripos($label, 'rias') !== false) {
            return [
                'icon' => 'ti ti-brush',
                'tone' => 'bg-danger-subtle text-danger',
                'default_image' => asset('assets/uploads/umkm/rias/Rias.jpg'),
            ];
        }

        if (stripos($label, 'katering') !== false) {
            return [
                'icon' => 'ti ti-tools-kitchen-2',
                'tone' => 'bg-warning-subtle text-warning',
                'default_image' => asset('assets/uploads/umkm/katering/Katering.jpg'),
            ];
        }

        return [
            'icon' => 'ti ti-building-store',
            'tone' => 'bg-primary-subtle text-primary',
            'default_image' => asset('assets/uploads/Rias.jpg'),
        ];
    };

    $excerpt = static function (?string $text, int $limit = 88): string {
        $clean = trim((string) $text);

        if ($clean === '') {
            return 'UMKM ini siap mendukung kebutuhan acara di GSG dengan layanan yang dapat disesuaikan.';
        }

        if (function_exists('mb_strimwidth')) {
            return mb_strimwidth($clean, 0, $limit, '...');
        }

        return strlen($clean) > $limit ? substr($clean, 0, $limit - 3) . '...' : $clean;
    };

    $compactText = static function (?string $text): string {
        return trim(preg_replace('/\s+/', ' ', (string) $text));
    };

    $activeBadges = [];

    if (($filters['product'] ?? '') !== '') {
        $activeBadges[] = 'Kategori: ' . $filters['product'];
    }

    if (($filters['region'] ?? '') !== '') {
        $activeBadges[] = 'Wilayah: Surabaya ' . $filters['region'];
    }

    if (($filters['district'] ?? '') !== '') {
        $activeBadges[] = 'Lokasi: ' . $filters['district'];
    }

    if (($filters['rating_min'] ?? '') !== '') {
        $activeBadges[] = 'Rating: >= ' . $filters['rating_min'] . ' bintang';
    }

    $hasActiveFilters = !empty($activeBadges);
    $pageRangeStart = (int) ($pagination['from'] ?? 0);
    $pageRangeEnd = (int) ($pagination['to'] ?? 0);
    $pageRangeLabel = $pageRangeStart > 0 ? $pageRangeStart . '-' . $pageRangeEnd : '0';
    $resultSummary = 'Menampilkan ' . $pageRangeLabel . ' dari ' . $filteredCount . ' UMKM aktif';

    if ($hasActiveFilters) {
        $resultSummary .= ' sesuai filter';
    }

    if ($filteredCount !== $allCount) {
        $resultSummary .= ' (total ' . $allCount . ')';
    }

    $resultSummary .= '.';

    $buildPageUrl = static function (int $page) use ($filters): string {
        $query = [
            'product' => $filters['product'] ?? '',
            'region' => $filters['region'] ?? '',
            'district' => $filters['district'] ?? '',
            'rating_min' => $filters['rating_min'] ?? '',
        ];

        $query = array_filter($query, static function ($value): bool {
            return $value !== '' && $value !== null;
        });

        $url = $page > 1
            ? url('umkm/' . $page)
            : url('umkm');

        if (!empty($query)) {
            $url .= '?' . http_build_query($query);
        }

        return $url;
    };

    $currentPage = (int) ($pagination['currentPage'] ?? 1);
    $totalPages = (int) ($pagination['totalPages'] ?? 1);
    $paginationItems = [];

    if ($totalPages <= 5) {
        for ($page = 1; $page <= $totalPages; $page++) {
            $paginationItems[] = [
                'type' => 'page',
                'value' => $page,
            ];
        }
    } elseif ($currentPage <= 2) {
        foreach ([1, 2, 3] as $page) {
            $paginationItems[] = [
                'type' => 'page',
                'value' => $page,
            ];
        }

        $paginationItems[] = ['type' => 'ellipsis'];
    } elseif ($currentPage >= $totalPages - 1) {
        $paginationItems[] = ['type' => 'ellipsis'];

        foreach ([$totalPages - 2, $totalPages - 1, $totalPages] as $page) {
            $paginationItems[] = [
                'type' => 'page',
                'value' => $page,
            ];
        }
    } else {
        $paginationItems[] = ['type' => 'ellipsis'];

        foreach ([$currentPage - 1, $currentPage, $currentPage + 1] as $page) {
            $paginationItems[] = [
                'type' => 'page',
                'value' => $page,
            ];
        }

        $paginationItems[] = ['type' => 'ellipsis'];
    }
@endphp

<section class="bg-primary-subtle py-14">
    <div class="container-fluid text-center">
        <p class="text-primary fs-4 fw-bolder mb-2" data-aos="fade-up" data-aos-delay="200" data-aos-duration="1000">
            UMKM
        </p>
        <h1 class="fw-bolder fs-12 mb-3" data-aos="fade-up" data-aos-delay="400" data-aos-duration="1000">
            Pahlawan Ekonomi Kita!
        </h1>
    </div>
</section>

<section class="shadow-sm" data-aos="fade-up" data-aos-delay="800" data-aos-duration="1000">
    <div class="container-fluid py-4 py-lg-5">
        <div class="d-flex d-lg-none justify-content-between align-items-center gap-3 mb-4">
            <div>
                <h4 class="fw-bold mb-1"><b>Filter UMKM</b></h4>
                <p class="text-muted mb-0">Pilih kriteria lalu klik TERAPKAN</p>
            </div>
            <button
                class="btn btn-primary"
                type="button"
                data-bs-toggle="collapse"
                data-bs-target="#umkmFilterCollapse"
                aria-expanded="false"
                aria-controls="umkmFilterCollapse"
            >
                <i class="ti ti-adjustments-horizontal me-1"></i> Filter
            </button>
        </div>

        <div class="row g-4 align-items-start">
            <div class="col-lg-4 col-xl-3">
                <div class="collapse d-lg-block" id="umkmFilterCollapse">
                    <div class="card border-0 shadow-sm umkm-filter-panel position-sticky">
                        <div class="card-body p-4">
                            <form method="GET" action="{{ url('umkm') }}" id="umkm-filter-form">
                                <div class="mb-4">
                                    <div class="umkm-filter-section-title">Kategori</div>
                                    <div class="d-grid gap-2">
                                        <label class="form-check umkm-filter-radio m-0 px-3 py-3">
                                            <input class="form-check-input" type="radio" name="product" value="" {{ ($filters['product'] ?? '') === '' ? 'checked' : '' }}>
                                            <span class="form-check-label ms-2">
                                                <i class="ti ti-layout-grid-add me-1"></i> Semua Kategori
                                            </span>
                                        </label>

                                        @foreach (($options['productTypes'] ?? []) as $productOption)
                                            @php
                                                $productUiState = $productUi($productOption['label'] ?? $productOption['value']);
                                            @endphp
                                            <label class="form-check umkm-filter-radio m-0 px-3 py-3">
                                                <input
                                                    class="form-check-input"
                                                    type="radio"
                                                    name="product"
                                                    value="{{ $productOption['value'] }}"
                                                    {{ ($filters['product'] ?? '') === ($productOption['value'] ?? '') ? 'checked' : '' }}
                                                >
                                                <span class="form-check-label ms-2">
                                                    <i class="{{ $productUiState['icon'] }} me-1"></i> {{ $productOption['label'] }}
                                                </span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <label class="umkm-filter-section-title d-block" for="umkm-region-filter">Wilayah</label>
                                    <select class="form-select" name="region" id="umkm-region-filter">
                                        <option value="">Semua Wilayah</option>
                                        @foreach (($options['regions'] ?? []) as $region)
                                            <option value="{{ $region }}" {{ ($filters['region'] ?? '') === $region ? 'selected' : '' }}>
                                                Surabaya {{ $region }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="mb-4">
                                    <label class="umkm-filter-section-title d-block" for="umkm-district-filter">Lokasi</label>
                                    <select class="form-select" name="district" id="umkm-district-filter" {{ ($filters['region'] ?? '') === '' ? 'disabled' : '' }}>
                                        @if (($filters['region'] ?? '') === '')
                                            <option value="">Pilih Wilayah Terlebih Dahulu</option>
                                        @else
                                            <option value="">Pilih Lokasi</option>
                                            @foreach ($currentDistrictOptions as $districtOption)
                                                <option value="{{ $districtOption }}" {{ ($filters['district'] ?? '') === $districtOption ? 'selected' : '' }}>
                                                    {{ $districtOption }}
                                                </option>
                                            @endforeach
                                        @endif
                                    </select>
                                    <div class="small text-muted mt-2">Lokasi akan aktif setelah wilayah dipilih dan hanya menampilkan kecamatan yang punya GSG.</div>
                                </div>

                                <div class="mb-4">
                                    <label class="umkm-filter-section-title d-block" for="umkm-rating-filter">Rating</label>
                                    <select class="form-select" name="rating_min" id="umkm-rating-filter">
                                        @foreach (($options['ratingOptions'] ?? []) as $ratingOption)
                                            <option value="{{ $ratingOption['value'] }}" {{ ($filters['rating_min'] ?? '') === ($ratingOption['value'] ?? '') ? 'selected' : '' }}>
                                                {{ $ratingOption['label'] }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-primary">TERAPKAN</button>
                                    <a href="{{ url('umkm') }}" class="btn bg-danger-subtle text-danger waves-effect">RESET FILTER</a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-8 col-xl-9" id="umkm-list-section">
                <div class="umkm-list-header mb-4">
                    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                        <div>
                            <h4 class="fw-bold mb-1"><b>DAFTAR UMKM</b></h4>
                            <p class="text-muted mb-0">{{ $resultSummary }}</p>
                        </div>

                        @if (($pagination['totalPages'] ?? 1) > 1)
                            <div class="umkm-pagination-toolbar ms-lg-auto">
                                @include('landing.partials.umkm-pagination', [
                                    'pagination' => $pagination,
                                    'paginationItems' => $paginationItems,
                                    'currentPage' => $currentPage,
                                    'buildPageUrl' => $buildPageUrl,
                                ])
                            </div>
                        @endif
                    </div>

                    @if (!empty($activeBadges))
                        <div class="d-flex flex-wrap gap-2 justify-content-lg-end mt-3">
                            @foreach ($activeBadges as $badge)
                                <span class="badge bg-primary-subtle text-primary px-3 py-2">{{ $badge }}</span>
                            @endforeach
                        </div>
                    @endif
                </div>

                @if (!empty($items))
                    <div class="row g-4">
                        @foreach ($items as $item)
                            @php
                                $ui = $productUi($item['product_label'] ?? $item['product_type'] ?? 'UMKM');
                                $defaultImage = $ui['default_image'];
                                $imagePath = trim((string) ($item['pic_path'] ?? '')) !== ''
                                    ? asset('assets/uploads/' . ltrim((string) $item['pic_path'], '/'))
                                    : $defaultImage;
                                $ratingValue = $item['rating'] ?? null;
                                $ratingLabel = $ratingValue !== null ? number_format((float) $ratingValue, 1) : '-';
                                $ratingDataValue = $ratingValue !== null ? number_format((float) $ratingValue, 1, '.', '') : '-';
                                $reviewCount = max(0, (int) ($item['review_count'] ?? 0));
                                $homeLocationParts = array_filter([
                                    $item['home_district'] ?? '',
                                    ($item['home_region'] ?? '') !== '' ? 'Surabaya ' . $item['home_region'] : '',
                                ]);
                                $homeLocationLabel = !empty($homeLocationParts) ? implode(', ', $homeLocationParts) : '-';
                                $gsgDistrictLabel = !empty($item['gsg_districts']) ? implode(', ', $item['gsg_districts']) : '-';
                                $buildingLabel = !empty($item['building_names']) ? implode(', ', $item['building_names']) : '-';
                                $modalDescription = $compactText($item['description'] ?? '');
                                $addressLabel = $compactText($item['address'] ?? '');
                            @endphp

                            <div class="col-sm-6 col-xl-4">
                                <article class="card border-0 shadow-sm overflow-hidden umkm-list-card">
                                    <div class="position-relative">
                                        <img
                                            src="{{ $imagePath }}"
                                            alt="{{ $item['umkm_name'] }}"
                                            class="w-100 umkm-list-thumb umkm-fallback-image"
                                            data-fallback-src="{{ $defaultImage }}"
                                        >

                                        <div class="position-absolute top-0 start-0 end-0 p-3 d-flex justify-content-between align-items-start gap-2">
                                            <span class="badge {{ $ui['tone'] }} px-3 py-2">
                                                <i class="{{ $ui['icon'] }} me-1"></i> {{ $item['product_label'] }}
                                            </span>

                                            <span class="badge bg-success-subtle text-dark px-3 py-2 umkm-rating-badge ms-auto">
                                                <b>{{ $ratingLabel }}</b><span class="text-warning">&#9733;</span><span class="text-dark"> ({{ $reviewCount }})</span>
                                            </span>
                                        </div>
                                    </div>

                                    <div class="card-body p-3 d-flex flex-column umkm-card-body h-100">
                                        <div>
                                            <h5 class="fw-bold mb-1"><b>{{ $item['umkm_name'] }}</b></h5>
                                            <p class="small text-muted mb-0">By {{ $item['owner'] ?: '-' }}</p>
                                        </div>

                                        <div class="umkm-card-footer">
                                            <div class="small umkm-location-line">
                                                <i class="ti ti-map-pin text-danger me-1"></i> {{ $homeLocationLabel }}
                                            </div>

                                            <button
                                                type="button"
                                                class="btn bg-primary-subtle text-primary waves-effect w-100"
                                                data-bs-toggle="modal"
                                                data-bs-target="#umkmDetailModal"
                                                data-name="{{ $item['umkm_name'] }}"
                                                data-owner="{{ $item['owner'] ?: '-' }}"
                                                data-product="{{ $item['product_label'] }}"
                                                data-rating="{{ $ratingDataValue }}"
                                                data-reviews="{{ $reviewCount }}"
                                                data-phone="{{ $item['phone'] ?: '-' }}"
                                                data-home-location="{{ $homeLocationLabel }}"
                                                data-gsg-districts="{{ $gsgDistrictLabel }}"
                                                data-buildings="{{ $buildingLabel }}"
                                                data-address="{{ $addressLabel !== '' ? $addressLabel : '-' }}"
                                                data-description="{{ $modalDescription !== '' ? $modalDescription : 'UMKM ini siap mendukung kebutuhan acara di GSG dengan layanan yang dapat disesuaikan.' }}"
                                                data-image="{{ $imagePath }}"
                                                data-fallback-image="{{ $defaultImage }}"
                                            >
                                                <i class="ti ti-eye me-1"></i> DETAIL UMKM
                                            </button>
                                        </div>
                                    </div>
                                </article>
                            </div>
                        @endforeach
                    </div>

                    @if (($pagination['totalPages'] ?? 1) > 1)
                        <div class="d-flex justify-content-center justify-content-lg-end pt-2">
                            @include('landing.partials.umkm-pagination', [
                                'pagination' => $pagination,
                                'paginationItems' => $paginationItems,
                                'currentPage' => $currentPage,
                                'buildPageUrl' => $buildPageUrl,
                            ])
                        </div>
                    @endif
                @else
                    <div class="card border-0 shadow-sm bg-light-subtle">
                        <div class="card-body py-5 text-center">
                            <span class="badge bg-danger-subtle text-danger mb-3">Belum Ada Hasil</span>
                            <h4 class="fw-bold mb-2">Tidak ada UMKM yang cocok dengan filter saat ini</h4>
                            <p class="text-muted mb-0">Coba ubah kategori, wilayah, lokasi, atau rating lalu klik <b>Terapkan</b> lagi.</p>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</section>

<div class="modal fade" id="umkmDetailModal" tabindex="-1" aria-labelledby="umkmDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-0 pb-0 align-items-start">
                <div class="w-100">
                    <div class="d-flex justify-content-between align-items-start gap-2 flex-nowrap mb-3 umkm-modal-header-top">
                        <span class="badge bg-primary-subtle text-primary" id="umkm-detail-product">UMKM</span>

                        <div class="d-flex align-items-center gap-2 shrink-0 ms-auto umkm-modal-header-actions">
                            <span class="badge bg-success-subtle text-dark px-3 py-2 umkm-rating-inline" id="umkm-detail-rating">
                                -
                            </span>
                            <button type="button" class="btn-close umkm-modal-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                    </div>

                    <h4 class="modal-title fw-bold mb-1" id="umkmDetailModalLabel">Detail UMKM</h4>
                    <p class="text-muted mb-0" id="umkm-detail-owner">-</p>
                </div>
            </div>

            <div class="modal-body p-4">
                <div class="row g-4">
                    <div class="col-md-5">
                        <img src="" alt="UMKM" class="w-100 rounded-3 umkm-modal-image bg-light" id="umkm-detail-image">
                    </div>

                    <div class="col-md-7">
                        <div class="row g-3 umkm-modal-detail-list">
                            @include('partials.modal.detail-info-card', [
                                'detailInfoColClass' => 'col-12',
                                'detailInfoLabel' => 'Wilayah UMKM',
                                'detailInfoValueId' => 'umkm-detail-home-location',
                                'detailInfoIcon' => 'ti ti-map-pin',
                                'detailInfoTone' => 'bg-warning-subtle text-warning',
                            ])

                            @include('partials.modal.detail-info-card', [
                                'detailInfoColClass' => 'col-12',
                                'detailInfoLabel' => 'Gedung Terkait',
                                'detailInfoValueId' => 'umkm-detail-buildings',
                                'detailInfoIcon' => 'ti ti-building',
                                'detailInfoTone' => 'bg-danger-subtle text-danger',
                            ])

                            @include('partials.modal.detail-info-card', [
                                'detailInfoColClass' => 'col-12',
                                'detailInfoLabel' => 'Alamat UMKM',
                                'detailInfoValueId' => 'umkm-detail-address',
                                'detailInfoIcon' => 'ti ti-directions',
                                'detailInfoTone' => 'bg-success-subtle text-success',
                            ])

                            @include('partials.modal.detail-info-card', [
                                'detailInfoColClass' => 'col-12',
                                'detailInfoLabel' => 'Kontak UMKM',
                                'detailInfoValueId' => 'umkm-detail-phone',
                                'detailInfoIcon' => 'ti ti-phone',
                                'detailInfoTone' => 'bg-info-subtle text-info',
                            ])

                            @include('partials.modal.detail-info-card', [
                                'detailInfoColClass' => 'col-12',
                                'detailInfoLabel' => 'Deskripsi',
                                'detailInfoValueId' => 'umkm-detail-description',
                                'detailInfoIcon' => 'ti ti-file-text',
                                'detailInfoTone' => 'bg-secondary-subtle text-secondary',
                            ])
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div
    id="umkm-region-district-map"
    class="d-none"
    data-map="{{ htmlspecialchars(json_encode($regionDistrictMap, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8') }}"
></div>

@endsection
