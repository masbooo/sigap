@extends('layouts.landing')

@section('content')

@php
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
    $minBookingDate = $minBookingDate ?? (new DateTime('today'))->modify('+14 days')->format('Y-m-d');
    $minBookingDateLabel = $minBookingDateLabel ?? $formatReservationDisplayDate($minBookingDate);
@endphp

<section class="bg-primary-subtle py-14">
    <div class="container-fluid">
        <div class="text-center">
            <p class="text-primary fs-4 fw-bolder" data-aos="fade-up" data-aos-delay="200" data-aos-duration="1000">
                JADWAL
            </p>
            <h1 class="fw-bolder fs-12" data-aos="fade-up" data-aos-delay="400" data-aos-duration="1000">
                Pilih Jadwal Anda Sendiri!
            </h1>
        </div>
    </div>
</section>

<div class="container-fluid" data-aos="fade-up" data-aos-delay="800" data-aos-duration="1000">
    <div class="row mt-2">
        <div class="col-lg-12">
            <div class="card mt-2">
                <div class="card-body">
                    <div class="row g-4">

                        <div class="col-xl-4" id="user-reservation-summary-column">
                            <div class="card border-0 bg-primary-subtle shadow-none h-100">
                                <div class="card-body p-4">
                                    <span class="badge bg-primary-subtle text-primary mb-3"><b>INFORMASI JADWAL</b></span>
                                    <h3 class="fw-bold text-success mb-3"><b>Pastikan jadwal Anda tersedia! Silakan cek kalender di samping sebelum melakukan reservasi</b></h3>
                                    <div class="d-flex align-items-center gap-2 mb-3">
                                        <span class="d-inline-flex text-danger align-items-center justify-content-center">
                                            <i class="ti ti-alert-triangle fs-7"></i>
                                        </span>
                                        <h5 class="fw-bold mb-0"><b>Ketentuan Reservasi</b></h5>
                                    </div>
                                    <ul style="text-align: justify;">
                                        <li>Pengajuan reservasi hanya dapat dilakukan <b>minimal H-14 dari tanggal acara</b></li>
                                    </ul>
                                    <div class="border rounded-3 bg-white p-3 mb-4">
                                        <div class="d-flex align-items-center gap-3">
                                            <span class="round-48 rounded-circle d-flex align-items-center justify-content-center bg-light-primary text-primary">
                                                <i class="ti ti-calendar-event fs-7"></i>
                                            </span>
                                            <div>
                                                <div class="fw-semibold">Tanggal Minimum Reservasi</div>
                                                <div class="text-muted small">{{ $minBookingDateLabel }}</div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="d-flex align-items-center gap-2 mb-3">
                                        <span class="text-warning d-inline-flex align-items-center justify-content-center">
                                            <i class="ti ti-route fs-7"></i>
                                        </span>
                                        <h5 class="fw-bold mb-0"><b>Alur Cek Jadwal</b></h5>
                                    </div>
                                    <ol class="ps-3" style="text-align: justify;">
                                        <li><b class="text-info">Pilih Lokasi:</b> Tentukan wilayah, kecamatan, dan gedung sesuai kebutuhan Anda</li>
                                        <li><b class="text-info">Pilih Bulan:</b> Gunakan tombol panah (<b><i class="ti ti-chevron-left"></i></b>) atau (<b><i class="ti ti-chevron-right"></i></b>) untuk menentukan bulan yang diinginkan</li>
                                        <li><b class="text-info">Cek Tanggal:</b> Pastikan tanggal yang dipilih masih tersedia pada lokasi tersebut</li>
                                        <li><b class="text-info">Login:</b> Silakan masuk ke akun Anda untuk melanjutkan dan menyelesaikan proses reservasi</li>
                                    </ol>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-8">
                            <div class="calender-sidebar app-calendar sigap-calendar-layout" data-calendar-scope="landing">
                                <div class="col-12 px-0">
                                    <label class="form-label text-danger mb-2">
                                        <b>Pilih Lokasi</b>
                                    </label>

                                    <div class="row gx-3 gy-2 align-items-stretch">
                                        <div class="col-lg-4 col-md-4 col-12">
                                            <select class="form-select w-100" id="filterWilayah" data-calendar-role="region">
                                                <option value="">Pilih Wilayah...</option>
                                                @if (!empty($filterData))
                                                    @foreach ($filterData as $region)
                                                        <option value="{{ $region['region'] }}">
                                                            Surabaya {{ $region['region'] }} ({{ $region['district_count'] }})
                                                        </option>
                                                    @endforeach
                                                @endif
                                            </select>
                                        </div>

                                        <div class="col-lg-4 col-md-4 col-12">
                                            <select class="form-select w-100" id="filterKecamatan" data-calendar-role="district" disabled>
                                                <option value="">Pilih Kecamatan...</option>
                                            </select>
                                        </div>

                                        <div class="col-lg-4 col-md-4 col-12">
                                            <select class="form-select w-100" id="filterGedung" data-calendar-role="building" disabled>
                                                <option value="">Pilih Gedung...</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div id="calendar-landing" class="mt-3 sigap-calendar-instance" data-calendar-instance="landing"></div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
   </div>
</div>

<!-- Modal Detail Reservasi -->
<div class="modal fade" id="eventDetailModal" tabindex="-1" aria-labelledby="eventDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div
                class="modal-header text-white border-0"
                id="eventDetailHeader"
                style="background-color:#3b82f6;"
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

<script>
    window.sigapCalendarData = window.sigapCalendarData || {};
    window.sigapCalendarData.landing = {
        filterData: <?php echo json_encode($filterData ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>,
        events: <?php echo json_encode($events ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>,
        eventsUrl: "{{ base_url('jadwal/events') }}",
        refreshIntervalMs: 30000
    };

    window.jadwalFilterData = <?php echo json_encode($filterData ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
    window.jadwalEvents = <?php echo json_encode($events ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
</script>

@endsection
