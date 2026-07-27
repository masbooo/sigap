<?php

function set_authenticated_user_session(array $user): void
{
    session([
        'user_auth' => true,
        'user' => [
        'id' => $user['id'],
        'username' => $user['username'],
        'name' => $user['name'],
        'nik' => $user['nik'],
        'gender' => $user['gender'] ?? null,
        'district_id' => $user['district_id'],
        'subdistrict_id' => $user['subdistrict_id'],
        'id_path' => $user['id_path'] ?? '',
        'pic_path' => $user['pic_path'] ?? '',
        'status' => $user['status']
        ],
        'user_last_activity_at' => time(),
        'user_browser_session_bootstrap_pending' => true,
    ]);

    session()->forget([
        'admin_auth',
        'admin',
        'admin_last_activity_at',
        'admin_browser_session_bootstrap_pending',
    ]);
}

function set_authenticated_admin_session(array $admin): void
{
    session([
        'admin_auth' => true,
        'admin' => [
        'id' => $admin['id'],
        'username' => $admin['username'],
        'name' => $admin['name'],
        'role_id' => $admin['role_id'],
        'district_id' => $admin['district_id'],
        'status' => $admin['status']
        ],
        'admin_last_activity_at' => time(),
        'admin_browser_session_bootstrap_pending' => true,
    ]);

    session()->forget([
        'user_auth',
        'user',
        'user_last_activity_at',
        'user_browser_session_bootstrap_pending',
    ]);
}

function auth_session_idle_timeout_seconds(string $scope = 'user'): int
{
    $constantName = strtolower(trim($scope)) === 'admin'
        ? 'SIGAP_ADMIN_SESSION_IDLE_TIMEOUT'
        : 'SIGAP_USER_SESSION_IDLE_TIMEOUT';
    $timeout = defined($constantName)
        ? (int) constant($constantName)
        : (defined('SIGAP_SESSION_IDLE_TIMEOUT') ? (int) SIGAP_SESSION_IDLE_TIMEOUT : 60 * 15);

    return max(300, $timeout);
}

function user_session_idle_timeout_seconds(): int
{
    return auth_session_idle_timeout_seconds('user');
}

function admin_session_idle_timeout_seconds(): int
{
    return auth_session_idle_timeout_seconds('admin');
}

function auth_session_expired_message(string $scope = 'user'): string
{
    $timeoutMinutes = (int) round(auth_session_idle_timeout_seconds($scope) / 60);

    if (strtolower(trim($scope)) === 'admin') {
        return 'Sesi admin Anda telah berakhir karena tidak ada aktivitas selama ' . $timeoutMinutes . ' menit. Silakan login kembali';
    }

    return 'Sesi Anda telah berakhir karena tidak ada aktivitas selama ' . $timeoutMinutes . ' menit. Silakan login kembali';
}

function touch_user_session_activity(?int $timestamp = null): void
{
    if (session('user_auth') !== true || empty(session('user.id'))) {
        return;
    }

    session(['user_last_activity_at' => $timestamp ?? time()]);
}

function touch_admin_session_activity(?int $timestamp = null): void
{
    if (session('admin_auth') !== true || empty(session('admin.id'))) {
        return;
    }

    session(['admin_last_activity_at' => $timestamp ?? time()]);
}

function destroy_active_session(): void
{
    session()->invalidate();
    session()->regenerateToken();
}

function destroy_user_auth_session(): void
{
    session()->forget([
        'user_auth',
        'user',
        'user_last_activity_at',
        'user_browser_session_bootstrap_pending',
    ]);

    if (empty(session('admin_auth')) && empty(session('admin'))) {
        destroy_active_session();
    }
}

function destroy_admin_auth_session(): void
{
    session()->forget([
        'admin_auth',
        'admin',
        'admin_last_activity_at',
        'admin_browser_session_bootstrap_pending',
    ]);

    if (empty(session('user_auth')) && empty(session('user'))) {
        destroy_active_session();
    }
}

function enforce_user_session_timeout(): void
{
    if (session('user_auth') !== true || empty(session('user.id'))) {
        session()->forget('user_last_activity_at');
        return;
    }

    $now = time();
    $lastActivity = (int) session('user_last_activity_at', 0);

    if ($lastActivity > 0 && ($now - $lastActivity) >= user_session_idle_timeout_seconds()) {
        destroy_user_auth_session();
        session(['error' => auth_session_expired_message('user')]);
        return;
    }

    touch_user_session_activity($now);
}

