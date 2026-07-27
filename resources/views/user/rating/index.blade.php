@extends('layouts.user')

@section('content')
@php
    $profileName = resolve_user_display_name($user ?? user());
    $ratingPageData = $pageData ?? [];
    $ratingGroups = array_values((array) ($ratingPageData['groups'] ?? []));
    $ratingNotifications = array_values((array) ($ratingPageData['notifications'] ?? []));
    $ratingStats = (array) ($ratingPageData['stats'] ?? []);
    $messages = $messages ?? ['success' => '', 'error' => ''];

    $formatNumber = static function ($value): string {
        return number_format((float) $value, 0, ',', '.');
    };

    $formatDate = static function (?string $date): string {
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

        return (int) date('d', $timestamp) . ' ' . ($monthNames[(int) date('n', $timestamp)] ?? date('F', $timestamp)) . ' ' . date('Y', $timestamp);
    };

    $toneClass = static function (?string $tone): string {
        $tone = trim((string) $tone);

        return $tone !== '' ? 'bg-' . $tone . '-subtle text-' . $tone : 'bg-secondary-subtle text-dark';
    };

    $resolveStatusClass = static function (?string $tone): string {
        $tone = trim((string) $tone);

        return $tone !== '' ? 'bg-' . $tone . '-subtle text-' . $tone : 'bg-secondary-subtle text-dark';
    };

    $defaultThumb = asset('assets/custom/images/backgrounds/profilebg.jpg');
    $resolveThumb = static function (?string $path) use ($defaultThumb): string {
        return resolve_public_upload_url($path, $defaultThumb);
    };

    $summaryCards = [
        [
            'label' => 'Acara Layak Dinilai',
            'value' => $formatNumber($ratingStats['reservation_count'] ?? 0),
            'tone' => 'primary',
            'icon' => 'ti ti-calendar-event',
        ],
        [
            'label' => 'Target Pending',
            'value' => $formatNumber($ratingStats['pending_count'] ?? 0),
            'tone' => 'warning',
            'icon' => 'ti ti-clock',
        ],
        [
            'label' => 'Target Selesai',
            'value' => $formatNumber($ratingStats['completed_count'] ?? 0),
            'tone' => 'success',
            'icon' => 'ti ti-check',
        ],
        [
            'label' => 'Total Target',
            'value' => $formatNumber($ratingStats['target_count'] ?? 0),
            'tone' => 'info',
            'icon' => 'ti ti-star',
        ],
    ];
@endphp

