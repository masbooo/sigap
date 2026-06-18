@extends('layouts.user')

@section('content')

@php
    $dashboardCards = $dashboardCards ?? [];
@endphp

<div class="container-fluid">
    <div class="card bg-info-subtle shadow-none position-relative overflow-hidden mb-4">
        <div class="card-body px-4 py-3">
            <div class="row align-items-center">
                <div class="col-9">
                    <h4 class="fw-semibold mb-8">Data Infografis</h4>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item">
                                <a class="text-muted text-decoration-none" href="{{ base_url('user/dasbor') }}">Dasbor</a>
                            </li>
                            <li class="breadcrumb-item active" aria-current="page">
                                Infografis
                            </li>
                        </ol>
                    </nav>
                </div>
                <div class="col-3">
                    <div class="text-center mb-n5">
                        <img src="{{ base_url('assets/custom/images/breadcrumb/ChatBc.png') }}" class="img-fluid mb-n4" alt="Breadcrumb">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="owl-carousel counter-carousel owl-theme">
        @foreach ($dashboardCards as $card)
            @php
                $tone = trim((string) ($card['tone'] ?? 'primary'));
                $surfaceTone = 'bg-' . $tone . '-subtle';
                $textTone = $tone === 'dark' ? 'dark' : $tone;
                $iconClass = trim((string) ($card['icon'] ?? 'ti ti-chart-bar'));
                $cardLabel = trim((string) ($card['label'] ?? 'Data'));
                $cardValue = (int) ($card['value'] ?? 0);
            @endphp
            <div class="item">
                <div class="card border-0 zoom-in {{ $surfaceTone }} shadow-none">
                    <div class="card-body">
                        <div class="text-center">
                            <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-white mb-3 shadow-sm" style="width:56px;height:56px;">
                                <i class="{{ $iconClass }} fs-7 text-{{ $textTone }}"></i>
                            </span>
                            <p class="fw-semibold fs-3 text-{{ $textTone }} mb-1">{{ $cardLabel }}</p>
                            <h5 class="fw-semibold text-{{ $textTone }} mb-0">{{ number_format($cardValue, 0, ',', '.') }}</h5>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>

@if (!empty($error))
<script>
    window.__loginErrorMessage = "{{ addslashes($error) }}";
</script>
@endif

@if (!empty($success))
<script>
    window.__loginSuccessMessage = "{{ addslashes($success) }}";
</script>
@endif

@if (!empty($forceProfileModal))
@php
    $normalizeUploadPath = static function ($relativePath): string {
        $normalizedPath = trim(str_replace('\\', '/', (string) $relativePath));
        $normalizedPath = $normalizedPath !== '' ? ltrim($normalizedPath, '/') : '';

        if (str_starts_with($normalizedPath, 'user/identity/')) {
            return 'user/identitas/' . substr($normalizedPath, strlen('user/identity/'));
        }

        return $normalizedPath;
    };

    $resolveUploadedFilePreview = static function ($relativePath) use ($normalizeUploadPath): array {
        $normalizedPath = $normalizeUploadPath($relativePath);
        $fileName = $normalizedPath !== '' ? basename($normalizedPath) : '';
        $extension = strtolower((string) pathinfo($fileName, PATHINFO_EXTENSION));
        $typeLabel = $extension !== '' ? strtoupper($extension) : 'FILE';

        return [
            'url' => $normalizedPath !== '' ? base_url('assets/uploads/' . $normalizedPath) : '',
            'name' => $fileName,
            'extension' => $extension,
            'type_label' => $typeLabel,
        ];
    };

    $existingIdentityUpload = $resolveUploadedFilePreview($user['id_path'] ?? '');
    $identityUploadRequired = $existingIdentityUpload['url'] === '';