function enforce_admin_session_timeout(): void
{
    if (session('admin_auth') !== true || empty(session('admin.id'))) {
        session()->forget('admin_last_activity_at');
        return;
    }

    $now = time();
    $lastActivity = (int) session('admin_last_activity_at', 0);

    if ($lastActivity > 0 && ($now - $lastActivity) >= admin_session_idle_timeout_seconds()) {
        destroy_admin_auth_session();
        session(['error' => auth_session_expired_message('admin')]);
        return;
    }

    touch_admin_session_activity($now);
}

function is_user_logged_in(): bool
{
    return session('user_auth') === true
        && is_array(session('user'))
        && !empty(session('user.id'));
}

function is_admin_logged_in(): bool
{
    return session('admin_auth') === true
        && is_array(session('admin'))
        && !empty(session('admin.id'));
}

function is_logged_in(): bool
{
    return is_user_logged_in() || is_admin_logged_in();
}

function current_user_data(): ?array
{
    $sessionUser = session('user');

    if (
        session('user_auth') !== true ||
        !is_array($sessionUser) ||
        empty($sessionUser['id'])
    ) {
        return is_array($sessionUser) ? $sessionUser : null;
    }

    static $cachedUser = null;
    static $cachedUserId = null;

    $userId = (int) $sessionUser['id'];

    if ($cachedUser !== null && $cachedUserId === $userId) {
        return $cachedUser;
    }

    $mergedUser = $sessionUser;

    try {
        $userModel = new \App\Repositories\UserRepository();
        $dbUser = $userModel->findById($userId);

        if (is_array($dbUser) && !empty($dbUser)) {
            $mergedUser = array_merge($sessionUser, [
                'username' => $dbUser['username'] ?? ($sessionUser['username'] ?? ''),
                'name' => $dbUser['name'] ?? ($sessionUser['name'] ?? ''),
                'nik' => $dbUser['nik'] ?? ($sessionUser['nik'] ?? ''),
                'gender' => $dbUser['gender'] ?? ($sessionUser['gender'] ?? null),
                'district_id' => $dbUser['district_id'] ?? ($sessionUser['district_id'] ?? null),
                'subdistrict_id' => $dbUser['subdistrict_id'] ?? ($sessionUser['subdistrict_id'] ?? null),
                'id_path' => $dbUser['id_path'] ?? ($sessionUser['id_path'] ?? ''),
                'pic_path' => $dbUser['pic_path'] ?? ($sessionUser['pic_path'] ?? ''),
                'status' => $dbUser['status'] ?? ($sessionUser['status'] ?? '')
            ]);

            session(['user' => $mergedUser]);
        }
    } catch (Throwable $e) {
        $mergedUser = $sessionUser;
    }

    $cachedUser = $mergedUser;
    $cachedUserId = $userId;

    return $mergedUser;
}

function resolve_user_display_name(?array $userData = null): string
{
    $userData = $userData ?? current_user_data() ?? [];

    $name = trim((string) ($userData['name'] ?? ''));
    if ($name !== '') {
        return $name;
    }

    $username = trim((string) ($userData['username'] ?? ''));
    if ($username !== '') {
        return $username;
    }

    return 'User SIGAP';
}

function resolve_user_default_profile_photo_path(?array $userData = null): string
{
    $userData = $userData ?? current_user_data() ?? [];
    $gender = strtoupper(trim((string) ($userData['gender'] ?? '')));

    if ($gender === 'L') {
        return 'images/profile/male.svg';
    }

    if ($gender === 'P') {
        return 'images/profile/female.svg';
    }

    return 'images/profile/default.svg';
}

function resolve_user_default_profile_photo_url(?array $userData = null): string
{
    return asset('assets/custom/' . resolve_user_default_profile_photo_path($userData));
}

function resolve_user_profile_photo_url(?array $userData = null): string
{
    $userData = $userData ?? current_user_data() ?? [];

    $defaultPhoto = resolve_user_default_profile_photo_url($userData);
    $picPath = trim(str_replace('\\', '/', (string) ($userData['pic_path'] ?? '')));

    if ($picPath === '') {
        return $defaultPhoto;
    }

    if (preg_match('#^https?://#i', $picPath)) {
        return $picPath;
    }

    $normalizedPath = ltrim($picPath, '/');
    $candidates = [];

    if (strpos($normalizedPath, 'assets/') === 0) {
        $candidates[] = $normalizedPath;
    }

    if (strpos($normalizedPath, 'images/') === 0) {
        $candidates[] = 'assets/custom/' . $normalizedPath;
    }

    if (strpos($normalizedPath, 'uploads/') === 0) {
        $candidates[] = 'assets/' . $normalizedPath;
    }

    $candidates[] = 'assets/uploads/' . $normalizedPath;
    $candidates[] = 'assets/custom/' . $normalizedPath;

    foreach ($candidates as $candidate) {
        if (legacy_first_existing_asset_path($candidate) !== null) {
            return asset($candidate);
        }
    }

    return $defaultPhoto;
}

