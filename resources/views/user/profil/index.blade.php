@extends('layouts.user')

@section('content')
@php
    $profileUser = $user ?? user();
    $profileName = resolve_user_display_name($profileUser);
    $profilePhoto = resolve_user_profile_photo_url($profileUser);
    $defaultProfilePhoto = resolve_user_default_profile_photo_url($profileUser);
    $messages = $messages ?? ['success' => '', 'error' => ''];
    $profileSubtitle = trim((string) ($profileUser['username'] ?? '')) !== '' ? '@' . $profileUser['username'] : 'User';
    $genderCode = strtoupper(trim((string) ($profileUser['gender'] ?? '')));
    $genderLabel = $genderCode === 'L' ? 'Laki-laki' : ($genderCode === 'P' ? 'Perempuan' : '-');
    $statusCode = strtoupper(trim((string) ($profileUser['status'] ?? '')));
    $statusLabel = $statusCode !== '' ? ucfirst(strtolower($statusCode)) : '-';
    $statusBadgeClass = $statusCode === 'AKTIF'
        ? 'bg-success-subtle text-success'
        : ($statusCode === 'PROSES' ? 'bg-warning-subtle text-warning' : 'bg-secondary-subtle text-secondary');
    $normalizedProfilePhotoPath = ltrim(trim(str_replace('\\', '/', (string) ($profileUser['pic_path'] ?? ''))), '/');
    $defaultProfilePhotoPaths = [
        'images/profile/male.svg',
        'images/profile/female.svg',
        'images/profile/default.svg',
    ];
    $hasCustomProfilePhoto = $normalizedProfilePhotoPath !== ''
        && !in_array($normalizedProfilePhotoPath, $defaultProfilePhotoPaths, true);
@endphp

