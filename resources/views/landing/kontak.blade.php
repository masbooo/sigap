@extends('layouts.landing')

@section('content')

@php
    $regionOrder = ['Pusat', 'Timur', 'Selatan', 'Barat', 'Utara'];

    $regionIcons = [
        'Pusat'   => 'ti ti-medical-cross-circle fs-7 me-2',
        'Timur'   => 'ti ti-map-east fs-7 me-2',
        'Selatan' => 'ti ti-map-south fs-7 me-2',
        'Barat'   => 'ti ti-map-west fs-7 me-2',
        'Utara'   => 'ti ti-map-north fs-7 me-2',
    ];
@endphp

<section class="bg-primary-subtle py-14">
    <div class="container-fluid">
        <div class="text-center">
            <p class="text-primary fs-4 fw-bolder" data-aos="fade-up" data-aos-delay="200" data-aos-duration="1000">
                KONTAK
            </p>
            <h1 class="fw-bolder fs-12" data-aos="fade-up" data-aos-delay="400" data-aos-duration="1000">
                Siap Melayani Anda!
            </h1>
        </div>
    </div>
</section>

<section class="shadow-sm" data-aos="fade" data-aos-delay="800" data-aos-duration="1000">
    <div class="container-fluid">
        <ul class="nav team-tab nav-tabs flex-nowrap overflow-x-auto gedung-region-tabs" id="contactRegionTab" role="tablist">
            @foreach ($regionOrder as $index => $region)
                @php
                    $regionKey = strtolower($region);
                    $label = $contactGroup[$region]['label'] ?? ('Surabaya ' . $region);
                    $iconClass = $regionIcons[$region] ?? 'ti ti-map fs-7 me-2';
                    $totalDistricts = count($contactGroup[$region]['contacts'] ?? []);
                @endphp

                <li class="nav-item" role="presentation">
                    <button
                        class="nav-link py-3 px-3 fw-semibold w-100 {{ $index === 0 ? 'active' : '' }} {{ $index !== count($regionOrder) - 1 ? 'border-end' : '' }} justify-content-center d-flex align-items-center rounded-0"
                        id="contact-tab-{{ $regionKey }}"
                        data-bs-toggle="tab"
                        data-bs-target="#contact-pane-{{ $regionKey }}"
                        data-region="{{ $region }}"
                        type="button"
                        role="tab"
                        aria-controls="contact-pane-{{ $regionKey }}"
                        aria-selected="{{ $index === 0 ? 'true' : 'false' }}"
                    >
                        <i class="{{ $iconClass }}"></i>
                        {{ $label }}
                        <span class="ms-2 badge {{ $totalDistricts > 0 ? 'bg-success' : 'bg-danger' }} text-white">
                            {{ $totalDistricts }}
                        </span>
                    </button>
                </li>
            @endforeach
        </ul>
    </div>

    <div class="container-fluid mt-4">
        <div class="contact-map-wrapper rounded-3 overflow-hidden shadow-sm">
            <div id="contact-map" style="width: 100%; height: 450px;"></div>
        </div>
    </div>
</section>

<script>
    window.contactMapDefault = {!! json_encode($mapDefault ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!};
    window.contactAllData = {!! json_encode($allContacts ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!};
    window.contactGroupByRegion = {!! json_encode($contactGroup ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!};
</script>

@endsection