function resolve_public_upload_url(?string $relativePath = null, string $defaultUrl = ''): string
{
    $relativePath = trim(str_replace('\\', '/', (string) $relativePath));

    if ($relativePath === '') {
        return $defaultUrl !== '' ? $defaultUrl : asset('assets/custom/images/backgrounds/profilebg.jpg');
    }

    if (preg_match('#^https?://#i', $relativePath)) {
        return $relativePath;
    }

    $normalizedPath = ltrim($relativePath, '/');
    $candidates = [];

    if (strpos($normalizedPath, 'assets/uploads/') === 0) {
        $candidates[] = $normalizedPath;
    }

    if (strpos($normalizedPath, 'uploads/') === 0) {
        $candidates[] = 'assets/' . $normalizedPath;
    }

    $candidates[] = 'assets/uploads/' . $normalizedPath;
    $candidates[] = 'assets/custom/' . $normalizedPath;

    foreach ($candidates as $candidate) {
        if (legacy_first_existing_asset_path($candidate) !== null) {
            return asset($candidate);
        }
    }

    return $defaultUrl !== '' ? $defaultUrl : asset('assets/custom/images/backgrounds/profilebg.jpg');
}

function resolve_user_rating_notifications(?array $userData = null): array
{
    $userData = $userData ?? current_user_data() ?? [];
    $userId = (int) ($userData['id'] ?? 0);

    if ($userId <= 0) {
        return [
            'pending_count' => 0,
            'items' => [],
        ];
    }

    static $cache = [];

    if (isset($cache[$userId])) {
        return $cache[$userId];
    }

    try {
        $ratingModel = new \App\Repositories\RatingRepository();
        $cache[$userId] = $ratingModel->getUserRatingNotifications($userId);
    } catch (Throwable $e) {
        $cache[$userId] = [
            'pending_count' => 0,
            'items' => [],
        ];
    }

    return $cache[$userId];
}

function user()
{
    return current_user_data();
}

function admin_user()
{
    return session('admin');
}

function resolve_admin_district_name(?array $adminData = null): string
{
    $adminData = $adminData ?? admin_user() ?? [];
    $districtId = (int) ($adminData['district_id'] ?? 0);

    if ($districtId <= 0) {
        return '';
    }

    static $districtMap = null;

    if ($districtMap === null) {
        $districtMap = [];

        try {
            $wilayahModel = new \App\Repositories\WilayahRepository();

            foreach ($wilayahModel->getDistricts() as $district) {
                $id = (int) ($district['id'] ?? 0);

                if ($id <= 0) {
                    continue;
                }

                $districtMap[$id] = trim((string) ($district['district'] ?? ''));
            }
        } catch (Throwable $e) {
            $districtMap = [];
        }
    }

    return trim((string) ($districtMap[$districtId] ?? ''));
}