<div class="container-fluid">
    <div class="card bg-info-subtle shadow-none position-relative overflow-hidden mb-4">
        <div class="card-body px-4 py-3">
            <div class="row align-items-center">
                <div class="col-9">
                    <h4 class="fw-semibold mb-8">Profil Saya</h4>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item">
                                <span class="text-muted">Pengaturan</span>
                            </li>
                            <li class="breadcrumb-item active" aria-current="page">
                                Profil
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
        <div class="d-none" data-profile-flash="success" data-message="{!! htmlspecialchars((string) $messages['success'], ENT_QUOTES, 'UTF-8') !!}"></div>
    @endif

    @if (($messages['error'] ?? '') !== '')
        <div class="d-none" data-profile-flash="error" data-message="{!! htmlspecialchars((string) $messages['error'], ENT_QUOTES, 'UTF-8') !!}"></div>
    @endif

    <div class="row">
        <div class="col-lg-6 d-flex align-items-stretch">
            <div class="card w-100 border position-relative overflow-hidden">
                <div class="card-body p-4">
                    <h4 class="card-title">Rubah Foto Profil</h4>
                    <p class="card-subtitle mb-4">Silakan merubah foto profil Anda disini</p>

                    <form
                        action="{{ url('user/profil/foto') }}"
                        method="POST"
                        enctype="multipart/form-data"
                        id="profilePhotoUploadForm"
                        class="mb-3"
                    >
                        {!! csrf_field() !!}
                        <input
                            type="file"
                            name="profile_photo"
                            id="profilePhotoInput"
                            class="d-none"
                            accept=".jpg,.jpeg,.png,image/jpeg,image/png"
                        >
                    </form>

                    <form
                        action="{{ url('user/profil/foto/reset') }}"
                        method="POST"
                        id="profilePhotoResetForm"
                        class="d-none"
                    >
                        {!! csrf_field() !!}
                    </form>

                    <div class="profile-photo-avatar">
                        <img
                            src="{{ $profilePhoto }}"
                            alt="{{ $profileName }}"
                            data-fallback-src="{{ $defaultProfilePhoto }}"
                        >
                    </div>

                    <div class="d-flex align-items-center justify-content-center gap-3 flex-wrap mb-3">
                        <button type="button" class="btn btn-primary px-4" id="profilePhotoUploadButton">UPLOAD</button>
                        <button
                            type="submit"
                            form="profilePhotoResetForm"
                            class="btn bg-danger-subtle text-danger px-4"
                            id="profilePhotoResetButton"
                            @if (!$hasCustomProfilePhoto) disabled @endif
                        >
                            RESET
                        </button>
                    </div>

                    <p class="text-muted text-center mb-4">Format yang didukung: JPG, JPEG, PNG dan ukuran maks. 1MB</p>

                </div>
            </div>
        </div>

        <div class="col-lg-6 d-flex align-items-stretch">
            <div class="card w-100 border position-relative overflow-hidden">
                <div class="card-body p-4">
                    <h4 class="card-title">Rubah Password</h4>
                    <p class="card-subtitle mb-4">Silakan merubah password Anda disini</p>
                    <form action="{{ url('user/profil/password') }}" method="POST" autocomplete="off" id="profilePasswordForm" novalidate>
                        {!! csrf_field() !!}

                        <div class="mb-3 password-field">
                            <label for="profileCurrentPassword" class="form-label">Password Sekarang</label>
                            <div class="password-group">
                                <input
                                    type="password"
                                    class="form-control password-input"
                                    id="profileCurrentPassword"
                                    name="current_password"
                                    placeholder="Masukkan Password Sekarang"
                                    autocomplete="current-password"
                                >
                                <span
                                    class="password-toggle-icon"
                                    data-target="profileCurrentPassword"
                                    role="button"
                                    tabindex="0"
                                    aria-label="Tampilkan password"
                                >
                                    <i class="ti ti-eye"></i>
                                </span>
                            </div>
                            <div class="invalid-feedback">Password sekarang wajib diisi</div>
                            <div class="valid-feedback">Password sekarang sudah diisi</div>
                        </div>

                        <div class="mb-3 password-field">
                            <label for="profileNewPassword" class="form-label">Password Baru</label>
                            <div class="password-group">
                                <input
                                    type="password"
                                    class="form-control password-input"
                                    id="profileNewPassword"
                                    name="password"
                                    placeholder="Masukkan Password Baru"
                                    autocomplete="new-password"
                                >
                                <span
                                    class="password-toggle-icon"
                                    data-target="profileNewPassword"
                                    role="button"
                                    tabindex="0"
                                    aria-label="Tampilkan password"
                                >
                                    <i class="ti ti-eye"></i>
                                </span>
                            </div>
                            <div class="invalid-feedback">Password minimal 8 karakter dan harus terdiri dari huruf dan angka</div>
                            <div class="valid-feedback">Password sesuai</div>
                        </div>

                        <div class="mb-4 password-field">
                            <label for="profilePasswordConfirmation" class="form-label">Ulangi Password</label>
                            <div class="password-group">
                                <input
                                    type="password"
                                    class="form-control password-input"
                                    id="profilePasswordConfirmation"
                                    name="password_confirmation"
                                    placeholder="Ulangi Password Baru"
                                    autocomplete="new-password"
                                >
                                <span
                                    class="password-toggle-icon"
                                    data-target="profilePasswordConfirmation"
                                    role="button"
                                    tabindex="0"
                                    aria-label="Tampilkan password"
                                >
                                    <i class="ti ti-eye"></i>
                                </span>
                            </div>
                            <div class="invalid-feedback">Ulangi Password tidak sesuai</div>
                            <div class="valid-feedback">Ulangi Password sesuai</div>
                        </div>

                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary px-4">SIMPAN</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card w-100 border position-relative overflow-hidden mb-0">
                <div class="card-body p-4">
                    <h4 class="card-title">Detail Domisili</h4>
                    <p class="card-subtitle mb-4">Informasi wilayah dan alamat yang tercatat pada akun Anda</p>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="rounded-3 border p-3 h-100">
                                <div class="text-muted fs-3 mb-1">Kecamatan</div>
                                <div class="fw-semibold">{{ $districtName ?? '-' }}</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="rounded-3 border p-3 h-100">
                                <div class="text-muted fs-3 mb-1">Kelurahan</div>
                                <div class="fw-semibold">{{ $villageName ?? '-' }}</div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="rounded-3 border p-3">
                                <div class="text-muted fs-3 mb-1">Alamat</div>
                                <div class="fw-semibold">{{ trim((string) ($profileUser['address'] ?? '')) !== '' ? $profileUser['address'] : '-' }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade profile-photo-crop-modal" id="profilePhotoCropModal" tabindex="-1" aria-labelledby="profilePhotoCropModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header border-0">
                <div>
                    <h5 class="modal-title fw-bold mb-1" id="profilePhotoCropModalLabel"><b>ATUR FOTO PROFIL</b></h5>
                    <div class="small text-muted">Geser dan zoom gambar untuk menentukan bagian yang tampil di avatar.</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body p-4">
                <div class="row g-4 align-items-center">
                    <div class="col-lg-7">
                        <div class="profile-photo-crop-stage" id="profilePhotoCropStage">
                            <img id="profilePhotoCropImage" alt="Preview crop foto profil" draggable="false">
                            <div class="profile-photo-crop-guides" aria-hidden="true">
                                <span class="profile-photo-crop-corner profile-photo-crop-corner--top-left"></span>
                                <span class="profile-photo-crop-corner profile-photo-crop-corner--top-right"></span>
                                <span class="profile-photo-crop-corner profile-photo-crop-corner--bottom-left"></span>
                                <span class="profile-photo-crop-corner profile-photo-crop-corner--bottom-right"></span>
                            </div>
                        </div>

                        <div class="mt-3">
                            <label class="form-label fw-semibold" for="profilePhotoCropZoom">Zoom</label>
                            <input
                                type="range"
                                class="form-range"
                                id="profilePhotoCropZoom"
                                min="100"
                                max="300"
                                step="1"
                                value="100"
                                disabled
                            >
                            <div class="small text-muted">Tarik slider atau gunakan roda mouse untuk memperbesar tampilan.</div>
                        </div>
                    </div>

                    <div class="col-lg-5 d-flex justify-content-center">
                        <div class="profile-photo-crop-sidebar">
                            <div class="profile-photo-crop-preview">
                                <canvas id="profilePhotoCropPreview" width="240" height="240"></canvas>
                            </div>
                            <h6 class="fw-semibold mb-2">Preview Lingkaran</h6>
                            <p class="text-muted mb-0 profile-photo-crop-helper">Bagian ini yang akan tampil pada foto profil Anda setelah diunggah.</p>
                            <div class="profile-photo-crop-actions">
                                <button type="button" class="btn bg-danger-subtle text-danger" data-bs-dismiss="modal">BATAL</button>
                                <button type="button" class="btn btn-primary" id="profilePhotoCropApplyButton">GUNAKAN FOTO INI</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