@endphp
<div class="modal fade" id="requiredBiodataModal" tabindex="-1" aria-labelledby="requiredBiodataModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <div class="modal-header d-flex align-items-center">
                <h3 class="modal-title fw-bold mb-0" id="requiredBiodataModalLabel"><b>Lengkapi Profil Anda</b></h3>
            </div>

            <div class="modal-body">
                <p class="text-info mb-3">
                    Silakan lengkapi profil Anda terlebih dahulu agar semua fitur dalam aplikasi SIGAP dapat segera Anda gunakan
                </p>

                <form action="{{ base_url('user/dasbor') }}" method="POST" enctype="multipart/form-data" id="requiredBiodataForm" novalidate>
                    {!! csrf_field() !!}
                    <input type="hidden" name="save_biodata" value="1">

                    <div class="mb-3">
                        <label class="form-label" for="requiredNIK">NIK <b class="text-danger">*</b></label>
                        <input
                            type="text"
                            id="requiredNIK"
                            name="nik"
                            class="form-control"
                            maxlength="16"
                            inputmode="numeric"
                            autocomplete="off"
                            placeholder="Masukkan 16 digit NIK"
                            value="{{ htmlspecialchars($user['nik'] ?? '', ENT_QUOTES, 'UTF-8') }}"
                        >
                        <div class="invalid-feedback">NIK wajib diisi dan harus 16 digit angka</div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-8">
                            <label class="form-label" for="requiredName">Nama Lengkap <b class="text-danger">*</b></label>
                            <input
                                type="text"
                                id="requiredName"
                                name="name"
                                class="form-control"
                                autocomplete="off"
                                placeholder="Masukkan Nama Lengkap"
                                value="{{ htmlspecialchars($user['name'] ?? '', ENT_QUOTES, 'UTF-8') }}"
                            >
                            <div class="invalid-feedback">Nama lengkap wajib diisi</div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label d-block">Jenis Kelamin <b class="text-danger">*</b></label>
                            <div
                                id="requiredGenderGroup"
                                class="d-flex align-items-center gap-4 flex-wrap pt-2"
                            >
                                <div class="form-check mb-0">
                                    <input
                                        class="form-check-input"
                                        type="radio"
                                        name="gender"
                                        id="requiredGenderMale"
                                        value="L"
                                        @if ((string) ($user['gender'] ?? '') === 'L') checked @endif
                                    >
                                    <label class="form-check-label" for="requiredGenderMale">L</label>
                                </div>

                                <div class="form-check mb-0">
                                    <input
                                        class="form-check-input"
                                        type="radio"
                                        name="gender"
                                        id="requiredGenderFemale"
                                        value="P"
                                        @if ((string) ($user['gender'] ?? '') === 'P') checked @endif
                                    >
                                    <label class="form-check-label" for="requiredGenderFemale">P</label>
                                </div>
                            </div>
                            <div id="requiredGenderInvalidFeedback" class="invalid-feedback d-none">Jenis kelamin wajib dipilih</div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="requiredAddress">Alamat <b class="text-danger">*</b></label>
                        <textarea
                            id="requiredAddress"
                            name="address"
                            class="form-control"
                            rows="3"
                            placeholder="Masukkan Alamat"
                        >{{ htmlspecialchars($user['address'] ?? '', ENT_QUOTES, 'UTF-8') }}</textarea>
                        <div class="invalid-feedback">Alamat wajib diisi</div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-6">
                            <label class="form-label" for="requiredDistrict">Kecamatan <b class="text-danger">*</b></label>
                            <select
                                id="requiredDistrict"
                                name="district_id"
                                class="form-select"
                                data-selected="{{ htmlspecialchars((string) ($user['district_id'] ?? ''), ENT_QUOTES, 'UTF-8') }}"
                            >
                                <option value="">Pilih Kecamatan</option>
                                <?php foreach (($districts ?? []) as $districtItem): ?>
                                    <option
                                        value="<?= (int) $districtItem['id']; ?>"
                                        <?= ((string) ($user['district_id'] ?? '') === (string) $districtItem['id']) ? 'selected' : ''; ?>
                                    >
                                        <?= htmlspecialchars($districtItem['district'], ENT_QUOTES, 'UTF-8'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback">Kecamatan wajib dipilih</div>
                        </div>

                        <div class="col-6">
                            <label class="form-label" for="requiredVillage">Kelurahan <b class="text-danger">*</b></label>
                            <select
                                id="requiredVillage"
                                name="subdistrict_id"
                                class="form-select"
                                data-selected="{{ htmlspecialchars((string) ($user['subdistrict_id'] ?? ''), ENT_QUOTES, 'UTF-8') }}"
                                disabled
                            >
                                <option value="">Pilih Kelurahan</option>
                            </select>
                            <div class="invalid-feedback">Kelurahan wajib dipilih</div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="requiredPhone">Telp / HP <b class="text-danger">*</b></label>
                        <input
                            type="text"
                            id="requiredPhone"
                            name="phone"
                            class="form-control"
                            inputmode="numeric"
                            autocomplete="off"
                            placeholder="Masukkan Telp / HP"
                            value="{{ htmlspecialchars($user['phone'] ?? '', ENT_QUOTES, 'UTF-8') }}"
                        >
                        <div class="invalid-feedback">Telp / HP wajib diisi dan hanya diisi angka</div>
                    </div>

                    <div class="mb-3">
                        <div class="reservation-upload-field">
                            <div class="row g-3 align-items-start">
                                <div class="col-sm-8">
                                    <label class="form-label" for="requiredIdentityFile">
                                        Upload KTP
                                        @if ($identityUploadRequired)
                                            <b class="text-danger">*</b>
                                        @endif
                                    </label>
                                    <input
                                        type="file"
                                        class="form-control"
                                        id="requiredIdentityFile"
                                        name="id_file"
                                        accept=".jpg,.jpeg,.png,.pdf"
                                        data-preview-target="required-identity-file-preview"
                                        data-existing-file-url="{{ $existingIdentityUpload['url'] }}"
                                        data-existing-file-name="{{ $existingIdentityUpload['name'] }}"
                                        data-existing-file-extension="{{ $existingIdentityUpload['extension'] }}"
                                        data-required-upload="{{ $identityUploadRequired ? '1' : '0' }}"
                                    >
                                    <div class="invalid-feedback">Upload KTP wajib diisi dengan file JPG, JPEG, PNG, atau PDF maksimal 1MB</div>
                                    <div class="form-text">Unggah KTP dengan format JPG, JPEG, PNG, atau PDF maksimal 1MB.</div>
                                </div>

                                <div class="col-sm-4 d-flex justify-content-sm-center">
                                    <div
                                        class="reservation-upload-preview {{ $existingIdentityUpload['url'] !== '' ? 'is-ready' : '' }}"
                                        id="required-identity-file-preview"
                                        data-gallery-title="File KTP"
                                        data-file-type="{{ $existingIdentityUpload['extension'] }}"
                                        data-empty-status="Belum ada file"
                                        data-empty-name="Unggah JPG, JPEG, PNG, atau PDF"
                                    >
                                        <span class="reservation-upload-preview-check" @if ($existingIdentityUpload['url'] === '') hidden @endif>
                                            <i class="ti ti-check"></i>
                                        </span>
                                        <a
                                            class="reservation-upload-preview-media reservation-upload-preview-trigger"
                                            data-upload-preview-media
                                            @if ($existingIdentityUpload['url'] !== '')
                                                data-gallery-trigger="required-identity-file"
                                                data-gallery-title="File KTP"
                                                href="{{ $existingIdentityUpload['url'] }}"
                                                target="_blank"
                                                rel="noopener noreferrer"
                                            @endif
                                            @if ($existingIdentityUpload['url'] === '') aria-disabled="true" tabindex="-1" @endif
                                        >
                                            <img
                                                class="reservation-upload-preview-image"
                                                data-upload-preview-image
                                                src=""
                                                alt="Preview file KTP"
                                                hidden
                                            >
                                            <span class="reservation-upload-preview-icon" data-upload-preview-icon>{{ $existingIdentityUpload['type_label'] }}</span>
                                        </a>
                                        <div class="reservation-upload-preview-status" data-upload-preview-status>
                                            {{ $existingIdentityUpload['url'] !== '' ? 'File sudah diunggah' : 'Belum ada file' }}
                                        </div>
                                        <div class="reservation-upload-preview-name" data-upload-preview-name hidden>
                                            {{ $existingIdentityUpload['name'] !== '' ? $existingIdentityUpload['name'] : 'Unggah JPG, JPEG, PNG, atau PDF' }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <div class="modal-footer">
                <a href="{{ base_url('logout') }}" class="btn bg-danger-subtle text-danger waves-effect text-start">KELUAR</a>
                <button type="submit" form="requiredBiodataForm" class="btn bg-success-subtle text-success waves-effect text-start">SIMPAN PROFIL</button>
            </div>
        </div>
    </div>
</div>

<script>
    window.__districtVillageMap = <?= json_encode($districtVillageMap ?? [], JSON_UNESCAPED_UNICODE); ?>;
</script>

@endif

@endsection