function admin_menu_master_blueprint(): array
{
    return [
        [
            'key' => 'dashboard',
            'heading' => 'DASBOR',
            'items' => [
                [
                    'key' => 'dashboard.infografis',
                    'label' => 'Infografis',
                    'icon' => 'ti ti-chart-pie-3',
                    'href' => url('admin/dasbor'),
                    'is_ajax' => true,
                ],
                [
                    'key' => 'dashboard.kalender',
                    'label' => 'Kalender',
                    'icon' => 'ti ti-calendar',
                    'href' => url('admin/kalender'),
                    'is_ajax' => true,
                ],
            ],
        ],
        [
            'key' => 'data',
            'heading' => 'DATA',
            'items' => [
                [
                    'key' => 'data.riwayat',
                    'label' => 'Riwayat',
                    'icon' => 'ti ti-history',
                    'children' => [
                        [
                            'key' => 'data.riwayat.gedung',
                            'label' => 'Gedung',
                            'href' => url('admin/riwayat/gedung'),
                            'is_ajax' => true,
                        ],
                        [
                            'key' => 'data.riwayat.umkm',
                            'label' => 'UMKM',
                            'href' => url('admin/riwayat/umkm'),
                            'is_ajax' => true,
                        ],
                    ],
                ],
                [
                    'key' => 'data.reservasi',
                    'label' => 'Reservasi',
                    'icon' => 'ti ti-calendar-event',
                    'href' => url('admin/reservasi'),
                    'is_ajax' => true,
                ],
                [
                    'key' => 'data.verifikasi',
                    'label' => 'Verifikasi',
                    'icon' => 'ti ti-clipboard-check',
                    'href' => url('admin/verifikasi'),
                    'is_ajax' => true,
                ],
                [
                    'key' => 'data.pembayaran',
                    'label' => 'Pembayaran',
                    'icon' => 'ti ti-cash-banknote',
                    'href' => url('admin/pembayaran'),
                    'is_ajax' => true,
                ],
            ],
        ],
        [
            'key' => 'manajemen',
            'heading' => 'MANAJEMEN',
            'items' => [
                [
                    'key' => 'manajemen.gedung',
                    'label' => 'Gedung',
                    'icon' => 'ti ti-building-community',
                    'href' => 'javascript:void(0)',
                ],
                [
                    'key' => 'manajemen.umkm',
                    'label' => 'UMKM',
                    'icon' => 'ti ti-basket',
                    'href' => 'javascript:void(0)',
                ],
                [
                    'key' => 'manajemen.acara',
                    'label' => 'Acara',
                    'icon' => 'ti ti-ticket',
                    'href' => 'javascript:void(0)',
                ],
                [
                    'key' => 'manajemen.produk',
                    'label' => 'Produk',
                    'icon' => 'ti ti-package',
                    'href' => 'javascript:void(0)',
                ],
            ],
        ],
        [
            'key' => 'laporan',
            'heading' => 'LAPORAN',
            'items' => [
                [
                    'key' => 'laporan.pendapatan',
                    'label' => 'Pendapatan',
                    'icon' => 'ti ti-report-money',
                    'href' => 'javascript:void(0)',
                ],
                [
                    'key' => 'laporan.kerusakan',
                    'label' => 'Kerusakan',
                    'icon' => 'ti ti-alert-triangle',
                    'href' => 'javascript:void(0)',
                ],
                [
                    'key' => 'laporan.rating',
                    'label' => 'Rating',
                    'icon' => 'ti ti-star',
                    'href' => 'javascript:void(0)',
                    'children' => [
                        [
                            'key' => 'laporan.rating.gedung',
                            'label' => 'Gedung',
                            'href' => url('admin/laporan/rating/gedung'),
                            'is_ajax' => true,
                        ],
                        [
                            'key' => 'laporan.rating.umkm',
                            'label' => 'UMKM',
                            'href' => url('admin/laporan/rating/umkm'),
                            'is_ajax' => true,
                        ],
                    ],
                ],
            ],
        ],
        [
            'key' => 'pengaturan',
            'heading' => 'PENGATURAN',
            'items' => [
                [
                    'key' => 'pengaturan.hak-akses',
                    'label' => 'Hak Akses',
                    'icon' => 'ti ti-shield-lock',
                    'href' => url('admin/pengaturan/akses'),
                    'is_ajax' => true,
                ],
                [
                    'key' => 'pengaturan.user',
                    'label' => 'User',
                    'icon' => 'ti ti-users',
                    'href' => url('admin/pengaturan/user'),
                    'is_ajax' => true,
                ],
                [
                    'key' => 'pengaturan.admin',
                    'label' => 'Admin',
                    'icon' => 'ti ti-user-circle',
                    'href' => url('admin/pengaturan/admin'),
                    'is_ajax' => true,
                ],
            ],
        ],
    ];
}

function admin_menu_blueprint(): array
{
    static $blueprint = null;

    if ($blueprint !== null) {
        return $blueprint;
    }

    $masterBlueprint = admin_menu_master_blueprint();
    $defaultRoleAccessMap = [
        1 => admin_role_default_access_keys(1),
        2 => admin_role_default_access_keys(2),
        3 => admin_role_default_access_keys(3),
        4 => admin_role_default_access_keys(4),
    ];

    try {
        $menuModel = new \App\Repositories\MenuRepository();
        $blueprint = $menuModel->getHydratedBlueprint($masterBlueprint, $defaultRoleAccessMap);
    } catch (Throwable $e) {
        $blueprint = $masterBlueprint;
    }

    return $blueprint;
}

