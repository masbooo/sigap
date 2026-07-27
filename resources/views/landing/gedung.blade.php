@extends('layouts.landing')

@section('content')

@php
    $regionOrder = ['Pusat', 'Timur', 'Selatan', 'Barat', 'Utara'];

    $regionMeta = [
        'Pusat' => [
            'label' => 'Surabaya Pusat',
            'icon' => 'ti ti-medical-cross-circle fs-7 me-2',
        ],
        'Timur' => [
            'label' => 'Surabaya Timur',
            'icon' => 'ti ti-map-east fs-7 me-2',
        ],
        'Selatan' => [
            'label' => 'Surabaya Selatan',
            'icon' => 'ti ti-map-south fs-7 me-2',
        ],
        'Barat' => [
            'label' => 'Surabaya Barat',
            'icon' => 'ti ti-map-west fs-7 me-2',
        ],
        'Utara' => [
            'label' => 'Surabaya Utara',
            'icon' => 'ti ti-map-north fs-7 me-2',
        ],
    ];

    $defaultBuildingPhoto = asset('assets/upload/gedung/foto/Default.jpg');

    $normalizeDistrictLabel = static function ($value) {
        $normalized = preg_replace('/\s+/', ' ', trim((string) $value));

        return $normalized !== '' ? $normalized : 'Tidak Diketahui';
    };

    $normalizeDistrictKey = static function ($value) use ($normalizeDistrictLabel) {
        return strtolower($normalizeDistrictLabel($value));
    };
@endphp

<section class="bg-primary-subtle py-14">
    <div class="container-fluid">
        <div class="text-center">
            <p class="text-primary fs-4 fw-bolder mb-2" data-aos="fade-up" data-aos-delay="200" data-aos-duration="1000">
                GEDUNG
            </p>
            <h1 class="fw-bolder fs-12 mb-3" data-aos="fade-up" data-aos-delay="400" data-aos-duration="1000">
                Jelajahi Gedung Kami!
            </h1>
        </div>
    </div>
</section>

