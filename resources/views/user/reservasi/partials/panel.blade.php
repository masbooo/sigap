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
    $userAddress = trim((string) ($user['address'] ?? ''));
    $selectedUmkmId = (string) ($oldInput['umkm_id'] ?? '');
    $normalizeUploadPath = static function ($relativePath): string {
        $normalizedPath = ltrim(str_replace('\\', '/', trim((string) $relativePath)), '/');

        if (str_starts_with($normalizedPath, 'user/identity/')) {
            return 'user/identitas/' . substr($normalizedPath, strlen('user/identity/'));
        }

        return $normalizedPath;
    };
    $resolveUploadedFilePreview = static function ($relativePath) use ($normalizeUploadPath): array {
        $normalizedPath = $normalizeUploadPath($relativePath);
        if ($normalizedPath === '') {
            return [
                'url' => '',
                'name' => '',
                'extension' => '',
                'type_label' => 'FILE',
                'is_image' => false,
            ];
        }

        $filename = basename($normalizedPath);
        $extension = strtolower((string) pathinfo($filename, PATHINFO_EXTENSION));
        $isImage = in_array($extension, ['jpg', 'jpeg', 'png'], true);

        $typeLabel = $extension !== '' ? strtoupper($extension) : 'FILE';

        return [
            'url' => base_url('assets/uploads/' . $normalizedPath),
            'name' => $filename,
            'extension' => $extension,
            'type_label' => $typeLabel,
            'is_image' => $isImage,
        ];
    };
    $emptyUploadPreview = [
        'url' => '',
        'name' => '',
        'extension' => '',
        'type_label' => 'FILE',
        'is_image' => false,
    ];
    $existingRequestUpload = $resolveUploadedFilePreview($editingReservation['form_path'] ?? '');
    $existingIdentityUpload = $resolveUploadedFilePreview($user['id_path'] ?? '');
    $editingReservationStatusKey = $isEditMode
        ? reservation_status_display_key($editingReservation['status'] ?? 'RESERVASI BARU')
        : 'RESERVASI BARU';
    $isReservationRevisionMode = $isEditMode && $editingReservationStatusKey === 'BERKAS RESERVASI TIDAK SESUAI';
    $isVerificationRevisionMode = $isEditMode && $editingReservationStatusKey === 'BERKAS VERIFIKASI TIDAK SESUAI';
    $isUmkmFollowUpMode = $isEditMode && in_array($editingReservationStatusKey, ['KERJASAMA UMKM', 'BERKAS VERIFIKASI TIDAK SESUAI'], true);
    if ($isUmkmFollowUpMode) {
        $existingRequestUpload = $emptyUploadPreview;
    }
    $selectedSessionOption = (string) ($oldInput['session_option'] ?? '');
    $selectedStartTime = trim((string) ($oldInput['start_time'] ?? ''));
    $selectedEndTime = trim((string) ($oldInput['end_time'] ?? ''));
    $isCustomSessionSelected = $selectedSessionOption === 'lainnya';
    $userNik = trim((string) ($user['nik'] ?? ''));
    $hasApplicantName = $profileName !== '' && $profileName !== '-';
    $hasApplicantNik = $userNik !== '' && $userNik !== '-';
    $hasApplicantPhone = trim((string) ($user['phone'] ?? '')) !== '' && trim((string) ($user['phone'] ?? '-')) !== '-';
    $hasApplicantAddress = $userAddress !== '' && $userAddress !== '-';
    $reservationFormAction = $isEditMode ? base_url('user/reservasi/update') : base_url('user/reservasi');
    $reservationFormTitle = $isUmkmFollowUpMode
        ? 'FORM KERJASAMA UMKM'
        : ($isEditMode ? 'FORM RUBAH RESERVASI' : 'FORM RESERVASI BARU');
    $requestUploadLabel = $isUmkmFollowUpMode ? 'Unggah Bukti Kerjasama UMKM' : 'Unggah Permohonan';
    $requestGalleryTitle = $isUmkmFollowUpMode ? 'Bukti Kerjasama UMKM' : 'File Permohonan';
    if ($isUmkmFollowUpMode) {
        $reservationFormDescription = 'Unggah bukti Kerjasama UMKM untuk melanjutkan reservasi';
    } elseif ($isEditMode) {
        $reservationFormDescription = 'Cek kembali perubahan data reservasi sebelum dikirim ulang';
    } else {
        $reservationFormDescription = 'Tanggal dan gedung akan terisi dari kalender serta filter lokasi yang Anda pilih';
    }
    $reservationSubmitLabel = $isUmkmFollowUpMode ? 'KIRIM BUKTI UMKM' : ($isEditMode ? 'SIMPAN' : 'RESERVASI');
    $shouldShowReservationForm = $isEditMode || $error !== '' || !empty($oldInput);
    if ($isUmkmFollowUpMode) {
        $reservationSelectionStatus = $selectedDate !== '' && $selectedBuildingLabel !== ''
            ? 'Kerjasama UMKM'
            : 'Menunggu data jadwal reservasi';
        $reservationSelectionHint = 'Unggah bukti Kerjasama UMKM untuk melanjutkan reservasi';
        $selectionStateCardClass = 'border border-info-subtle bg-info-subtle';
        $stageBadgeClass = 'bg-info-subtle text-info';
        $stageBadgeLabel = 'Kerjasama UMKM';
    } elseif ($isVerificationRevisionMode) {
        $reservationSelectionStatus = $selectedDate !== '' && $selectedBuildingLabel !== ''
            ? 'Revisi Verifikasi'
            : 'Menunggu data verifikasi diperbaiki';
        $reservationSelectionHint = 'Unggah ulang bukti Kerjasama UMKM sesuai catatan petugas agar reservasi kembali ke proses verifikasi.';
        $selectionStateCardClass = 'border border-warning-subtle bg-warning-subtle';
        $stageBadgeClass = 'bg-warning-subtle text-warning';
        $stageBadgeLabel = 'Revisi Verifikasi';
    } elseif ($isReservationRevisionMode) {
        $reservationSelectionStatus = $selectedDate !== '' && $selectedBuildingLabel !== ''
            ? 'Revisi Reservasi'
            : 'Menunggu data reservasi diperbaiki';
        $reservationSelectionHint = 'Sesuaikan data dan berkas mengikuti catatan petugas, lalu simpan perubahan setelah semuanya sesuai.';
        $selectionStateCardClass = 'border border-warning-subtle bg-warning-subtle';
        $stageBadgeClass = 'bg-warning-subtle text-warning';
        $stageBadgeLabel = 'Revisi Reservasi';
    } else {
        $reservationSelectionStatus = $selectedDate !== '' && $selectedBuildingLabel !== ''
            ? ($isEditMode ? 'Siap Diperbarui' : 'Siap Diajukan')
            : 'Menunggu pilihan tanggal dan gedung';
        $reservationSelectionHint = 'Klik tanggal pada kalender reservasi untuk mengisi form reservasi';
        $selectionStateCardClass = 'border bg-white';
        $stageBadgeClass = $isEditMode ? 'bg-primary-subtle text-primary' : 'bg-success-subtle text-success';
        $stageBadgeLabel = $isEditMode ? 'Rubah Reservasi' : 'Reservasi Baru';
    }
    $reservationFeedbackNotes = $isEditMode ? trim((string) ($editingReservation['notes'] ?? '')) : '';
    $requestUploadRequired = !$isEditMode || $isUmkmFollowUpMode;
    $requestUploadFeedback = $requestUploadRequired
        ? ($isUmkmFollowUpMode ? 'Unggah bukti Kerjasama UMKM terlebih dahulu' : 'Unggah file permohonan terlebih dahulu')
        : 'Unggah file permohonan baru bila ingin mengganti berkas sebelumnya';
    $requestUploadHelper = $requestUploadRequired
        ? ($isUmkmFollowUpMode
            ? 'Unggah ulang bukti Kerjasama UMKM dengan format JPG, JPEG, PNG, atau PDF dan ukuran maks. 1MB'
            : 'Format yang didukung: JPG, JPEG, PNG, PDF dan ukuran maks. 1MB')
        : 'Kosongkan bila tidak ada perubahan file. Format yang didukung: JPG, JPEG, PNG, PDF dan ukuran maks. 1MB';
    $reservationActionHelperText = $isUmkmFollowUpMode
        ? 'Klik Kirim Bukti UMKM setelah selesai unggah file'
        : ($isEditMode
            ? 'Klik Cetak Permohonan kembali apabila ada perubahan, lalu klik ' . ucwords(strtolower($reservationSubmitLabel)) . ' setelah upload file Permohonan yang baru'
            : 'Setelah melengkapi formulir reservasi, klik Cetak Permohonan, unggah, lalu klik Reservasi');
    $hasApplicantIdentity = $existingIdentityUpload['url'] !== '';
    $estPersonFeedback = $selectedBuildingCapacity > 0
        ? 'Estimasi orang tidak boleh 0, maksimum ' . number_format($selectedBuildingCapacity, 0, ',', '.') . ' orang'
        : 'Estimasi orang tidak boleh 0 dan wajib menyesuaikan kapasitas gedung';
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

