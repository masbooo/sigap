@extends('layouts.auth')

@section('content')

<div class="auth-max-width col-sm-8 col-md-6 col-xl-7 px-4">
    <div class="position-relative text-center">
        <img src="{{ asset_url('assets/custom/images/logos/pemkot.svg') }}" class="mb-3 w-25" alt="Logo Pemkot" />
        <h2 class="mb-3 fs-7 fw-bolder text-center">DAFTAR SIGAP</h2>
    </div>

    <form action="{{ base_url('daftar') }}" method="POST" autocomplete="off" id="registerForm" novalidate>
        <input type="hidden" name="_token" value="{{ csrf_token() }}">

        <div class="mb-3">
            <label class="form-label" for="registerUsername">Username</label>
            <input
                type="text"
                id="registerUsername"
                name="username"
                class="form-control"
                placeholder="Masukkan Username"
                maxlength="30"
                value="{{ htmlspecialchars($_SESSION['old_username'] ?? '', ENT_QUOTES, 'UTF-8') }}"
            >
            <div class="invalid-feedback">Username belum diisi</div>
            <div class="valid-feedback">Username belum digunakan (tersedia)</div>
        </div>

        <div class="row">
            <div class="col-md-6 mb-3 password-field">
                <label class="form-label" for="registerPassword">Password</label>

                <div class="password-group">
                    <input
                        type="password"
                        id="registerPassword"
                        name="password"
                        class="form-control password-input"
                        placeholder="Masukkan Password"
                    >

                    <span
                        class="password-toggle-icon"
                        data-target="registerPassword"
                        role="button"
                        tabindex="0"
                        aria-label="Tampilkan password"
                    >
                        <i class="ti ti-eye"></i>
                    </span>
                </div>

                <div class="invalid-feedback">Password minimal 8 karakter dan harus terdiri dari huruf dan angka</div>
            </div>

            <div class="col-md-6 mb-3 password-field">
                <label class="form-label" for="registerPasswordConfirmation">Ulangi Password</label>

                <div class="password-group">
                    <input
                        type="password"
                        id="registerPasswordConfirmation"
                        name="password_confirmation"
                        class="form-control password-input"
                        placeholder="Ulangi Password"
                    >

                    <span
                        class="password-toggle-icon"
                        data-target="registerPasswordConfirmation"
                        role="button"
                        tabindex="0"
                        aria-label="Tampilkan password"
                    >
                        <i class="ti ti-eye"></i>
                    </span>
                </div>

                <div class="invalid-feedback">Ulangi Password tidak sesuai</div>
            </div>
        </div>

        <div class="row mb-3">
            <label class="form-label" for="loginCaptcha">Captcha</label>
            <div class="col-6">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <img
                        src="{{ base_url('captcha') }}"
                        id="captcha-img"
                        alt="Captcha"
                        style="height: 45px; width: 130px; border: 1px solid #ddd; border-radius: 6px; background: #fff;"
                    >

                    <button type="button" class="btn btn-sm btn-secondary" id="reload-captcha-btn">
                        ↻
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
                >
                <div class="invalid-feedback">Captcha belum diisi</div>
            </div>
        </div>

        <button type="submit" class="btn btn-success w-100 py-8 mb-3 rounded-2">DAFTAR</button>
    </form>

    <div class="text-center">
        <span>Sudah punya akun SIGAP?</span>
        <a href="{{ base_url('login') }}" class="text-primary fw-semibold ms-1">Masuk di sini!</a>
    </div>
</div>

<script>
    window.__checkUsernameUrl = "{{ base_url('cek-username') }}";
</script>

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

@endsection