<section class="shadow-sm" data-aos="fade" data-aos-delay="800" data-aos-duration="1000">
    <div class="container-fluid">
        <ul class="nav team-tab nav-tabs flex-nowrap overflow-x-auto gedung-region-tabs" id="gedungRegionTab" role="tablist">
            @foreach ($regionOrder as $index => $region)
                @php
                    $regionData = $gedungGroup[$region] ?? [];
                    $regionKey = strtolower($region);
                    $regionLabel = $regionData['label'] ?? ($regionMeta[$region]['label'] ?? ('Surabaya ' . $region));
                    $iconClass = $regionMeta[$region]['icon'] ?? 'ti ti-map fs-7 me-2';
                    $totalBuildings = count($regionData['buildings'] ?? []);
                @endphp

                <li class="nav-item" role="presentation">
                    <button
                        class="nav-link py-3 px-3 fw-semibold w-100 {{ $index === 0 ? 'active' : '' }} {{ $index !== count($regionOrder) - 1 ? 'border-end' : '' }} justify-content-center d-flex align-items-center rounded-0 region-tab"
                        id="tab-{{ $regionKey }}"
                        data-region="{{ $region }}"
                        data-bs-toggle="tab"
                        data-bs-target="#pane-{{ $regionKey }}"
                        type="button"
                        role="tab"
                        aria-controls="pane-{{ $regionKey }}"
                        aria-selected="{{ $index === 0 ? 'true' : 'false' }}"
                    >
                        <i class="{{ $iconClass }}"></i>
                        {{ $regionLabel }}
                        <span class="ms-2 badge {{ $totalBuildings > 0 ? 'bg-success' : 'bg-danger' }} text-white">
                            {{ $totalBuildings }}
                        </span>
                    </button>
                </li>
            @endforeach
        </ul>
    </div>

    <div class="container-fluid py-3 py-lg-3">
        <div class="tab-content" id="gedungRegionTabContent">
            @foreach ($regionOrder as $index => $region)
                @php
                    $regionData = $gedungGroup[$region] ?? [];
                    $regionKey = strtolower($region);
                    $buildings = $regionData['buildings'] ?? [];
                    $hasBuildings = !empty($buildings);
                    $buildingCount = count($buildings);
                    $regionLabel = $regionData['label'] ?? ($regionMeta[$region]['label'] ?? ('Surabaya ' . $region));
                    $districtOptions = [];

                    foreach ($buildings as $buildingItem) {
                        $districtName = $normalizeDistrictLabel($buildingItem['district'] ?? '');
                        $districtKey = $normalizeDistrictKey($districtName);

                        if (!isset($districtOptions[$districtKey])) {
                            $districtOptions[$districtKey] = [
                                'key' => $districtKey,
                                'name' => $districtName,
                                'count' => 0,
                            ];
                        }

                        $districtOptions[$districtKey]['count']++;
                    }

                    uasort($districtOptions, static function ($left, $right) {
                        return strnatcasecmp($left['name'], $right['name']);
                    });

                    $districtTotal = count($districtOptions);
                @endphp

                <div
                    class="tab-pane fade {{ $index === 0 ? 'show active' : '' }}"
                    id="pane-{{ $regionKey }}"
                    role="tabpanel"
                    aria-labelledby="tab-{{ $regionKey }}"
                    tabindex="0"
                >
                    @if ($hasBuildings)
                        <div class="row g-4">
                            <div class="col-12">
                                <div class="card border-0 shadow-sm bg-light-subtle">
                                    <div class="card-body p-4">
                                        <div class="row g-4 align-items-end">
                                            <div class="col-xl-5 col-lg-6">
                                                <label class="form-label text-danger mb-2">
                                                    <b>Pilih Kecamatan</b>
                                                </label>
                                                <select
                                                    class="form-select district-select"
                                                    id="district-select-{{ $regionKey }}"
                                                    data-region-key="{{ $regionKey }}"
                                                >
                                                    <option value="">Semua Kecamatan</option>
                                                    @foreach ($districtOptions as $districtOption)
                                                        <option value="{{ $districtOption['key'] }}">
                                                            {{ $districtOption['name'] }} ({{ $districtOption['count'] }})
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            <div class="col-xl-7 col-lg-6 ms-lg-auto">
                                                <div class="row g-3 justify-content-lg-end">
                                                    <div class="col-sm-6 col-xl-4">
                                                        <div class="border rounded-3 bg-warning-subtle h-100 p-3">
                                                            <div class="small text-muted mb-1">Kecamatan Tersedia</div>
                                                            <div class="fw-semibold">{{ $districtTotal }} Kecamatan</div>
                                                        </div>
                                                    </div>

                                                    <div class="col-sm-6 col-xl-4">
                                                        <div class="border rounded-3 bg-success-subtle h-100 p-3">
                                                            <div class="small text-muted mb-1">Jumlah Gedung Serbaguna</div>
                                                            <div class="fw-semibold">{{ $buildingCount }} Gedung</div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12">
                                <div id="carousel-{{ $regionKey }}" class="carousel slide carousel-dark gedung-carousel" data-bs-ride="false">
                                    <div class="carousel-inner" id="carousel-inner-{{ $regionKey }}">
                                        @foreach ($buildings as $i => $building)
                                            @php
                                                $photo = !empty($building['building_photo'])
                                                    ? asset('assets/upload/' . ltrim($building['building_photo'], '/'))
                                                    : $defaultBuildingPhoto;

                                                $description = trim((string) ($building['description'] ?? ''));
                                                $districtKey = $normalizeDistrictKey($building['district'] ?? '');

                                                $detailItems = [
                                                    [
                                                        'icon' => 'ti ti-directions',
                                                        'tone' => 'bg-warning-subtle text-warning',
                                                        'label' => 'Alamat',
                                                        'value' => trim((string) ($building['address'] ?? '')) !== '' ? $building['address'] : '-',
                                                    ],
                                                    [
                                                        'icon' => 'ti ti-world',
                                                        'tone' => 'bg-primary-subtle text-primary',
                                                        'label' => 'Wilayah',
                                                        'value' => 'Surabaya ' . ($building['region'] ?? '-'),
                                                    ],
                                                    [
                                                        'icon' => 'ti ti-map-2',
                                                        'tone' => 'bg-secondary-subtle text-secondary',
                                                        'label' => 'Kecamatan',
                                                        'value' => $normalizeDistrictLabel($building['district'] ?? '-'),
                                                    ],
                                                    [
                                                        'icon' => 'ti ti-map-pin',
                                                        'tone' => 'bg-danger-subtle text-danger',
                                                        'label' => 'Kelurahan',
                                                        'value' => $building['subdistrict'] ?? '-',
                                                    ],
                                                    [
                                                        'icon' => 'ti ti-dimensions',
                                                        'tone' => 'bg-success-subtle text-success',
                                                        'label' => 'Luas Bangunan',
                                                        'value' => number_format((float) ($building['building_area'] ?? 0), 2, ',', '.') . ' m²',
                                                    ],
                                                    [
                                                        'icon' => 'ti ti-friends',
                                                        'tone' => 'bg-secondary-subtle text-secondary',
                                                        'label' => 'Estimasi Kapasitas',
                                                        'value' => '± ' . number_format((int) ($building['capacity'] ?? 0), 0, ',', '.') . ' orang',
                                                    ],
                                                    [
                                                        'icon' => 'ti ti-cash',
                                                        'tone' => 'bg-danger-subtle text-danger',
                                                        'label' => 'Tarif Per Sesi (5 Jam)',
                                                        'value' => 'Rp ' . number_format((float) ($building['session_price'] ?? 0), 0, ',', '.'),
                                                    ],
                                                    [
                                                        'icon' => 'ti ti-clock',
                                                        'tone' => 'bg-success-subtle text-success',
                                                        'label' => 'Tarif Per Jam',
                                                        'value' => 'Rp ' . number_format((float) ($building['perhour_price'] ?? 0), 0, ',', '.'),
                                                    ],
                                                ];
                                            @endphp

                                            <div class="carousel-item {{ $i === 0 ? 'active' : '' }}" data-district-key="{{ $districtKey }}">
                                                <div class="px-0 px-lg-4 pb-4">
                                                    <div class="card border-0 shadow-sm overflow-hidden">
                                                        <div class="row g-0 align-items-stretch">
                                                            <div class="col-lg-5 col-xl-4">
                                                                <div class="h-100 bg-light">
                                                                    <img
                                                                        src="{{ $photo }}"
                                                                        alt="{{ $building['building_name'] }}"
                                                                        class="w-100 h-100 gedung-carousel-image gedung-fallback-image"
                                                                        data-fallback-src="{{ $defaultBuildingPhoto }}"
                                                                    >
                                                                </div>
                                                            </div>

                                                            <div class="col-lg-7 col-xl-8">
                                                                <div class="card-body p-4 p-xl-5 h-100 d-flex flex-column justify-content-center">
                                                                    <div class="border-bottom pb-3 mb-4">
                                                                        <h3 class="fw-bold mb-2"><b>{{ $building['building_name'] ?? 'Tanpa Nama Gedung' }}</b></h3>
                                                                        <p class="text-muted mb-0">
                                                                            {{ $description !== '' ? $description : 'Gedung ini siap digunakan untuk pernikahan, rapat, maupun berbagai kegiatan lainnya' }}
                                                                        </p>
                                                                    </div>

                                                                    <div class="row g-3">
                                                                        @foreach ($detailItems as $detail)
                                                                            <div class="col-md-6">
                                                                                <div class="border rounded-3 bg-light-subtle h-100 p-3">
                                                                                    <div class="d-flex align-items-start gap-3">
                                                                                        <span
                                                                                            class="rounded-2 d-inline-flex align-items-center justify-content-center gedung-detail-icon {{ $detail['tone'] }}"
                                                                                        >
                                                                                            <i class="{{ $detail['icon'] }} fs-6"></i>
                                                                                        </span>

                                                                                        <div>
                                                                                            <div class="small text-muted mb-1">{{ $detail['label'] }}</div>
                                                                                            <div class="fw-semibold">{{ $detail['value'] }}</div>
                                                                                        </div>
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                        @endforeach
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>

                                    <div class="card border-0 bg-light-subtle d-none mx-0 mx-lg-4 mb-4" id="empty-filter-{{ $regionKey }}">
                                        <div class="card-body py-5 text-center text-danger">
                                            <b>Belum ada gedung pada kecamatan yang dipilih</b>
                                        </div>
                                    </div>

                                    @if ($buildingCount > 1)
                                        <button
                                            class="carousel-control-prev gedung-carousel-control w-auto ps-2 ps-md-3 ps-lg-4"
                                            type="button"
                                            data-bs-target="#carousel-{{ $regionKey }}"
                                            data-bs-slide="prev"
                                        >
                                            <i
                                                class="ti ti-chevron-left text-dark gedung-carousel-chevron"
                                                aria-hidden="true"
                                            ></i>
                                            <span class="visually-hidden">Previous</span>
                                        </button>

                                        <button
                                            class="carousel-control-next gedung-carousel-control w-auto pe-2 pe-md-3 pe-lg-4"
                                            type="button"
                                            data-bs-target="#carousel-{{ $regionKey }}"
                                            data-bs-slide="next"
                                        >
                                            <i
                                                class="ti ti-chevron-right text-dark gedung-carousel-chevron"
                                                aria-hidden="true"
                                            ></i>
                                            <span class="visually-hidden">Next</span>
                                        </button>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="card border-0 shadow-sm bg-light-subtle">
                            <div class="card-body py-5 text-center">
                                <span class="badge bg-danger-subtle text-danger mb-3"><b>Belum Tersedia</b></span>
                                <h4 class="fw-bold mb-2"><b>Cari Gedung Serbaguna di {{ $regionLabel }}?</b></h4>
                                <p class="text-muted mb-0">Data gedung untuk wilayah ini belum tersedia. Tunggu pembaruan berikutnya ya..</p>
                            </div>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
</section>

@endsection
