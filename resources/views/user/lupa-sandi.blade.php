@extends('layouts.auth')

@section('content')

<div class="auth-max-width col-sm-8 col-md-6 col-xl-7 px-4">
    <div class="position-relative text-center">
        <img src="{{ asset('assets/custom/images/logos/pemkot.svg') }}" class="mb-3 w-25" alt="Logo Pemkot" />
        <h2 class="mb-3 fs-7 fw-bolder text-center">LUPA SANDI SIGAP</h2>
    </div>

    @if (($step ?? 1) === 1)
    <form action="{{ url('lupa-sandi/verifikasi') }}" method="POST" autocomplete="off" id="forgotPasswordVerifyForm" novalidate>
        <input type="hidden" name="_token" value="{{ csrf_token() }}">

        <div class="mb-3">
            <label class="form-label" for="forgotNik">NIK</label>
            <input
                type="text"
                id="forgotNik"
                name="nik"
                class="form-control"
                placeholder="Masukkan NIK"
                inputmode="numeric"
                maxlength="16"
                value="{{ htmlspecialchars($oldForgotPassword['nik'] ?? '', ENT_QUOTES, 'UTF-8') }}"
            >
            <div class="invalid-feedback">NIK belum diisi</div>
        </div>

        <div class="mb-3">
            <label class="form-label" for="forgotPhone">Telp / HP</label>
            <input
                type="tel"
                id="forgotPhone"
                name="phone"
                class="form-control"
                placeholder="Masukkan Telp / HP"
                inputmode="numeric"
                maxlength="15"
                value="{{ htmlspecialchars($oldForgotPassword['phone'] ?? '', ENT_QUOTES, 'UTF-8') }}"
            >
            <div class="invalid-feedback">Telp / HP belum diisi</div>
        </div>

        <div class="row mb-3">
            <label class="form-label" for="forgotCaptcha">Captcha</label>
            <div class="col-6">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <img
                        src="{{ url('captcha') }}"
                        id="captcha-img"
                        alt="Captcha"
                        class="auth-captcha-image"
                    >

                    <button type="button" class="btn btn-sm btn-secondary" id="reload-captcha-btn">
                        ↻
                    </button>
                </div>
            </div>
            <div class="col-6">
                <input
                    type="text"
                    id="forgotCaptcha"
                    name="captcha"
                    class="form-control"
                    placeholder="Masukkan captcha"
                    inputmode="numeric"
                    maxlength="5"
                >
                <div class="invalid-feedback">Captcha belum diisi</div>
            </div>
        </div>

        <button type="submit" class="btn btn-warning w-100 py-8 mb-3 rounded-2">VERIFIKASI</button>
    </form>
    @else
    <p class="text-muted text-center mb-3">Verifikasi berhasil. Silakan buat password baru Anda</p>

    <form action="{{ url('lupa-sandi/reset') }}" method="POST" autocomplete="off" id="forgotPasswordResetForm" novalidate>
        <input type="hidden" name="_token" value="{{ csrf_token() }}">

        <div class="mb-3 password-field">
            <label class="form-label" for="forgotPassword">Password Baru</label>

            <div class="password-group">
                <input
                    type="password"
                    id="forgotPassword"
                    name="password"
                    class="form-control password-input"
                    placeholder="Masukkan Password Baru"
                >

                <span
                    class="password-toggle-icon"
                    data-target="forgotPassword"
                    role="button"
                    tabindex="0"
                    aria-label="Tampilkan password"
                >
                    <i class="ti ti-eye"></i>
                </span>
            </div>

            <div class="invalid-feedback">Password minimal 8 karakter dan harus terdiri dari huruf dan angka</div>
        </div>

        <div class="mb-4 password-field">
            <label class="form-label" for="forgotPasswordConfirmation">Ulangi Password Baru</label>

            <div class="password-group">
                <input
                    type="password"
                    id="forgotPasswordConfirmation"
                    name="password_confirmation"
                    class="form-control password-input"
                    placeholder="Ulangi Password Baru"
                >

                <span
                    class="password-toggle-icon"
                    data-target="forgotPasswordConfirmation"
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
        <div class="row">
             <div class="col-6">
                <a href="{{ url('lupa-sandi/batal') }}" class="btn btn-danger w-100 py-8 mb-3 rounded-2">BATAL</a>
            </div>
            <div class="col-6">
                <button type="submit" class="btn btn-success w-100 py-8 mb-3 rounded-2">SIMPAN</button>
            </div>
        </div>
    </form>
    @endif

    <div class="text-center">
        <span>Sudah punya akun SIGAP?</span>
        <a href="{{ url('lupa-sandi/batal') }}" class="text-primary fw-semibold ms-1">Masuk di sini!</a>
    </div>
</div>

@if (!empty($error))
<div class="d-none" data-auth-flash="error" data-message="{!! htmlspecialchars((string) $error, ENT_QUOTES, 'UTF-8') !!}"></div>
@endif

@if (!empty($success))
<div class="d-none" data-auth-flash="success" data-message="{!! htmlspecialchars((string) $success, ENT_QUOTES, 'UTF-8') !!}"></div>
@endif

@endsection
