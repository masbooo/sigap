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
                        <img src="{{ base_url('assets/custom/images/breadcrumb/ChatBc.png') }}" class="img-fluid mb-n4" alt="Breadcrumb">
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if (($messages['success'] ?? '') !== '')
        <div id="flash-message-success" data-message="{{ $messages['success'] }}" class="d-none"></div>
        <script>
            (function checkSwalSuccess() {
                if (typeof Swal !== 'undefined') {
                    var msgEl = document.getElementById('flash-message-success');
                    if (msgEl) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil',
                            text: msgEl.getAttribute('data-message'),
                            confirmButtonText: 'OK',
                            timer: 3000,
                            timerProgressBar: true
                        });
                    }
                } else {
                    setTimeout(checkSwalSuccess, 50);
                }
            })();
        </script>
    @endif

    @if (($messages['error'] ?? '') !== '')
        <div id="flash-message-error" data-message="{{ $messages['error'] }}" class="d-none"></div>
        <script>
            (function checkSwalError() {
                if (typeof Swal !== 'undefined') {
                    var msgEl = document.getElementById('flash-message-error');
                    if (msgEl) {
                        Swal.fire({
                timer: 3000,
                timerProgressBar: true,
                            icon: 'error',
                            title: 'Gagal',
                            text: msgEl.getAttribute('data-message'),
                            confirmButtonText: 'OK'
                        });
                    }
                } else {
                    setTimeout(checkSwalError, 50);
                }
            })();
        </script>
    @endif

    <div class="row">
        <div class="col-lg-6 d-flex align-items-stretch">
            <div class="card w-100 border position-relative overflow-hidden">
                <div class="card-body p-4">
                    <h4 class="card-title">Rubah Foto Profil</h4>
                    <p class="card-subtitle mb-4">Silakan merubah foto profil Anda disini</p>

                    <form
                        action="{{ base_url('user/profil/foto') }}"
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
                        action="{{ base_url('user/profil/foto/reset') }}"
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
                            onerror="this.onerror=null;this.src='{{ $defaultProfilePhoto }}';"
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
                    <form action="{{ base_url('user/profil/password') }}" method="POST" autocomplete="off" id="profilePasswordForm" novalidate>
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