function admin_flatten_leaf_menu_items(array $sections): array
{
    $items = [];

    foreach ($sections as $section) {
        $heading = (string) ($section['heading'] ?? '');

        foreach ((array) ($section['items'] ?? []) as $item) {
            $itemLabel = (string) ($item['label'] ?? '');
            $children = (array) ($item['children'] ?? []);

            if (!empty($children)) {
                foreach ($children as $child) {
                    $grandChildren = (array) ($child['children'] ?? []);

                    if (!empty($grandChildren)) {
                        foreach ($grandChildren as $grandChild) {
                            $items[] = [
                                'key' => (string) ($grandChild['key'] ?? ''),
                                'menu_id' => (int) ($grandChild['menu_id'] ?? 0),
                                'heading' => $heading,
                                'parent_label' => (string) ($child['label'] ?? ''),
                                'label' => (string) ($grandChild['label'] ?? ''),
                                'path_label' => trim((string) ($child['label'] ?? '') . ' > ' . (string) ($grandChild['label'] ?? '')),
                                'href' => (string) ($grandChild['href'] ?? 'javascript:void(0)'),
                                'icon' => (string) ($grandChild['icon'] ?? 'ti ti-circle'),
                            ];
                        }
                    } else {
                        $items[] = [
                            'key' => (string) ($child['key'] ?? ''),
                            'menu_id' => (int) ($child['menu_id'] ?? 0),
                            'heading' => $heading,
                            'parent_label' => $itemLabel,
                            'label' => (string) ($child['label'] ?? ''),
                            'path_label' => trim($itemLabel . ' > ' . (string) ($child['label'] ?? '')),
                            'href' => (string) ($child['href'] ?? 'javascript:void(0)'),
                            'icon' => (string) ($child['icon'] ?? 'ti ti-circle'),
                        ];
                    }
                }
            } else {
                $items[] = [
                    'key' => (string) ($item['key'] ?? ''),
                    'menu_id' => (int) ($item['menu_id'] ?? 0),
                    'heading' => $heading,
                    'parent_label' => null,
                    'label' => (string) ($item['label'] ?? ''),
                    'path_label' => (string) ($item['label'] ?? ''),
                    'href' => (string) ($item['href'] ?? 'javascript:void(0)'),
                    'icon' => (string) ($item['icon'] ?? 'ti ti-layout-grid'),
                ];
            }
        }
    }

    return $items;
}

function admin_all_menu_access_keys(): array
{
    static $keys = null;

    if ($keys !== null) {
        return $keys;
    }

    $keys = array_values(array_unique(array_filter(array_map(function (array $item): string {
        return trim((string) ($item['key'] ?? ''));
    }, admin_flatten_leaf_menu_items(admin_menu_master_blueprint())))));

    return $keys;
}

function admin_role_default_access_keys(int $roleId): array
{
    $allKeys = admin_all_menu_access_keys();

    $map = [
        1 => $allKeys,
        2 => [
            'dashboard.infografis',
            'dashboard.kalender',
            'data.riwayat.gedung',
            'data.riwayat.umkm',
            'data.reservasi',
            'data.verifikasi',
            'data.pembayaran',
            'manajemen.gedung',
            'manajemen.umkm',
            'laporan.pendapatan',
            'laporan.kerusakan',
            'laporan.rating.gedung',
            'laporan.rating.umkm',
            'pengaturan.admin',
        ],
        3 => [
            'dashboard.infografis',
            'dashboard.kalender',
            'data.riwayat.gedung',
            'data.riwayat.umkm',
            'data.verifikasi',
            'laporan.kerusakan',
            'laporan.rating.gedung',
            'laporan.rating.umkm',
        ],
        4 => [
            'dashboard.infografis',
            'dashboard.kalender',
            'data.riwayat.gedung',
            'data.riwayat.umkm',
            'data.reservasi',
            'data.pembayaran',
            'laporan.kerusakan',
            'laporan.rating.gedung',
            'laporan.rating.umkm',
        ],
    ];

    return array_values(array_unique($map[$roleId] ?? []));
}

function admin_role_access_keys(int $roleId): array
{
    static $cache = [];

    if (isset($cache[$roleId])) {
        return $cache[$roleId];
    }

    $fallbackKeys = admin_role_default_access_keys($roleId);

    try {
        $menuModel = new \App\Repositories\MenuRepository();
        $blueprint = admin_menu_blueprint();
        $leafItems = admin_flatten_leaf_menu_items($blueprint);
        $menuIdToKeyMap = [];

        foreach ($leafItems as $item) {
            $menuId = (int) ($item['menu_id'] ?? 0);
            $itemKey = trim((string) ($item['key'] ?? ''));

            if ($menuId > 0 && $itemKey !== '') {
                $menuIdToKeyMap[$menuId] = $itemKey;
            }
        }

        $hasRows = $menuModel->hasRoleAccessRows($roleId);
        $allowedMenuIds = $menuModel->getRoleAccessMenuIds($roleId);
        $resolvedKeys = [];

        foreach ($allowedMenuIds as $menuId) {
            if (isset($menuIdToKeyMap[$menuId])) {
                $resolvedKeys[] = $menuIdToKeyMap[$menuId];
            }
        }

        if (!$hasRows) {
            $resolvedKeys = $fallbackKeys;
        }

        $cache[$roleId] = array_values(array_unique($resolvedKeys));
    } catch (Throwable $e) {
        $cache[$roleId] = $fallbackKeys;
    }

    return $cache[$roleId];
}

