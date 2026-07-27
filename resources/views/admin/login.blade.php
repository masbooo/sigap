@extends('layouts.auth')

@section('content')

<div class="auth-max-width col-sm-9 col-md-7 col-xl-8 px-4">
    <div class="admin-auth-panel my-2 mb-2">
        <div class="position-relative text-center">
            <span class="badge admin-auth-badge mb-3">PORTAL ADMIN</span>
            <img src="{{ asset('assets/custom/images/logos/pemkot.svg') }}" class="mb-4 admin-auth-logo" alt="Logo Pemkot" />
        </div>

        <div class="admin-auth-note">
            <div class="d-flex align-items-start gap-3">
                <span class="admin-auth-note-icon">
                    <i class="ti ti-shield-lock"></i>
                </span>
                <div>
                    <div class="fw-semibold text-dark mb-1">Akses Terbatas</div>
                    <div class="small text-muted">Halaman khusus admin SIGAP</div>
                </div>
            </div>
        </div>
    </div>

    <form action="{{ url('admin/login') }}" method="POST" autocomplete="off" id="loginForm" novalidate>
        <input type="hidden" name="_token" value="{{ csrf_token() }}">

        <div class="mb-3">
            <label class="form-label" for="loginUsername">Username</label>
            <input
                type="text"
                id="loginUsername"
                name="username"
                class="form-control"
                placeholder="Masukkan username admin"
                autocomplete="username"
                required
                value="{{ session('old_admin_username', '') }}"
            >
            <div class="invalid-feedback">Username belum diisi</div>
        </div>

        <div class="mb-3 password-field">
            <label class="form-label" for="loginPassword">Password</label>

            <div class="password-group">
                <input
                    type="password"
                    id="loginPassword"
                    name="password"
                    class="form-control password-input"
                    placeholder="Masukkan password admin"
                    autocomplete="current-password"
                    required
                >

                <span
                    class="password-toggle-icon"
                    data-target="loginPassword"
                    role="button"
                    tabindex="0"
                    aria-label="Tampilkan password"
                >
                    <i class="ti ti-eye"></i>
                </span>
            </div>

            <div class="invalid-feedback">Password belum diisi</div>
        </div>

        <div class="row mb-3">
            <label class="form-label" for="loginCaptcha">Captcha</label>
            <div class="col-6">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <img
                        src="{{ url('captcha') }}"
                        id="captcha-img"
                        alt="Captcha"
                        class="admin-auth-captcha-image"
                    >

                    <button type="button" class="btn btn-sm btn-outline-secondary" id="reload-captcha-btn" aria-label="Muat ulang captcha">
                        <i class="ti ti-refresh"></i>
                    </button>
                </div>
            </div>
            <div class="col-6">
                <input
                    type="text"
                    id="loginCaptcha"
                    name="captcha"
                    class="form-control"
                    placeholder="Masukkan captcha"
                    inputmode="numeric"
                    maxlength="5"
                    required
                >
                <div class="invalid-feedback">Captcha belum diisi</div>
            </div>
        </div>

        <button type="submit" class="btn btn-dark waves-effect w-100 py-8 mb-3 rounded-2 admin-auth-submit">MASUK</button>
    </form>

    <div class="text-center text-muted small admin-auth-footer-note">
        &copy; {{ date('Y') }} SIGAP. Hak Cipta Dilindungi
    </div>
</div>

@if (!empty($error))
<script>
    window.__loginErrorMessage = "{{ addslashes($error) }}";
</script>
@endif

@endsection