<script>
(function () {
    var fileInput = document.getElementById('profilePhotoInput');
    var uploadButton = document.getElementById('profilePhotoUploadButton');
    var uploadForm = document.getElementById('profilePhotoUploadForm');
    var passwordForm = document.getElementById('profilePasswordForm');
    var currentPasswordInput = document.getElementById('profileCurrentPassword');
    var newPasswordInput = document.getElementById('profileNewPassword');
    var confirmationInput = document.getElementById('profilePasswordConfirmation');
    var cropModalEl = document.getElementById('profilePhotoCropModal');
    var cropStage = document.getElementById('profilePhotoCropStage');
    var cropImage = document.getElementById('profilePhotoCropImage');
    var cropZoomRange = document.getElementById('profilePhotoCropZoom');
    var cropPreviewCanvas = document.getElementById('profilePhotoCropPreview');
    var cropApplyButton = document.getElementById('profilePhotoCropApplyButton');
    var cropModal = null;

    function getCropModal() {
        if (!cropModal && cropModalEl && typeof bootstrap !== 'undefined') {
            cropModal = new bootstrap.Modal(cropModalEl);
        }
        return cropModal;
    }
    var cropState = {
        image: null,
        objectUrl: '',
        stageSize: 0,
        minScale: 1,
        scale: 1,
        offsetX: 0,
        offsetY: 0,
        pointerActive: false,
        pointerId: null,
        lastPointerX: 0,
        lastPointerY: 0
    };

    function showProfileAlert(icon, title, text) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: icon,
                title: title,
                text: text,
                confirmButtonText: 'OK',
                timer: 3000,
                timerProgressBar: true
            });
            return;
        }

        alert(text || title);
    }

    function revokeCropObjectUrl() {
        if (cropState.objectUrl) {
            URL.revokeObjectURL(cropState.objectUrl);
            cropState.objectUrl = '';
        }
    }

    function clearCropPreviewCanvas() {
        if (!cropPreviewCanvas) {
            return;
        }

        var context = cropPreviewCanvas.getContext('2d');
        if (!context) {
            return;
        }

        context.clearRect(0, 0, cropPreviewCanvas.width, cropPreviewCanvas.height);
    }

    function resetCropper(clearSelectedFile) {
        revokeCropObjectUrl();

        cropState.image = null;
        cropState.stageSize = 0;
        cropState.minScale = 1;
        cropState.scale = 1;
        cropState.offsetX = 0;
        cropState.offsetY = 0;
        cropState.pointerActive = false;
        cropState.pointerId = null;
        cropState.lastPointerX = 0;
        cropState.lastPointerY = 0;

        if (cropStage) {
            cropStage.classList.remove('is-dragging');
        }

        if (cropImage) {
            cropImage.removeAttribute('src');
            cropImage.style.width = '';
            cropImage.style.height = '';
            cropImage.style.transform = '';
        }

        if (cropZoomRange) {
            cropZoomRange.value = '100';
            cropZoomRange.disabled = true;
        }

        if (cropApplyButton) {
            cropApplyButton.disabled = false;
            cropApplyButton.textContent = 'GUNAKAN FOTO INI';
        }

        clearCropPreviewCanvas();

        if (clearSelectedFile && fileInput) {
            fileInput.value = '';
        }
    }

    function clampCropOffsets() {
        if (!cropState.image || !cropState.stageSize) {
            return;
        }

        var renderedWidth = cropState.image.naturalWidth * cropState.scale;
        var renderedHeight = cropState.image.naturalHeight * cropState.scale;
        var minOffsetX = cropState.stageSize - renderedWidth;
        var minOffsetY = cropState.stageSize - renderedHeight;

        if (renderedWidth <= cropState.stageSize) {
            cropState.offsetX = (cropState.stageSize - renderedWidth) / 2;
        } else {
            cropState.offsetX = Math.min(0, Math.max(minOffsetX, cropState.offsetX));
        }

        if (renderedHeight <= cropState.stageSize) {
            cropState.offsetY = (cropState.stageSize - renderedHeight) / 2;
        } else {
            cropState.offsetY = Math.min(0, Math.max(minOffsetY, cropState.offsetY));
        }
    }

    function drawCropToCanvas(targetCanvas, clipCircle) {
        if (!targetCanvas || !cropState.image || !cropState.stageSize) {
            return;
        }

        var context = targetCanvas.getContext('2d');
        if (!context) {
            return;
        }

        var canvasWidth = targetCanvas.width;
        var canvasHeight = targetCanvas.height;
        var sourceX = Math.max(0, -cropState.offsetX / cropState.scale);
        var sourceY = Math.max(0, -cropState.offsetY / cropState.scale);
        var sourceSize = cropState.stageSize / cropState.scale;

        context.clearRect(0, 0, canvasWidth, canvasHeight);
        context.save();

        if (clipCircle) {
            context.beginPath();
            context.arc(canvasWidth / 2, canvasHeight / 2, Math.min(canvasWidth, canvasHeight) / 2, 0, Math.PI * 2);
            context.clip();
        }

        context.fillStyle = '#ffffff';
        context.fillRect(0, 0, canvasWidth, canvasHeight);
        context.drawImage(
            cropState.image,
            sourceX,
            sourceY,
            sourceSize,
            sourceSize,
            0,
            0,
            canvasWidth,
            canvasHeight
        );
        context.restore();
    }

    function renderCropState() {
        if (!cropState.image || !cropState.stageSize || !cropImage) {
            return;
        }

        clampCropOffsets();

        cropImage.style.width = (cropState.image.naturalWidth * cropState.scale) + 'px';
        cropImage.style.height = (cropState.image.naturalHeight * cropState.scale) + 'px';
        cropImage.style.transform = 'translate3d(' + cropState.offsetX + 'px, ' + cropState.offsetY + 'px, 0)';

        drawCropToCanvas(cropPreviewCanvas, true);
    }

    function fitImageToCropStage() {
        if (!cropState.image || !cropStage) {
            return;
        }

        var stageSize = cropStage.clientWidth;
        if (!stageSize) {
            window.requestAnimationFrame(fitImageToCropStage);
            return;
        }

        cropState.stageSize = stageSize;
        cropState.minScale = Math.max(
            stageSize / cropState.image.naturalWidth,
            stageSize / cropState.image.naturalHeight
        );
        cropState.scale = cropState.minScale;
        cropState.offsetX = (stageSize - (cropState.image.naturalWidth * cropState.scale)) / 2;
        cropState.offsetY = (stageSize - (cropState.image.naturalHeight * cropState.scale)) / 2;

        if (cropZoomRange) {
            cropZoomRange.value = '100';
            cropZoomRange.disabled = false;
        }

        renderCropState();
    }

    function setCropZoom(percentage) {
        if (!cropState.image || !cropState.stageSize) {
            return;
        }

        var normalizedPercentage = Math.max(100, Math.min(300, parseInt(percentage, 10) || 100));
        var oldScale = cropState.scale;
        var newScale = cropState.minScale * (normalizedPercentage / 100);
        var centerX = (cropState.stageSize / 2 - cropState.offsetX) / oldScale;
        var centerY = (cropState.stageSize / 2 - cropState.offsetY) / oldScale;

        cropState.scale = newScale;
        cropState.offsetX = cropState.stageSize / 2 - centerX * newScale;
        cropState.offsetY = cropState.stageSize / 2 - centerY * newScale;

        renderCropState();
    }

    function stopCropDrag(pointerId) {
        if (!cropState.pointerActive) {
            return;
        }

        if (typeof pointerId === 'number' && cropState.pointerId !== null && pointerId !== cropState.pointerId) {
            return;
        }

        if (cropStage && typeof cropStage.releasePointerCapture === 'function' && cropState.pointerId !== null) {
            try {
                cropStage.releasePointerCapture(cropState.pointerId);
            } catch (error) {
                // Ignore browsers that reject release after the pointer is already cleared.
            }
        }

        cropState.pointerActive = false;
        cropState.pointerId = null;
        cropState.lastPointerX = 0;
        cropState.lastPointerY = 0;

        if (cropStage) {
            cropStage.classList.remove('is-dragging');
        }
    }

    function validateSelectedProfilePhoto(file) {
        if (!file) {
            return 'Pilih gambar profil terlebih dahulu.';
        }

        var fileName = String(file.name || '').toLowerCase();
        var validExtension = /\.(jpg|jpeg|png)$/.test(fileName);
        var validMime = file.type === 'image/jpeg' || file.type === 'image/png';

        if (!validExtension || !validMime) {
            return 'Format gambar profil harus JPG, JPEG, atau PNG.';
        }

        return '';
    }

    function openCropperWithFile(file) {
        var modalInstance = getCropModal();
        if (!modalInstance || !cropStage || !cropImage) {
            uploadForm.submit();
            return;
        }

        var objectUrl = URL.createObjectURL(file);
        var previewImage = new Image();

        previewImage.onload = function () {
            cropState.image = previewImage;
            cropState.objectUrl = objectUrl;
            cropImage.src = objectUrl;
            modalInstance.show();
            window.setTimeout(fitImageToCropStage, 180);
        };

        previewImage.onerror = function () {
            URL.revokeObjectURL(objectUrl);
            showProfileAlert('warning', '<b>FILE TIDAK VALID</b>', 'Gambar tidak dapat diproses. Silakan pilih file lain.');
            resetCropper(true);
        };

        previewImage.src = objectUrl;
    }

    function applyCropAndSubmit() {
        if (!cropState.image || !fileInput || !uploadForm) {
            return;
        }

        cropApplyButton.disabled = true;
        cropApplyButton.textContent = 'MEMPROSES...';

        var outputCanvas = document.createElement('canvas');
        outputCanvas.width = 600;
        outputCanvas.height = 600;
        drawCropToCanvas(outputCanvas, false);

        var qualitySteps = [0.92, 0.86, 0.8];
        var qualityIndex = 0;

        function exportNextQuality() {
            var quality = qualitySteps[qualityIndex];

            outputCanvas.toBlob(function (blob) {
                if (!blob) {
                    cropApplyButton.disabled = false;
                    cropApplyButton.textContent = 'GUNAKAN FOTO INI';
                    showProfileAlert('warning', '<b>GAGAL MEMPROSES</b>', 'Gambar gagal diproses. Silakan coba lagi.');
                    return;
                }

                if (blob.size > 1048576 && qualityIndex < qualitySteps.length - 1) {
                    qualityIndex += 1;
                    exportNextQuality();
                    return;
                }

                if (blob.size > 1048576) {
                    cropApplyButton.disabled = false;
                    cropApplyButton.textContent = 'GUNAKAN FOTO INI';
                    showProfileAlert(
                        'warning',
                        '<b>UKURAN TERLALU BESAR</b>',
                        'Hasil crop masih lebih besar dari 1MB. Coba gunakan gambar lain atau atur ulang crop.'
                    );
                    return;
                }

                var croppedFile = new File(
                    [blob],
                    'profile-photo-' + Date.now() + '.jpg',
                    { type: 'image/jpeg' }
                );
                var dataTransfer = new DataTransfer();
                dataTransfer.items.add(croppedFile);
                fileInput.files = dataTransfer.files;
                uploadForm.dataset.profileSubmitting = '1';
                uploadForm.submit();
            }, 'image/jpeg', quality);
        }

        exportNextQuality();
    }

    function getFieldContainer(input) {
        return input ? (input.closest('.password-field') || input.parentElement) : null;
    }

    function resetFieldState(input) {
        if (!input) {
            return;
        }

        input.classList.remove('is-invalid', 'is-valid');

        var container = getFieldContainer(input);
        if (!container) {
            return;
        }

        var invalidEl = container.querySelector('.invalid-feedback');
        var validEl = container.querySelector('.valid-feedback');

        if (invalidEl) invalidEl.style.display = 'none';
        if (validEl) validEl.style.display = 'none';
    }

    function setInputState(input, type, invalidMessage, validMessage) {
        if (!input) {
            return false;
        }

        resetFieldState(input);

        var container = getFieldContainer(input);
        var invalidEl = container ? container.querySelector('.invalid-feedback') : null;
        var validEl = container ? container.querySelector('.valid-feedback') : null;

        if (invalidEl && typeof invalidMessage === 'string') {
            invalidEl.textContent = invalidMessage;
        }

        if (validEl && typeof validMessage === 'string') {
            validEl.textContent = validMessage;
        }

        if (type === 'invalid') {
            input.classList.add('is-invalid');
            if (invalidEl) invalidEl.style.display = 'block';
            return false;
        }

        if (type === 'valid') {
            input.classList.add('is-valid');
            if (validEl) validEl.style.display = 'block';
            return true;
        }

        return false;
    }

    function bindPasswordToggles() {
        document.querySelectorAll('.password-toggle-icon').forEach(function (toggle) {
            if (toggle.dataset.profileBound === '1') {
                return;
            }

            toggle.dataset.profileBound = '1';

            function doToggle() {
                var targetId = toggle.getAttribute('data-target');
                var input = document.getElementById(targetId);
                var icon = toggle.querySelector('i');

                if (!input || !icon) {
                    return;
                }

                if (input.type === 'password') {
                    input.type = 'text';
                    icon.className = 'ti ti-eye-off';
                    toggle.setAttribute('aria-label', 'Sembunyikan password');
                } else {
                    input.type = 'password';
                    icon.className = 'ti ti-eye';
                    toggle.setAttribute('aria-label', 'Tampilkan password');
                }
            }

            toggle.addEventListener('click', doToggle);
            toggle.addEventListener('keydown', function (event) {
                if (event.key === 'Enter' || event.key === ' ') {
                    event.preventDefault();
                    doToggle();
                }
            });
        });
    }

    function validateRequiredPassword(input, emptyMessage) {
        if (!input) {
            return false;
        }

        if (input.value.trim() === '') {
            return setInputState(input, 'invalid', emptyMessage, '');
        }

        return setInputState(input, 'valid', emptyMessage, 'Field sudah diisi');
    }

    function validateNewPassword(input) {
        if (!input) {
            return false;
        }

        var value = input.value.trim();

        if (value === '') {
            return setInputState(input, 'invalid', 'Password baru wajib diisi', 'Password sesuai');
        }

        if (!/^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{8,}$/.test(value)) {
            return setInputState(
                input,
                'invalid',
                'Password minimal 8 karakter dan harus terdiri dari huruf dan angka',
                'Password sesuai'
            );
        }

        return setInputState(
            input,
            'valid',
            'Password minimal 8 karakter dan harus terdiri dari huruf dan angka',
            'Password sesuai'
        );
    }

    function validatePasswordConfirmation(passwordInput, repeatInput) {
        if (!repeatInput) {
            return false;
        }

        if (repeatInput.value.trim() === '') {
            return setInputState(repeatInput, 'invalid', 'Ulangi Password belum diisi', 'Ulangi Password sesuai');
        }

        if (!passwordInput || repeatInput.value !== passwordInput.value) {
            return setInputState(repeatInput, 'invalid', 'Ulangi Password tidak sesuai', 'Ulangi Password sesuai');
        }

        return setInputState(repeatInput, 'valid', 'Ulangi Password tidak sesuai', 'Ulangi Password sesuai');
    }

    if (uploadButton && fileInput) {
        uploadButton.addEventListener('click', function () {
            fileInput.click();
        });
    }

    if (fileInput) {
        fileInput.addEventListener('change', function () {
            var selectedFile = fileInput.files && fileInput.files[0] ? fileInput.files[0] : null;
            if (!selectedFile) {
                return;
            }

            var validationMessage = validateSelectedProfilePhoto(selectedFile);
            if (validationMessage !== '') {
                showProfileAlert('warning', '<b>FILE TIDAK VALID</b>', validationMessage);
                resetCropper(true);
                return;
            }

            resetCropper(false);
            openCropperWithFile(selectedFile);
        });
    }

    if (cropModalEl) {
        cropModalEl.addEventListener('shown.bs.modal', function () {
            window.setTimeout(fitImageToCropStage, 120);
        });

        cropModalEl.addEventListener('hidden.bs.modal', function () {
            if (uploadForm && uploadForm.dataset.profileSubmitting === '1') {
                return;
            }

            stopCropDrag();
            resetCropper(true);
        });
    }

    if (cropZoomRange) {
        cropZoomRange.addEventListener('input', function () {
            setCropZoom(this.value);
        });
    }

    if (cropStage) {
        cropStage.addEventListener('dragstart', function (event) {
            event.preventDefault();
        });

        cropStage.addEventListener('pointerdown', function (event) {
            if (!cropState.image) {
                return;
            }

            if (typeof event.button === 'number' && event.button !== 0) {
                return;
            }

            event.preventDefault();
            cropState.pointerActive = true;
            cropState.pointerId = event.pointerId;
            cropState.lastPointerX = event.clientX;
            cropState.lastPointerY = event.clientY;

            cropStage.classList.add('is-dragging');

            if (typeof cropStage.setPointerCapture === 'function') {
                cropStage.setPointerCapture(event.pointerId);
            }
        });

        cropStage.addEventListener('wheel', function (event) {
            if (!cropState.image || !cropZoomRange) {
                return;
            }

            event.preventDefault();

            var currentValue = parseInt(cropZoomRange.value, 10) || 100;
            var nextValue = currentValue + (event.deltaY < 0 ? 5 : -5);
            nextValue = Math.max(100, Math.min(300, nextValue));
            cropZoomRange.value = String(nextValue);
            setCropZoom(nextValue);
        }, { passive: false });
    }

    window.addEventListener('pointermove', function (event) {
        if (!cropState.pointerActive || cropState.pointerId !== event.pointerId) {
            return;
        }

        event.preventDefault();
        cropState.offsetX += event.clientX - cropState.lastPointerX;
        cropState.offsetY += event.clientY - cropState.lastPointerY;
        cropState.lastPointerX = event.clientX;
        cropState.lastPointerY = event.clientY;
        renderCropState();
    }, { passive: false });

    ['pointerup', 'pointercancel'].forEach(function (eventName) {
        window.addEventListener(eventName, function (event) {
            stopCropDrag(event.pointerId);
        });
    });

    window.addEventListener('blur', function () {
        stopCropDrag();
    });

    if (cropApplyButton) {
        cropApplyButton.addEventListener('click', applyCropAndSubmit);
    }

    window.addEventListener('resize', function () {
        if (cropModalEl && cropModalEl.classList.contains('show') && cropState.image) {
            window.setTimeout(fitImageToCropStage, 100);
        }
    });

    bindPasswordToggles();

    if (currentPasswordInput) {
        ['input', 'change', 'blur'].forEach(function (eventName) {
            currentPasswordInput.addEventListener(eventName, function () {
                validateRequiredPassword(currentPasswordInput, 'Password sekarang wajib diisi');
            });
        });
    }

    if (newPasswordInput) {
        ['input', 'change', 'blur'].forEach(function (eventName) {
            newPasswordInput.addEventListener(eventName, function () {
                validateNewPassword(newPasswordInput);
                validatePasswordConfirmation(newPasswordInput, confirmationInput);
            });
        });
    }

    if (confirmationInput) {
        ['input', 'change', 'blur'].forEach(function (eventName) {
            confirmationInput.addEventListener(eventName, function () {
                validatePasswordConfirmation(newPasswordInput, confirmationInput);
            });
        });
    }

    var profilePhotoResetButton = document.getElementById('profilePhotoResetButton');
    var profilePhotoResetForm = document.getElementById('profilePhotoResetForm');

    if (profilePhotoResetButton && profilePhotoResetForm) {
        profilePhotoResetButton.addEventListener('click', function (e) {
            e.preventDefault();
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'warning',
                    title: '<b>RESET FOTO PROFIL</b>',
                    text: 'Apakah Anda yakin ingin mereset foto profil ke gambar default?',
                    showCancelButton: true,
                    confirmButtonText: 'YA, RESET',
                    cancelButtonText: 'BATAL',
                    confirmButtonColor: '#dc3545'
                }).then(function (result) {
                    if (result.isConfirmed) {
                        profilePhotoResetForm.submit();
                    }
                });
            } else {
                if (confirm('Apakah Anda yakin ingin mereset foto profil ke gambar default?')) {
                    profilePhotoResetForm.submit();
                }
            }
        });
    }

    if (passwordForm) {
        passwordForm.addEventListener('submit', function (event) {
            var validCurrentPassword = validateRequiredPassword(currentPasswordInput, 'Password sekarang wajib diisi');
            var validNewPassword = validateNewPassword(newPasswordInput);
            var validConfirmation = validatePasswordConfirmation(newPasswordInput, confirmationInput);

            if (!validCurrentPassword || !validNewPassword || !validConfirmation) {
                event.preventDefault();
            }
        });
    }
})();
</script>
@endsection