function admin_filter_menu_items_by_access(array $items, array $allowedKeys): array
{
    $filtered = [];
    $allowedLookup = array_fill_keys($allowedKeys, true);

    foreach ($items as $item) {
        $itemKey = trim((string) ($item['key'] ?? ''));
        $children = $item['children'] ?? [];
        $filteredChildren = [];

        if (is_array($children) && !empty($children)) {
            $filteredChildren = admin_filter_menu_items_by_access($children, $allowedKeys);
        }

        $isAllowed = $itemKey !== '' && isset($allowedLookup[$itemKey]);

        if (!$isAllowed && empty($filteredChildren)) {
            continue;
        }

        $newItem = $item;

        if (!empty($filteredChildren)) {
            $newItem['children'] = $filteredChildren;
        } else {
            unset($newItem['children']);
        }

        $filtered[] = $newItem;
    }

    return $filtered;
}

function admin_build_sidebar_sections_for_role(int $roleId): array
{
    $allowedKeys = admin_role_access_keys($roleId);
    $sections = [];

    foreach (admin_menu_blueprint() as $section) {
        $filteredItems = admin_filter_menu_items_by_access($section['items'] ?? [], $allowedKeys);

        if (empty($filteredItems)) {
            continue;
        }

        $sections[] = [
            'key' => $section['key'] ?? '',
            'heading' => $section['heading'] ?? 'MENU',
            'items' => $filteredItems,
        ];
    }

    return $sections;
}

function admin_roles_catalog(): array
{
    static $roles = null;

    if ($roles !== null) {
        return $roles;
    }

    $roles = [
        1 => [
            'label' => 'Super Admin',
            'scope' => 'Seluruh Sistem',
            'description' => 'Memiliki akses penuh ke seluruh sistem',
        ],
        2 => [
            'label' => 'Admin',
            'scope' => 'Seluruh Sistem',
            'description' => 'Mengelola user dan data',
        ],
        3 => [
            'label' => 'Verifikator',
            'scope' => 'Kecamatan Login',
            'description' => 'Melakukan verifikasi dan pengesahan data',
        ],
        4 => [
            'label' => 'Operator',
            'scope' => 'Kecamatan Login',
            'description' => 'Mengecek dan menginput data',
        ],
    ];

    try {
        $menuModel = new \App\Repositories\MenuRepository();
        foreach ($menuModel->getRoles() as $role) {
            $roleId = (int) ($role['id'] ?? 0);
            if ($roleId <= 0) {
                continue;
            }

            $roles[$roleId] = [
                'label' => trim((string) ($role['role_name'] ?? ($roles[$roleId]['label'] ?? 'Role'))),
                'scope' => $roles[$roleId]['scope'] ?? 'Seluruh Sistem',
                'description' => trim((string) ($role['description'] ?? ($roles[$roleId]['description'] ?? ''))),
            ];
        }
    } catch (Throwable $e) {
        // gunakan fallback hardcoded
    }

    ksort($roles);

    return $roles;
}

function resolve_admin_access_overview(): array
{
    $roles = admin_roles_catalog();
    $overview = [];

    foreach ($roles as $roleId => $role) {
        $overview[] = [
            'role_id' => $roleId,
            'role_label' => $role['label'],
            'scope_label' => $role['scope'],
            'description' => $role['description'] ?? '',
            'allowed_keys' => admin_role_access_keys($roleId),
            'sidebar_sections' => admin_build_sidebar_sections_for_role($roleId),
        ];
    }

    return $overview;
}

function admin_has_menu_access(string $menuKey, ?array $adminData = null): bool
{
    $adminData = $adminData ?? admin_user() ?? [];
    $roleId = (int) ($adminData['role_id'] ?? 0);
    $allowedKeys = admin_role_access_keys($roleId);

    return in_array($menuKey, $allowedKeys, true);
}

function admin_extract_relative_menu_path(string $href): string
{
    $href = trim($href);

    if ($href === '' || stripos($href, 'javascript:') === 0 || $href === '#') {
        return '';
    }

    $base = url('/');

    if (strpos($href, $base . '/') === 0) {
        $href = substr($href, strlen($base));
    } elseif (strpos($href, $base) === 0) {
        $href = substr($href, strlen($base));
    }

    return '/' . trim((string) preg_replace('#\?.*$#', '', ltrim($href, '/')), '/');
}

