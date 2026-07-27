<!DOCTYPE html>
<html lang="en" dir="ltr" data-bs-theme="light" data-color-theme="Blue_Theme" data-layout="vertical">

<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />

    <link rel="shortcut icon" type="image/png" href="{{ asset('assets/custom/images/logos/sigap32.svg') }}">

    <link rel="stylesheet" href="{{ asset('assets/custom/css/styles.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/main/css/styles.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/custom/libs/sweetalert2/sweetalert2.min.css') }}">

    <title>{{ $title ?? 'Login SIGAP' }}</title>
</head>

<body>  
    @php
        $authLogoUrl = $authLogoUrl ?? url('login');
        $authSessionRedirectConfig = is_array($authSessionRedirectConfig ?? null)
            ? $authSessionRedirectConfig
            : ['active' => false];
    @endphp

    <script>
        window.__authSessionRedirectConfig = {!! json_encode($authSessionRedirectConfig, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!};
    </script>
    <div id="main-wrapper" class="auth-customizer-none">
        <div class="position-relative overflow-hidden radial-gradient min-vh-100 w-100">
            <div class="position-relative z-index-5">
                <div class="row g-0">
                    <div class="col-xl-7 col-xxl-8 position-relative">
                        @hasSection('authTopAction')
                            <div class="position-absolute top-0 end-0 mt-3 mt-md-4 me-3 me-md-4 me-xl-5 z-3">
                                @yield('authTopAction')
                            </div>
                        @endif

                        <a href="{{ $authLogoUrl }}" class="text-nowrap logo-img d-block px-4 py-9 w-100">
                            <img src="{{ asset('assets/custom/images/logos/logotxt_sigap_b.svg') }}" class="dark-logo sigap-logo-text" alt="Logo-Dark" />
                        </a>

                        <div class="d-none d-xl-flex align-items-center justify-content-center h-n80">
                            <img src="{{ asset('assets/custom/images/backgrounds/login-security.svg') }}" alt="login-illustration" class="img-fluid" width="500">
                        </div>
                    </div>

                    <div class="col-xl-5 col-xxl-4">
                        <div class="authentication-login bg-body w-100 d-flex flex-column justify-content-start align-items-center p-4 pt-5 min-vh-100">
                            @yield('content')
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="{{ asset('assets/custom/libs/bootstrap/dist/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('assets/custom/libs/simplebar/dist/simplebar.min.js') }}"></script>
    <script src="{{ asset('assets/custom/js/theme/app.init.js') }}"></script>
    <script src="{{ asset('assets/custom/js/theme/theme.js') }}"></script>
    <script src="{{ asset('assets/custom/js/theme/app.min.js') }}"></script>
    <script src="{{ asset('assets/custom/libs/sweetalert2/sweetalert2.min.js') }}"></script>
    <script src="{{ asset('assets/main/js/login.js') }}"></script>
    <script src="{{ asset('assets/js/plugins/bootstrap-validation-init.js') }}"></script>
</body>
</html>
