<!-- ------------------------------------- -->
<!-- Header Start -->
<!-- ------------------------------------- -->
<header class="header-fp p-0 w-100">
    <nav class="navbar navbar-expand-lg bg-primary-subtle py-2 py-lg-10">
        <div class="landing-shell d-flex align-items-center justify-content-between w-100">
            <!-- LOGO (KIRI) -->
            <a href="{{ url('/') }}" class="text-nowrap logo-img">
                <img
                    src="{{ asset('assets/custom/images/logos/logotxt_sigap_b.svg') }}"
                    class="dark-logo"
                    style="width:120px;height:auto;"
                    width="120"
                    alt="Logo-Dark"
                    fetchpriority="high"
                    decoding="sync"
                />
                <img
                    src="{{ asset('assets/custom/images/logos/logotxt_sigap_w.svg') }}"
                    class="light-logo"
                    style="display:none;width:120px;height:auto;"
                    width="120"
                    alt="Logo-light"
                    fetchpriority="high"
                    decoding="sync"
                />
            </a>

            <!-- TOGGLER -->
            <button
                class="navbar-toggler border-0 p-0 shadow-none d-lg-none"
                type="button"
                data-bs-toggle="offcanvas"
                data-bs-target="#offcanvasRight"
                aria-controls="offcanvasRight"
                aria-label="Toggle navigation"
            >
                <i class="ti ti-menu-2 fs-8"></i>
            </button>

            <!-- MENU DESKTOP -->
            <div class="collapse navbar-collapse d-none d-lg-flex" id="navbarSupportedContent">
                <ul class="navbar-nav mx-auto mb-2 gap-xl-7 gap-8 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link landing-navbar-link fs-4 fw-bold text-dark link-primary" href="{{ url('/') }}">Beranda</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link landing-navbar-link fs-4 fw-bold text-dark link-primary" href="{{ url('gedung') }}">Gedung</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link landing-navbar-link fs-4 fw-bold text-dark link-primary" href="{{ url('umkm') }}">UMKM</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link landing-navbar-link fs-4 fw-bold text-dark link-primary" href="{{ url('jadwal') }}">Jadwal</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link landing-navbar-link fs-4 fw-bold text-dark link-primary" href="{{ url('kontak') }}">Kontak</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link landing-navbar-link fs-4 fw-bold text-dark link-primary" href="{{ url('faq') }}">Tanya</a>
                    </li>
                </ul>

                <div class="landing-login-wrap">
                    <a href="{{ url('login') }}" data-no-pjax class="btn btn-primary py-8 px-9">MASUK</a>
                </div>
            </div>
        </div>
    </nav>
</header>
<!-- ------------------------------------- -->
<!-- Header End -->
<!-- ------------------------------------- -->

<!-- ------------------------------------- -->
<!-- Responsive Sidebar Start -->
<!-- ------------------------------------- -->
<div class="offcanvas offcanvas-end landing-offcanvas" tabindex="-1" id="offcanvasRight" aria-labelledby="offcanvasRightLabel">
    <div class="offcanvas-header px-4 pt-4 pb-2 border-0">
        <a href="{{ url('/') }}" class="text-nowrap logo-img">
            <img
                src="{{ asset('assets/custom/images/logos/logotxt_sigap_b.svg') }}"
                class="dark-logo"
                style="width:120px;height:auto;"
                width="120"
                alt="Logo-Dark"
            />
        </a>

        <button type="button" class="btn-close text-reset shadow-none" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>

    <div class="offcanvas-body landing-offcanvas-body px-4 pb-4 mt-4">
        <ul class="list-unstyled ps-0 mb-0 landing-offcanvas-menu">
            <li class="mb-2">
                <a class="landing-navbar-link fs-4 d-block w-100 py-2 text-dark link-primary" href="{{ url('/') }}">Beranda</a>
            </li>
            <li class="mb-2">
                <a class="landing-navbar-link fs-4 d-block w-100 py-2 text-dark link-primary" href="{{ url('gedung') }}">Gedung</a>
            </li>
            <li class="mb-2">
                <a class="landing-navbar-link fs-4 d-block w-100 py-2 text-dark link-primary" href="{{ url('umkm') }}">UMKM</a>
            </li>
            <li class="mb-2">
                <a class="landing-navbar-link fs-4 d-block w-100 py-2 text-dark link-primary" href="{{ url('jadwal') }}">Jadwal</a>
            </li>
            <li class="mb-2">
                <a class="landing-navbar-link fs-4 d-block w-100 py-2 text-dark link-primary" href="{{ url('kontak') }}">Kontak</a>
            </li>
            <li class="mb-2">
                <a class="landing-navbar-link fs-4 d-block w-100 py-2 text-dark link-primary" href="{{ url('faq') }}">Tanya</a>
            </li>
            <li class="mt-2 landing-offcanvas-login">
                <a
                    href="{{ url('login') }}"
                    data-no-pjax
                    class="btn btn-primary w-100 py-2 text-center"
                    style="display:block;"
                >
                    MASUK
                </a>
            </li>
        </ul>
    </div>
</div>
<!-- ------------------------------------- -->
<!-- Responsive Sidebar End -->
<!-- ------------------------------------- -->