<div class="container-fluid user-rating-page" id="user-rating-page" data-user-rating-page="1">
    <div class="card bg-info-subtle shadow-none position-relative overflow-hidden mb-4">
        <div class="card-body px-4 py-3">
            <div class="row align-items-center">
                <div class="col-9">
                    <h4 class="fw-semibold mb-8">Rating Saya</h4>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item">
                                <span class="text-muted">Laporan</span>
                            </li>
                            <li class="breadcrumb-item active" aria-current="page">
                                Rating
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

    @if (($messages['success'] ?? '') !== '')
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ $messages['success'] }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if (($messages['error'] ?? '') !== '')
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            {{ $messages['error'] }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- <div class="row g-4 mb-4">
        @foreach ($summaryCards as $card)
            <div class="col-12 col-md-6 col-xxl-3">
                <div class="card border-0 zoom-in bg-{{ $card['tone'] }}-subtle shadow-none h-100">
                    <div class="card-body">
                        <div class="text-center">
                            <i class="{{ $card['icon'] }} fs-7 text-{{ $card['tone'] }} mb-3 d-inline-block"></i>
                            <p class="fw-medium fs-3 text-muted mb-1">{{ $card['label'] }}</p>
                            <h4 class="fw-semibold text-dark fs-8 mb-0"><b>{{ $card['value'] }}</b></h4>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div> --}}

    @if (empty($ratingGroups))
        <div class="card shadow-sm">
            <div class="card-body py-5 text-center">
                <img src="{{ asset('assets/custom/images/backgrounds/feedback.svg') }}" alt="Feedback" class="img-fluid mb-4 user-rating-empty-image">
                <h5 class="fw-semibold mb-2">Belum ada penilaian rating yang bisa diisi</h5>
                <p class="text-muted mb-0">Penilaian rating baru dapat dilakukan setelah tanggal acara terlewati</p>
            </div>
        </div>
    @else
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4">
            <div>
                <h5 class="fw-semibold mb-1"><b>DAFTAR RATING</b></h5>
                <p class="text-muted mb-0">Isi rating dan ulasan untuk gedung dan UMKM pada reservasi yang sudah selesai.</p>
            </div>
            <span class="badge bg-primary-subtle text-primary px-3 py-2">
                {{ $formatNumber($ratingStats['pending_count'] ?? 0) }} target pending
            </span>
        </div>

        @foreach ($ratingGroups as $group)
            @php
                $targets = array_values((array) ($group['targets'] ?? []));
                $targetCols = count($targets) > 1 ? 'col-lg-6' : 'col-12';
            @endphp
            <div class="card shadow-sm border-0 mb-4" id="rating-reservation-{{ $group['reservation_id'] ?? 0 }}">
                <div class="card-body">
                    <div class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-4">
                        <div>
                            <h5 class="fw-semibold mb-1">{{ $group['reservation_title'] ?? 'Rating' }}</h5>
                            <div class="text-muted small">{{ $group['reservation_subtitle'] ?? '-' }}</div>
                            <div class="text-muted small">{{ $group['reservation_date_label'] ?? '-' }} • {{ $group['reservation_location'] ?? '-' }}</div>
                        </div>
                        <div class="text-end">
                            <span class="badge {{ $resolveStatusClass($group['reservation_status_tone'] ?? null) }} px-3 py-2">
                                {{ $group['reservation_status'] ?? '-' }}
                            </span>
                            <div class="small text-muted mt-2">
                                {{ $formatNumber($group['pending_count'] ?? 0) }} target belum dinilai
                            </div>
                        </div>
                    </div>

                    <div class="row g-4">
                        @foreach ($targets as $target)
                            <div class="col-12 {{ $targetCols }}">
                                <form action="{{ url('user/rating') }}" method="POST" class="h-100" data-user-rating-form>
                                    {!! csrf_field() !!}
                                    <input type="hidden" name="reservation_id" value="{{ $group['reservation_id'] ?? 0 }}">
                                    <input type="hidden" name="target_type" value="{{ $target['target_type'] ?? '' }}">
                                    <input type="hidden" name="target_id" value="{{ $target['target_id'] ?? 0 }}">

                                    <div class="card rating-card border-0 shadow-sm h-100">
                                        <div class="card-body">
                                            <div class="d-flex flex-column flex-md-row gap-3">
                                                <img
                                                    src="{{ $resolveThumb($target['thumbnail_url'] ?? '') }}"
                                                    alt="{{ $target['name_label'] ?? 'Rating' }}"
                                                    class="rating-thumb"
                                                    data-fallback-src="{{ $defaultThumb }}"
                                                />

                                                <div class="flex-grow-1 min-w-0">
                                                    <div class="d-flex flex-wrap align-items-start justify-content-between gap-3">
                                                        <div>
                                                            <div class="text-muted small text-uppercase fw-semibold mb-1">{{ $target['target_type_label'] ?? 'Rating' }}</div>
                                                            <h6 class="fw-semibold mb-1">{{ $target['name_label'] ?? '-' }}</h6>
                                                            <div class="text-muted small">{{ $target['name_subtitle'] ?? '-' }}</div>
                                                        </div>
                                                        <span class="badge {{ !empty($target['is_completed']) ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning' }} rounded-pill px-3 py-2">
                                                            {{ !empty($target['is_completed']) ? 'Sudah dinilai' : 'Belum dinilai' }}
                                                        </span>
                                                    </div>

                                                    <div class="d-flex flex-wrap align-items-center gap-2 mt-4">
                                                        <span class="text-muted small fw-semibold">Rating</span>
                                                        <div class="rating-stars" data-user-rating-widget data-initial-rating="{{ (int) ($target['rating_value'] ?? 0) }}">
                                                            <input type="hidden" name="rating" value="{{ (int) ($target['rating_value'] ?? 0) }}" data-rating-input>
                                                            @for ($star = 1; $star <= 5; $star++)
                                                                <button type="button" class="rating-star" data-rating-value="{{ $star }}" aria-label="{{ $star }} bintang">★</button>
                                                            @endfor
                                                        </div>
                                                        <span class="badge {{ $toneClass($target['rating_tone'] ?? null) }} rounded-pill px-3 py-2" data-rating-label>
                                                            {{ $target['rating_label'] ?? 'Pilih rating' }}
                                                        </span>
                                                    </div>

                                                    <div class="mt-4">
                                                        <label class="form-label fw-semibold">Ulasan</label>
                                                        <textarea class="form-control" name="review" rows="4" maxlength="1000" placeholder="Ceritakan pengalaman Anda..." autocomplete="off">{{ $target['review'] ?? '' }}</textarea>
                                                    </div>

                                                    <div class="d-flex justify-content-end mt-4">
                                                        <button type="submit" class="btn btn-primary px-4">
                                                            <b>{{ $target['submit_label'] ?? 'Simpan Ulasan' }}</b>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endforeach
    @endif
</div>
@endsection
