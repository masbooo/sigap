@extends('layouts.auth')

@section('authTopAction')
    <a
        href="{{ url('/') }}"
        class="btn auth-back-button bg-danger-subtle text-danger waves-effect border-0 shadow-sm px-3 px-md-4 py-2 d-inline-flex align-items-center gap-2"
    >
        <i class="ti ti-arrow-left fs-4"></i>
        <span class="fw-medium">KEMBALI</span>
    </a>
@endsection

@section('content')

<div class="auth-max-width col-sm-8 col-md-6 col-xl-7 px-4">
    <div class="position-relative text-center">
        <img src="{{ asset('assets/custom/images/logos/pemkot.svg') }}" class="mb-3 w-25" alt="Logo Pemkot" />
        <h2 class="mb-3 fs-7 fw-bolder text-center">DAFTAR SIGAP</h2>
    </div>

    <form action="{{ url('daftar') }}" method="POST" autocomplete="off" id="registerForm" data-check-username-url="{{ url('cek-username') }}" novalidate>
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
                value="{{ session('old_username', '') }}"
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

        <div class="row mb-3 auth-captcha-row">
            <label class="form-label" for="loginCaptcha">Captcha</label>
            <div class="col-6 auth-captcha-preview-col">
                <div class="d-flex align-items-center gap-2 mb-2 auth-captcha-tools">
                    <img
                        src="{{ url('captcha') }}"
                        id="captcha-img"
                        alt="Captcha"
                        class="auth-captcha-image"
                    >

                    <button type="button" class="btn btn-sm btn-secondary auth-captcha-refresh" id="reload-captcha-btn" aria-label="Muat ulang captcha">
                        <i class="ti ti-refresh"></i>
                    </button>
                </div>
            </div>
            <div class="col-6 auth-captcha-input-col">
                <input
                    type="text"
                    id="loginCaptcha"
                    name="captcha"
                    class="form-control auth-captcha-input"
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
        <a href="{{ url('login') }}" class="text-primary fw-semibold ms-1">Masuk di sini!</a>
    </div>
</div>

@if (!empty($error))
<div class="d-none" data-auth-flash="error" data-message="{!! htmlspecialchars((string) $error, ENT_QUOTES, 'UTF-8') !!}"></div>
@endif

@if (!empty($success))
<div class="d-none" data-auth-flash="success" data-message="{!! htmlspecialchars((string) $success, ENT_QUOTES, 'UTF-8') !!}"></div>
@endif

@endsection