<div id="user-reservation-panel-root">
    <div class="row g-4">
        <div class="col-xl-4" id="user-reservation-summary-column">
            <div class="card border-0 bg-primary-subtle shadow-none h-100">
                <div class="card-body p-4">
                    <span class="badge bg-primary-subtle text-primary mb-3"><b>PANDUAN RESERVASI</b></span>
                    <h3 class="fw-bold text-success mb-3"><b>Halo! Silakan ikuti langkah-langkah berikut ini untuk melakukan reservasi</b></h3>
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
                        <h5 class="fw-bold mb-0"><b>Alur Reservasi</b></h5>
                    </div>
                    <ol class="ps-3" style="text-align: justify;">
                        <li><b class="text-info">Pilih Lokasi:</b> Tentukan wilayah, kecamatan, dan gedung yang ingin digunakan</li>
                        <li><b class="text-info">Pilih Tanggal:</b> Klik tanggal yang diinginkan pada kalender reservasi. Formulir reservasi akan otomatis muncul</li>
                        <li><b class="text-info">Isi Data:</b> Lengkapi seluruh data pada formulir yang tersedia. Semua kolom wajib diisi</li>
                        <li><b class="text-info">Cetak & Unggah:</b> Klik Cetak Permohonan setelah data lengkap. Cetak dokumen, bubuhkan tanda tangan, lalu unggah kembali file permohonan</li>
                        <li><b class="text-info">Kirim Pengajuan:</b> Klik Reservasi agar dapat melanjutkan permohonan reservasi</li>
                    </ol>
                </div>
            </div>
        </div>

        <div class="col-xl-8" id="user-reservation-form-column">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="calender-sidebar app-calendar sigap-calendar-layout" data-calendar-scope="user">
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
                            <div>
                                <h5 class="fw-semibold mb-1"><b>KALENDER RESERVASI</b></h5>
                            </div>
                        </div>

                        <div class="col-12 px-0">
                            <label class="form-label text-danger mb-2">
                                <b>Pilih Lokasi</b>
                            </label>

                            <div class="row gx-3 gy-2 align-items-stretch">
                                <div class="col-lg-4 col-md-4 col-12">
                                    <select class="form-select w-100" id="filterWilayahUser" data-calendar-role="region" data-placeholder="Pilih Wilayah...">
                                        <option value="">Pilih Wilayah...</option>
                                        @if (!empty($filterData))
                                            @foreach ($filterData as $region)
                                                <option value="{{ $region['region'] }}">
                                                    Surabaya {{ $region['region'] }} ({{ $region['district_count'] }})
                                                </option>
                                            @endforeach
                                        @endif
                                    </select>
                                    <div class="invalid-feedback">Pilih wilayah terlebih dahulu</div>
                                </div>

                                <div class="col-lg-4 col-md-4 col-12">
                                    <select class="form-select w-100" id="filterKecamatanUser" data-calendar-role="district" disabled>
                                        <option value="">Pilih Kecamatan...</option>
                                    </select>
                                    <div class="invalid-feedback">Pilih kecamatan terlebih dahulu</div>
                                </div>

                                <div class="col-lg-4 col-md-4 col-12">
                                    <select class="form-select w-100" id="filterGedungUser" data-calendar-role="building" disabled>
                                        <option value="">Pilih Gedung...</option>
                                    </select>
                                    <div class="invalid-feedback">Pilih gedung terlebih dahulu</div>
                                </div>
                            </div>
                        </div>

                        <div
                            id="calendar-user"
                            class="mt-3 sigap-calendar-instance"
                            data-calendar-instance="user"
                        ></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div
        class="row g-4 mt-1{{ $shouldShowReservationForm ? '' : ' d-none' }}"
        id="user-reservation-detail-row"
    >
        <div class="col-xl-4">
            <div class="card border-0 bg-light shadow-sm h-100">
                <div class="card-body">
                    <h5 class="fw-semibold mb-3"><b>SLOT YANG DIPILIH</b></h5>
                    <div class="border rounded-3 bg-white p-3 mb-3">
                        <div class="small text-muted mb-1">Tanggal Reservasi</div>
                        <div class="fw-semibold" id="reservation-selected-date-text">
                            {{ $selectedDate !== '' ? $selectedDate : 'Belum dipilih' }}
                        </div>
                    </div>

                    <div class="border rounded-3 bg-white p-3">
                        <div class="small text-muted mb-1">Gedung Dipilih</div>
                        <div class="fw-semibold" id="reservation-selected-building-text">
                            {{ $selectedBuildingLabel !== '' ? $selectedBuildingLabel : 'Pilih gedung terlebih dahulu' }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-8">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3 reservation-form-header">
                        <div>
                            <h5 class="fw-semibold mb-1"><b>{{ $reservationFormTitle }}</b></h5>
                            <p class="text-muted mb-0">{{ $reservationFormDescription }}</p>
                        </div>
                        <div class="reservation-form-header-actions">
                            @if ($profileIncomplete)
                                <span class="badge bg-danger-subtle text-danger">Profil belum lengkap</span>
                            @else
                                <a href="{{ base_url('user/reservasi') }}" class="btn btn-danger reservation-form-back-button"><b>KEMBALI</b></a>
                            @endif
                        </div>
                    </div>

                    @if ($isEditMode && $reservationFeedbackNotes !== '')
                        <div class="alert alert-warning mb-4" role="alert">
                            <div class="fw-semibold mb-2">Catatan Revisi Petugas</div>
                            <div style="white-space: pre-line;" class="text-danger">"<b>{{ $reservationFeedbackNotes }}</b>"</div>
                        </div>
                    @endif

                    <form
                        action="{{ $reservationFormAction }}"
                        method="POST"
                        enctype="multipart/form-data"
                        id="userReservationForm"
                        data-min-booking-date="{{ $minBookingDate }}"
                        data-reservation-mode="{{ $isEditMode ? 'edit' : 'create' }}"
                        data-reservation-status-key="{{ $editingReservationStatusKey }}"
                        data-initial-form-visible="{{ $shouldShowReservationForm ? '1' : '0' }}"
                        data-requires-request-file="{{ $requestUploadRequired ? '1' : '0' }}"
                        data-requires-fresh-request-file="{{ $isUmkmFollowUpMode ? '1' : '0' }}"
                        data-requires-id-file="0"
                        novalidate
                    >
                        {!! csrf_field() !!}

                        <input type="hidden" name="reservation_id" value="{{ $isEditMode ? $editingReservationId : '' }}">
                        <input type="hidden" name="building_id" id="reservation-building-id" value="{{ $selectedBuildingId }}">
                        <input type="hidden" name="start_date" id="reservation-start-date" value="{{ $selectedDate }}">
                        <input type="hidden" name="end_date" id="reservation-end-date" value="{{ $selectedDate }}">

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">NIK</label>
                                <input
                                    type="text"
                                    class="form-control{{ $hasApplicantNik ? ' is-valid' : '' }}"
                                    id="reservation-applicant-nik"
                                    value="{{ $userNik !== '' ? $userNik : '-' }}"
                                    readonly
                                >
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Nama</label>
                                <input
                                    type="text"
                                    class="form-control{{ $hasApplicantName ? ' is-valid' : '' }}"
                                    id="reservation-applicant-name"
                                    value="{{ $profileName }}"
                                    readonly
                                >
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Alamat</label>
                                <input
                                    type="text"
                                    class="form-control{{ $hasApplicantAddress ? ' is-valid' : '' }}"
                                    id="reservation-applicant-address"
                                    value="{{ $userAddress !== '' ? $userAddress : '-' }}"
                                    readonly
                                >
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Tlp / HP</label>
                                <input
                                    type="text"
                                    class="form-control{{ $hasApplicantPhone ? ' is-valid' : '' }}"
                                    id="reservation-applicant-phone"
                                    value="{{ $user['phone'] ?? '-' }}"
                                    readonly
                                >
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Gedung Pilihan</label>
                                <input
                                    type="text"
                                    class="form-control"
                                    id="reservation-building-display"
                                    value="{{ $selectedBuildingLabel }}"
                                    placeholder="Pilih gedung dari filter lokasi"
                                    readonly
                                >
                                <div class="invalid-feedback">Gedung akan terisi setelah Anda memilih lokasi</div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Tanggal Pilihan</label>
                                <input
                                    type="text"
                                    class="form-control"
                                    id="reservation-date-display"
                                    value="{{ $selectedDate !== '' ? $selectedDate : '' }}"
                                    placeholder="Klik tanggal pada kalender"
                                    readonly
                                >
                                <div class="invalid-feedback">Pilih tanggal dari kalender dan pastikan minimal H-14</div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" for="reservation-event-id">Jenis Acara <b class="text-danger">*</b></label>
                                <select class="form-select" id="reservation-event-id" name="event_id" @if ($profileIncomplete) disabled @endif>
                                    <option value="">Pilih jenis acara...</option>
                                    @foreach ($eventOptions as $eventOption)
                                        <option
                                            value="{{ $eventOption['id'] }}"
                                            @if ((string) ($oldInput['event_id'] ?? '') === (string) $eventOption['id']) selected @endif
                                        >
                                            {{ $eventOption['event_name'] }}
                                        </option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback">Pilih jenis acara terlebih dahulu</div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" for="reservation-est-person">Estimasi Orang <b class="text-danger">*</b></label>
                                <input
                                    type="number"
                                    class="form-control"
                                    id="reservation-est-person"
                                    name="est_person"
                                    min="1"
                                    step="1"
                                    value="{{ $oldInput['est_person'] ?? '' }}"
                                    placeholder="Masukkan estimasi peserta"
                                    @if ($profileIncomplete) disabled @endif
                                >
                                <div class="invalid-feedback" id="reservation-est-person-feedback">{{ $estPersonFeedback }}</div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" for="reservation-session-id">Sesi Reservasi <b class="text-danger">*</b></label>
                                <select class="form-select" id="reservation-session-id" name="session_option" @if ($profileIncomplete) disabled @endif>
                                    <option value="">Pilih sesi...</option>
                                    @foreach ($sessionOptions as $sessionOption)
                                        <option
                                            value="{{ $sessionOption['id'] }}"
                                            data-start-time="{{ isset($sessionOption['start_time']) && $sessionOption['start_time'] !== null ? substr((string) $sessionOption['start_time'], 0, 5) : '' }}"
                                            data-end-time="{{ isset($sessionOption['end_time']) && $sessionOption['end_time'] !== null ? substr((string) $sessionOption['end_time'], 0, 5) : '' }}"
                                            data-is-custom="{{ !empty($sessionOption['is_custom']) ? '1' : '0' }}"
                                            @if ($selectedSessionOption === (string) $sessionOption['id']) selected @endif
                                        >
                                            {{ $formatSessionLabel($sessionOption) }}
                                        </option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback">Pilih sesi reservasi terlebih dahulu</div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" for="reservation-umkm-id">UMKM</label>
                                <select class="form-select" id="reservation-umkm-id" name="umkm_id" @if ($profileIncomplete) disabled @endif>
                                    <option value="">Pilih UMKM...</option>
                                    @foreach (($umkmOptions ?? []) as $umkmOption)
                                        @php $umkmProductLabel = trim((string) ($umkmOption['product_label'] ?? '')); @endphp
                                        <option
                                            value="{{ $umkmOption['id'] }}"
                                            data-building-ids="{{ implode(',', $umkmOption['building_ids'] ?? []) }}"
                                            @if ($selectedUmkmId === (string) $umkmOption['id']) selected @endif
                                        >
                                            {{ $umkmOption['umkm_name'] }}{{ $umkmProductLabel !== '' ? ' (' . $umkmProductLabel . ')' : '' }}
                                        </option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback">Pilih UMKM terlebih dahulu</div>
                            </div>

                            <div class="col-md-6 {{ $isCustomSessionSelected ? '' : 'd-none' }}" id="reservation-custom-time-group">
                                <label class="form-label d-block">Jam Mulai Jam Selesai</label>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <input
                                            type="time"
                                            class="form-control"
                                            id="reservation-start-time"
                                            name="start_time"
                                            value="{{ $selectedStartTime !== '' ? substr($selectedStartTime, 0, 5) : '' }}"
                                            @if ($profileIncomplete) disabled @endif
                                        >
                                        <div class="invalid-feedback">Isi jam mulai reservasi dengan benar</div>
                                    </div>

                                    <div class="col-md-6">
                                        <input
                                            type="time"
                                            class="form-control"
                                            id="reservation-end-time"
                                            name="end_time"
                                            value="{{ $selectedEndTime !== '' ? substr($selectedEndTime, 0, 5) : '' }}"
                                            @if ($profileIncomplete) disabled @endif
                                        >
                                        <div class="invalid-feedback">Isi jam selesai yang lebih besar dari jam mulai</div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="row g-3">
                                    {{-- <div class="col-md-6">
                                        <div class="reservation-upload-field">
                                            <div class="row g-3 align-items-start">
                                                <div class="col-sm-8">
                                                    <label class="form-label">Identitas KTP</label>
                                                    <div class="form-text">
                                                        {{ $hasApplicantIdentity ? 'File KTP otomatis diambil dari profil Anda' : 'File KTP belum tersedia pada profil Anda' }}
                                                    </div>
                                                </div>

                                                <div class="col-sm-4 d-flex justify-content-sm-center">
                                                    <div
                                                        class="reservation-upload-preview {{ $hasApplicantIdentity ? 'is-ready' : '' }}"
                                                        id="reservation-id-file-preview"
                                                        data-gallery-title="Identitas KTP"
                                                        data-file-type="{{ $existingIdentityUpload['extension'] }}"
                                                        data-empty-status="Belum ada file"
                                                        data-empty-name="File KTP belum tersedia"
                                                    >
                                                        <span class="reservation-upload-preview-check" @if (!$hasApplicantIdentity) hidden @endif>
                                                            <i class="ti ti-check"></i>
                                                        </span>
                                                        <a
                                                            class="reservation-upload-preview-media reservation-upload-preview-trigger{{ $hasApplicantIdentity && $existingIdentityUpload['is_image'] ? ' image-popup-vertical-fit' : '' }}"
                                                            data-upload-preview-media
                                                            @if ($hasApplicantIdentity)
                                                                data-gallery-trigger="reservation-file"
                                                                data-gallery-title="Identitas KTP"
                                                                data-file-type="{{ $existingIdentityUpload['extension'] }}"
                                                                href="{{ $existingIdentityUpload['url'] }}"
                                                            @endif
                                                            @if (!$hasApplicantIdentity) aria-disabled="true" tabindex="-1" @endif
                                                        >
                                                            <img
                                                                class="reservation-upload-preview-image"
                                                                data-upload-preview-image
                                                                @if ($hasApplicantIdentity && $existingIdentityUpload['is_image'])
                                                                    src="{{ $existingIdentityUpload['url'] }}"
                                                                @else
                                                                    src=""
                                                                @endif
                                                                alt="Preview file KTP"
                                                                @if (!$hasApplicantIdentity || !$existingIdentityUpload['is_image']) hidden @endif
                                                            >
                                                            <span class="reservation-upload-preview-icon" data-upload-preview-icon @if ($hasApplicantIdentity && $existingIdentityUpload['is_image']) hidden @endif>{{ $existingIdentityUpload['type_label'] }}</span>
                                                        </a>
                                                        <div class="reservation-upload-preview-status" data-upload-preview-status>
                                                            {{ $hasApplicantIdentity ? 'File tersedia' : 'Belum ada file' }}
                                                        </div>
                                                        <div class="reservation-upload-preview-name" data-upload-preview-name hidden>
                                                            {{ $existingIdentityUpload['name'] !== '' ? $existingIdentityUpload['name'] : 'File KTP belum tersedia' }}
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>     --}}

                                    <div class="col-md-6">
                                        <div class="reservation-upload-field">
                                            <div class="row g-3 align-items-start">
                                                <div class="col-sm-8">
                                                    <label class="form-label" for="reservation-request-file">
                                                        {{ $requestUploadLabel }}
                                                        @if ($requestUploadRequired)
                                                            <b class="text-danger">*</b>
                                                        @endif
                                                    </label>
                                                    <input
                                                        type="file"
                                                        class="form-control"
                                                        id="reservation-request-file"
                                                        name="request_file"
                                                        accept=".jpg,.jpeg,.png,.pdf"
                                                        data-preview-target="reservation-request-file-preview"
                                                        data-existing-file-url="{{ $existingRequestUpload['url'] }}"
                                                        data-existing-file-name="{{ $existingRequestUpload['name'] }}"
                                                        data-existing-file-extension="{{ $existingRequestUpload['extension'] }}"
                                                        @if ($profileIncomplete) disabled @endif
                                                    >
                                                    <div class="invalid-feedback">{{ $requestUploadFeedback }}</div>
                                                    <div class="form-text">{{ $requestUploadHelper }}</div>
                                                </div>

                                                <div class="col-sm-4 d-flex justify-content-sm-center">
                                                    <div
                                                        class="reservation-upload-preview {{ $existingRequestUpload['url'] !== '' ? 'is-ready' : '' }}"
                                                        id="reservation-request-file-preview"
                                                        data-gallery-title="{{ $requestGalleryTitle }}"
                                                        data-file-type="{{ $existingRequestUpload['extension'] }}"
                                                        data-empty-status="Belum ada file"
                                                        data-empty-name="Unggah JPG, JPEG, PNG, atau PDF"
                                                    >
                                                        <span class="reservation-upload-preview-check" @if ($existingRequestUpload['url'] === '') hidden @endif>
                                                            <i class="ti ti-check"></i>
                                                        </span>
                                                        <a
                                                            class="reservation-upload-preview-media reservation-upload-preview-trigger{{ $existingRequestUpload['is_image'] ? ' image-popup-vertical-fit' : '' }}"
                                                            data-upload-preview-media
                                                            @if ($existingRequestUpload['url'] !== '')
                                                                data-gallery-trigger="reservation-file"
                                                                data-gallery-title="{{ $requestGalleryTitle }}"
                                                                data-file-type="{{ $existingRequestUpload['extension'] }}"
                                                                href="{{ $existingRequestUpload['url'] }}"
                                                            @endif
                                                            @if ($existingRequestUpload['url'] === '') aria-disabled="true" tabindex="-1" @endif
                                                        >
                                                            <img
                                                                class="reservation-upload-preview-image"
                                                                data-upload-preview-image
                                                                src=""
                                                                alt="Preview file permohonan"
                                                                hidden
                                                            >
                                                            <span class="reservation-upload-preview-icon" data-upload-preview-icon>{{ $existingRequestUpload['type_label'] }}</span>
                                                        </a>
                                                        <div class="reservation-upload-preview-status" data-upload-preview-status>
                                                            {{ $existingRequestUpload['url'] !== '' ? 'File sudah diunggah' : 'Belum ada file' }}
                                                        </div>
                                                        <div class="reservation-upload-preview-name" data-upload-preview-name hidden>
                                                            {{ $existingRequestUpload['name'] !== '' ? $existingRequestUpload['name'] : 'Unggah JPG, JPEG, PNG, atau PDF' }}
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="border rounded-3 bg-light p-3">
                                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                                        <div>
                                            <div class="fw-semibold">Pastikan kembali data isian Anda</div>
                                            <div class="text-muted small">{{ $reservationActionHelperText }}</div>
                                        </div>
                                        <div class="d-flex flex-wrap gap-2">
                                            @if (!$isUmkmFollowUpMode)
                                                <button type="button" class="btn btn-warning" id="reservation-print-button" data-reservation-print-toggle="application" data-print-state="ready" @if ($profileIncomplete) disabled @endif>
                                                    <b>CETAK PERMOHONAN</b>
                                                </button>
                                            @endif
                                            <button type="submit" class="btn btn-success" @if ($profileIncomplete) disabled @endif>
                                                <b>{{ $reservationSubmitLabel }}</b>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
