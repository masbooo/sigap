<?php
$navbarAdmin = $admin ?? admin_user() ?? [];
$roleContext = resolve_admin_role_context($navbarAdmin);
$profileName = trim((string) ($navbarAdmin['name'] ?? '')) !== ''
    ? trim((string) $navbarAdmin['name'])
    : trim((string) ($navbarAdmin['username'] ?? 'Administrator'));
$profileSubtitle = trim((string) ($roleContext['role_label'] ?? 'Admin'));
$districtName = trim((string) ($roleContext['district_name'] ?? ''));

if ($districtName !== '' && ($roleContext['scope_type'] ?? 'all') === 'district') {
    $profileSubtitle .= ' - ' . $districtName;
}
$defaultProfilePhoto = asset_url('assets/custom/images/profile/user-1.jpg');
?>
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
                    <img src="{{ asset_url('assets/custom/images/logos/sigap32.svg') }}" class="img-fluid" alt="SIGAP">
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
                        <li class="nav-item nav-icon-hover-bg rounded-circle dropdown">
                            <a class="nav-link position-relative" href="javascript:void(0)" id="drop2" role="button" data-bs-toggle="dropdown" data-bs-auto-close="true" aria-expanded="false">
                                <i class="ti ti-bell-ringing"></i>
                                <div class="notification bg-danger rounded-circle"></div>
                            </a>

                            <div class="dropdown-menu content-dd dropdown-menu-end dropdown-menu-animate-up" aria-labelledby="drop2">
                                <div class="py-3 px-7">
                                    <h5 class="mb-1 fs-5 fw-semibold">Notifikasi Admin</h5>
                                    <p class="mb-0 fs-2 text-body-secondary">Panel notifikasi admin siap digunakan.</p>
                                </div>
                            </div>
                        </li>

                        <li class="nav-item dropdown">
                            <a class="nav-link pe-0" href="javascript:void(0)" id="drop1" role="button" data-bs-toggle="dropdown" data-bs-auto-close="true" aria-expanded="false">
                                <div class="d-flex align-items-center">
                                    <div class="user-profile-img">
                                        <img
                                            src="{{ $defaultProfilePhoto }}"
                                            alt="{{ $profileName }}"
                                            class="rounded-circle"
                                            width="35"
                                            height="35"
                                        />
                                    </div>
                                </div>
                            </a>

                            <div class="dropdown-menu content-dd dropdown-menu-end dropdown-menu-animate-up" aria-labelledby="drop1">
                                <div class="profile-dropdown position-relative" data-simplebar>
                                    <div class="py-3 px-7 pb-0">
                                        <h5 class="mb-0 fs-5 fw-semibold">Akun Admin</h5>
                                    </div>

                                    <div class="d-flex align-items-center py-9 mx-7 border-bottom">
                                        <img
                                            src="{{ $defaultProfilePhoto }}"
                                            alt="{{ $profileName }}"
                                            class="rounded-circle"
                                            width="80"
                                            height="80"
                                        />
                                        <div class="ms-3">
                                            <h5 class="mb-1 fs-3">{{ $profileName }}</h5>
                                            <span class="mb-1 d-block">{{ $profileSubtitle }}</span>
                                            <small class="text-muted">{{ '@' . ($navbarAdmin['username'] ?? 'admin') }}</small>
                                        </div>
                                    </div>

                                    <div class="d-grid px-7 admin-profile-logout-wrap">
                                        <a href="{{ base_url('admin/logout') }}" class="btn btn-outline-primary">KELUAR</a>
                                    </div>
                                </div>
                            </div>
                        </li>
                    </ul>
                </div>
            </nav>
        </div>
    </header>