function resolve_admin_page_meta(?string $currentPath = null): array
{
    $leafItems = admin_flatten_leaf_menu_items(admin_menu_blueprint());
    $normalizedPath = $currentPath;

    if ($normalizedPath === null) {
        $normalizedPath = admin_extract_relative_menu_path('/' . trim(request()->path(), '/'));
    }

    $normalizedPath = $normalizedPath !== '' ? '/' . trim((string) $normalizedPath, '/') : '/admin/dasbor';

    $defaultMeta = [
        'key' => 'dashboard.infografis',
        'title' => 'Infografis',
        'breadcrumbs' => [
            [
                'label' => 'Infografis',
                'href' => url('admin/dasbor'),
            ],
            [
                'label' => 'Infografis',
                'href' => null,
            ],
        ],
    ];

    foreach ($leafItems as $item) {
        $itemPath = admin_extract_relative_menu_path((string) ($item['href'] ?? ''));

        if ($itemPath === '' || $itemPath !== $normalizedPath) {
            continue;
        }

        $sectionLabel = ucwords(strtolower((string) ($item['heading'] ?? 'Menu')));
        $sectionHref = url('admin/dasbor');

        foreach ($leafItems as $candidate) {
            if ((string) ($candidate['heading'] ?? '') !== (string) ($item['heading'] ?? '')) {
                continue;
            }

            $candidatePath = admin_extract_relative_menu_path((string) ($candidate['href'] ?? ''));
            if ($candidatePath !== '') {
                $sectionHref = url(ltrim($candidatePath, '/'));
                break;
            }
        }

        $breadcrumbs = [
            [
                'label' => $sectionLabel,
                'href' => $sectionHref,
            ],
        ];

        $parentLabel = trim((string) ($item['parent_label'] ?? ''));
        if ($parentLabel !== '') {
            $breadcrumbs[] = [
                'label' => $parentLabel,
                'href' => null,
            ];
        }

        $breadcrumbs[] = [
            'label' => (string) ($item['label'] ?? 'Menu'),
            'href' => null,
        ];

        return [
            'key' => (string) ($item['key'] ?? ''),
            'title' => (string) ($item['label'] ?? 'Menu'),
            'breadcrumbs' => $breadcrumbs,
        ];
    }

    return $defaultMeta;
}

function resolve_admin_role_context(?array $adminData = null): array
{
    $adminData = $adminData ?? admin_user() ?? [];
    $roleId = (int) ($adminData['role_id'] ?? 0);
    $districtName = resolve_admin_district_name($adminData);
    $scopeLabelDistrict = $districtName !== '' ? 'Kecamatan ' . $districtName : 'Kecamatan Login';

    $roleContext = [
        'role_id' => $roleId,
        'role_label' => 'Admin',
        'role_description' => 'Mengelola data utama SIGAP.',
        'scope_type' => 'all',
        'scope_label' => 'Seluruh Sistem',
        'district_name' => $districtName,
        'dashboard_title' => 'Infografis',
        'dashboard_summary' => 'Pantau informasi utama dan menu kerja sesuai peran admin yang sedang login.',
        'focus_items' => [
            'Mengakses menu kerja yang sesuai dengan peran admin.',
            'Memantau status reservasi dan proses operasional utama.',
            'Menjaga pengelolaan data tetap terarah sesuai hak akses.'
        ],
        'sidebar_sections' => admin_build_sidebar_sections_for_role($roleId),
        'allowed_menu_keys' => admin_role_access_keys($roleId),
        'menu_blueprint' => admin_menu_blueprint(),
    ];

    switch ($roleId) {
        case 1:
            $roleContext['role_label'] = 'Super Admin';
            $roleContext['role_description'] = 'Memiliki akses penuh ke seluruh sistem SIGAP.';
            $roleContext['dashboard_title'] = 'Infografis';
            $roleContext['dashboard_summary'] = 'Memantau seluruh data reservasi, kalender, laporan, manajemen master, dan pengaturan hak akses aplikasi SIGAP.';
            $roleContext['focus_items'] = [
                'Mengatur menu master yang menjadi sumber hak akses seluruh role admin.',
                'Memantau seluruh data reservasi, verifikasi, pembayaran, dan riwayat lintas wilayah.',
                'Mengelola pengaturan sistem seperti hak akses, peran, user, dan admin.'
            ];
            break;

        case 2:
            $roleContext['role_label'] = 'Admin';
            $roleContext['role_description'] = 'Mengelola operasional utama SIGAP secara menyeluruh.';
            $roleContext['dashboard_title'] = 'Infografis';
            $roleContext['dashboard_summary'] = 'Melihat seluruh data operasional dan menu yang diizinkan oleh Super Admin untuk peran Admin.';
            $roleContext['focus_items'] = [
                'Mengakses data operasional seluruh sistem sesuai menu yang diberikan oleh Super Admin.',
                'Mengelola proses kerja lintas modul tanpa mengubah hak akses inti sistem.',
                'Menindaklanjuti laporan dan data master yang menjadi tanggung jawab admin.'
            ];
            break;

        case 3:
            $roleContext['role_label'] = 'Verifikator';
            $roleContext['role_description'] = 'Fokus pada proses verifikasi di kecamatan login.';
            $roleContext['scope_type'] = 'district';
            $roleContext['scope_label'] = $scopeLabelDistrict;
            $roleContext['dashboard_title'] = 'Infografis';
            $roleContext['dashboard_summary'] = 'Memantau data dan menu verifikasi yang diberikan oleh Super Admin untuk kecamatan tempat verifikator bertugas.';
            $roleContext['focus_items'] = [
                'Memverifikasi data sesuai cakupan kecamatan login.',
                'Melihat riwayat modul yang diizinkan pada wilayah tugas.',
                'Menangani laporan kerusakan yang menjadi bagian akses verifikator.'
            ];
            break;

        case 4:
            $roleContext['role_label'] = 'Operator';
            $roleContext['role_description'] = 'Fokus pada input dan operasional data kecamatan login.';
            $roleContext['scope_type'] = 'district';
            $roleContext['scope_label'] = $scopeLabelDistrict;
            $roleContext['dashboard_title'] = 'Infografis';
            $roleContext['dashboard_summary'] = 'Mengelola data operasional sesuai menu yang diberikan oleh Super Admin pada kecamatan tempat operator bertugas.';
            $roleContext['focus_items'] = [
                'Mengakses menu operator seperti contoh hak akses yang diberikan oleh Super Admin.',
                'Menginput data reservasi, pembayaran, dan laporan dalam cakupan kecamatan login.',
                'Melihat riwayat gedung dan UMKM sesuai menu yang diizinkan.'
            ];
            break;
    }

    return $roleContext;
}

