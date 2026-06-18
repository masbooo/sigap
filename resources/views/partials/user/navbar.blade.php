{{-- <div class="page-wrapper"> --}}
<?php
$navbarUser = $user ?? user();
$profileName = resolve_user_display_name($navbarUser);
$profilePhoto = resolve_user_profile_photo_url($navbarUser);
$ratingNotifications = resolve_user_rating_notifications($navbarUser);
$ratingPendingCount = (int) ($ratingNotifications['pending_count'] ?? 0);
$ratingNotificationItems = array_values((array) ($ratingNotifications['items'] ?? []));
$profileSubtitle = trim((string) ($navbarUser['username'] ?? '')) !== ''
    ? '@' . $navbarUser['username']
    : 'User';
$defaultProfilePhoto = resolve_user_default_profile_photo_url($navbarUser);
?>
    <!-- Header Start -->
    <header class="topbar">
        <div class="with-vertical">
            <nav class="navbar navbar-expand-lg p-0 px-3 px-lg-4">
                <ul class="navbar-nav">
                    <li class="nav-item nav-icon-hover-bg rounded-circle ms-n2">
                        <a class="nav-link sidebartoggler" id="headerCollapse" href="javascript:void(0)">
                            <i class="ti ti-menu-2"></i>
                        </a>
                    </li>
                </ul>

                <div class="d-block d-lg-none py-4 logo-img">
                    <img src="{{ asset_url('assets/custom/images/logos/sigap32.svg') }}" class="img-fluid">
                </div>

                <a class="navbar-toggler nav-icon-hover-bg rounded-circle p-0 mx-0 border-0"
                   href="javascript:void(0)"
                   data-bs-toggle="collapse"
                   data-bs-target="#navbarNav"
                   aria-controls="navbarNav"
                   aria-expanded="false"
                   aria-label="Toggle navigation">
                    <i class="ti ti-dots fs-7"></i>
                </a>

                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav flex-row ms-auto align-items-center gap-2">

                        <!-- Notification -->
                        <li class="nav-item nav-icon-hover-bg rounded-circle dropdown">
                            <a class="nav-link position-relative" href="javascript:void(0)" id="drop2" role="button" data-bs-toggle="dropdown" data-bs-auto-close="true" aria-expanded="false">
                                <i class="ti ti-bell-ringing"></i>
                                @if ($ratingPendingCount > 0)
                                    <div class="notification bg-danger rounded-circle"></div>
                                @endif
                            </a>

                            <div class="dropdown-menu content-dd dropdown-menu-end dropdown-menu-animate-up" aria-labelledby="drop2">
                                <div class="d-flex align-items-center justify-content-between py-3 px-7">
                                    <h5 class="mb-0 fs-5 fw-semibold">Notifikasi User</h5>
                                    @if ($ratingPendingCount > 0)
                                        <span class="badge text-bg-danger rounded-4 px-3 py-1 lh-sm">{{ $ratingPendingCount }} new</span>
                                    @else
                                        <span class="badge text-bg-secondary rounded-4 px-3 py-1 lh-sm">0</span>
                                    @endif
                                </div>

                                <div class="message-body" data-simplebar>
                                    @forelse ($ratingNotificationItems as $item)
                                        <a href="{{ $item['href'] ?? base_url('user/rating') }}" class="py-6 px-7 d-flex align-items-center dropdown-item">
                                            <span class="me-3 rounded-circle bg-warning-subtle text-warning d-inline-flex align-items-center justify-content-center shrink-0" style="width:48px;height:48px;">
                                                <i class="ti ti-star fs-6"></i>
                                            </span>
                                            <div class="w-100">
                                                <h6 class="mb-1 fw-semibold lh-base">{{ $item['title'] ?? 'Notifikasi User' }}</h6>
                                                <span class="fs-2 d-block text-body-secondary">{{ $item['subtitle'] ?? 'Silakan buka menu rating untuk memberikan ulasan' }}</span>
                                            </div>
                                        </a>
                                    @empty
                                        <div class="py-6 px-7 text-center text-body-secondary">
                                            Tidak ada rating yang perlu diisi
                                        </div>
                                    @endforelse
                                </div>
                            </div>
                        </li>

                        <!-- Profile -->
                        <li class="nav-item dropdown">
                            <a class="nav-link pe-0" href="javascript:void(0)" id="drop1" role="button"data-bs-toggle="dropdown" data-bs-auto-close="true" aria-expanded="false">
                                <div class="d-flex align-items-center">
                                    <div class="user-profile-img">
                                        <img
                                            src="{{ $profilePhoto }}"
                                            alt="{{ $profileName }}"
                                            class="rounded-circle"
                                            width="35"
                                            height="35"
                                            onerror="this.onerror=null;this.src='{{ $defaultProfilePhoto }}';"
                                        />
                                    </div>
                                </div>
                            </a>

                            <div class="dropdown-menu content-dd dropdown-menu-end dropdown-menu-animate-up" aria-labelledby="drop1">
                                <div class="profile-dropdown position-relative" data-simplebar>
                                    <div class="py-3 px-7 pb-0">
                                        <h5 class="mb-0 fs-5 fw-semibold">Akun Saya</h5>
                                    </div>

                                    <div class="d-flex align-items-center py-9 mx-7 border-bottom">
                                        <img
                                            src="{{ $profilePhoto }}"
                                            alt="{{ $profileName }}"
                                            class="rounded-circle"
                                            width="80"
                                            height="80"
                                            onerror="this.onerror=null;this.src='{{ $defaultProfilePhoto }}';"
                                        />
                                        <div class="ms-3">
                                            <h5 class="mb-1 fs-3">{{ $profileName }}</h5>
                                            <span class="mb-1 d-block">{{ $profileSubtitle }}</span>
                                        </div>
                                    </div>

                                    <div class="message-body">
                                        <a href="{{ base_url('user/profil') }}" class="py-8 px-7 mt-8 d-flex align-items-center ajax-link">
                                            <span class="d-flex align-items-center justify-content-center text-bg-light rounded-1 p-6">
                                                <img src="{{ asset_url('assets/custom/images/svgs/icon-account.svg') }}" width="24" height="24" />
                                            </span>
                                            <div class="w-100 ps-3">
                                                <h6 class="mb-1 fs-3 fw-semibold lh-base">Profilku</h6>
                                                <span class="fs-2 d-block text-body-secondary">Pengaturan</span>
                                            </div>
                                        </a>
                                    </div>

                                    <div class="d-grid py-4 px-7 pt-8">
                                        <a href="{{ base_url('logout') }}" class="btn btn-outline-primary">KELUAR</a>
                                    </div>
                                </div>
                            </div>
                        </li>

                    </ul>
                </div>
            </nav>
        </div>
    </header>
    <!-- Header End -->
{{-- </div> --}}
