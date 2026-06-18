<?php
$sidebarUser = $user ?? user();
$ratingNotifications = resolve_user_rating_notifications($sidebarUser);
$ratingPendingCount = (int) ($ratingNotifications['pending_count'] ?? 0);
?>
{{-- <div id="main-wrapper"> --}}
    <!-- Sidebar Start -->
    <aside class="left-sidebar with-vertical">
        <div>

            <div class="brand-logo d-flex align-items-center justify-content-between">
                <a href="{{ base_url('user/dasbor') }}" class="text-nowrap logo-img">
                    <img src="{{ base_url('assets/custom/images/logos/logotxt_sigap_b.svg') }}" class="dark-logo" style="width:160px;" alt="Logo-Dark" />
                    <img src="{{ base_url('assets/custom/images/logos/logotxt_sigap_w.svg') }}" class="light-logo" alt="Logo-light" />
                </a>
                <a href="javascript:void(0)" class="sidebartoggler ms-auto text-decoration-none fs-5 d-block d-xl-none">
                    <i class="ti ti-x"></i>
                </a>
            </div>

            <nav class="sidebar-nav scroll-sidebar" data-simplebar>
                <ul id="sidebarnav">

                    <li class="nav-small-cap">
                        <i class="ti ti-dots nav-small-cap-icon fs-4"></i>
                        <span class="hide-menu">Dasbor</span>
                    </li>

                    <li class="sidebar-item">
                        <a class="sidebar-link ajax-link" href="{{ base_url('user/dasbor') }}">
                            <i class="ti ti-chart-pie-3"></i>
                            <span class="hide-menu">Infografis</span>
                        </a>
                    </li>

                    <li class="nav-small-cap">
                        <i class="ti ti-dots nav-small-cap-icon fs-4"></i>
                        <span class="hide-menu">Data</span>
                    </li>

                    <li class="sidebar-item">
                        <a class="sidebar-link ajax-link" href="{{ base_url('user/reservasi') }}">
                            <i class="ti ti-calendar-event"></i>
                            <span class="hide-menu">Reservasi</span>
                        </a>
                    </li>

                    <li class="sidebar-item">
                        <a class="sidebar-link ajax-link" href="{{ base_url('user/rating') }}">
                            <i class="ti ti-star"></i>
                            <span class="hide-menu">Rating</span>
                        </a>
                    </li>

                    <li class="nav-small-cap">
                        <i class="ti ti-dots nav-small-cap-icon fs-4"></i>
                        <span class="hide-menu">Pengaturan</span>
                    </li>

                    <li class="sidebar-item">
                        <a class="sidebar-link ajax-link" href="{{ base_url('user/profil') }}">
                            <i class="ti ti-user"></i>
                            <span class="hide-menu">Profil</span>
                        </a>
                    </li>

                    <li class="sidebar-item">
                        <a class="sidebar-link ajax-link" href="{{ base_url('user/faq') }}">
                            <i class="ti ti-help"></i>
                            <span class="hide-menu">FAQ</span>
                        </a>
                    </li>

                </ul>
            </nav>

        </div>
    </aside>
{{-- </div> --}}