function require_login(): void
{
    if (!is_user_logged_in()) {
        throw new \Illuminate\Http\Exceptions\HttpResponseException(redirect('login'));
    }
}

function require_admin(): void
{
    if (!is_admin_logged_in()) {
        throw new \Illuminate\Http\Exceptions\HttpResponseException(redirect('admin/login'));
    }
}

function require_admin_menu_access(string $menuKey, string $fallbackPath = 'admin/dasbor'): void
{
    require_admin();

    if (!admin_has_menu_access($menuKey)) {
        throw new \Illuminate\Http\Exceptions\HttpResponseException(redirect($fallbackPath));
    }
}

function clear_invalid_auth_session(): void
{
    $user = session('user');
    if ($user !== null && (!is_array($user) || empty($user['id']))) {
        session()->forget([
            'user',
            'user_auth',
            'user_last_activity_at',
            'user_browser_session_bootstrap_pending',
        ]);
    }

    if (session('user_auth') !== true) {
        session()->forget(['user_last_activity_at', 'user_browser_session_bootstrap_pending']);
    }

    $admin = session('admin');
    if ($admin !== null && (!is_array($admin) || empty($admin['id']))) {
        session()->forget([
            'admin',
            'admin_auth',
            'admin_last_activity_at',
            'admin_browser_session_bootstrap_pending',
        ]);
    }

    if (session('admin_auth') !== true) {
        session()->forget(['admin_last_activity_at', 'admin_browser_session_bootstrap_pending']);
    }
}

function auth_browser_session_storage_key(string $scope = 'user'): string
{
    return strtolower(trim($scope)) === 'admin'
        ? 'sigap.admin.browser-session'
        : 'sigap.user.browser-session';
}

function consume_auth_browser_session_guard_mode(string $scope = 'user'): string
{
    $normalizedScope = strtolower(trim($scope)) === 'admin' ? 'admin' : 'user';
    $authKey = $normalizedScope . '_auth';
    $payloadKey = $normalizedScope;
    $bootstrapKey = $normalizedScope . '_browser_session_bootstrap_pending';
    $payload = session($payloadKey);

    if (
        session($authKey) !== true ||
        empty($payload) ||
        !is_array($payload) ||
        empty($payload['id'])
    ) {
        session()->forget($bootstrapKey);
        return 'none';
    }

    if (!empty(session($bootstrapKey))) {
        session()->forget($bootstrapKey);
        return 'bootstrap';
    }

    return 'enforce';
}
