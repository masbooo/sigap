<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

class DashboardController extends Controller
{
    public function index()
    {
        require_admin();

        $admin = admin_user() ?? [];
        $roleContext = resolve_admin_role_context($admin);
        $districtId = (int) ($admin['district_id'] ?? 0);
        $districtName = (string) ($roleContext['district_name'] ?? '');

        $userModel = $this->model('User');
        $reservasiModel = $this->model('Reservasi');
        $gedungModel = $this->model('Gedung');
        $umkmModel = $this->model('Umkm');

        $reservations = $reservasiModel->all();
        $buildings = $gedungModel->getAllActive();
        $umkmItems = $umkmModel->getPageData()['items'] ?? [];
        $activeUsers = $userModel->getActiveUsers();

        if (($roleContext['scope_type'] ?? 'all') === 'district' && $districtId > 0) {
            $reservations = $this->filterReservationsByDistrict($reservations, $districtId);
            $buildings = $this->filterBuildingsByDistrict($buildings, $districtId);
            $umkmItems = $this->filterUmkmByDistrict($umkmItems, $districtName);
            $activeUsers = $this->filterUsersByDistrict($activeUsers, $districtId);
        }

        $reservationStats = [
            'total' => count($reservations),
            'proses' => $this->countReservationsByStatuses($reservations, [
                'RESERVASI BARU',
                'BERKAS RESERVASI TIDAK SESUAI',
                'KERJASAMA UMKM',
                'PROSES VERIFIKASI',
                'BERKAS VERIFIKASI TIDAK SESUAI',
                'MENUNGGU PEMBAYARAN',
                'CEK PEMBAYARAN',
                'BERKAS PEMBAYARAN TIDAK SESUAI',
            ]),
            'selesai' => $this->countReservationsByStatuses($reservations, ['PEMBAYARAN LUNAS', 'ACARA SELESAI']),
            'batal' => $this->countReservationsByStatuses($reservations, ['PERMOHONAN DITOLAK', 'DIBATALKAN PEMOHON']),
        ];

        $dashboardCards = [
            [
                'label' => 'Reservasi',
                'value' => count($reservations),
                'description' => ($roleContext['scope_type'] ?? 'all') === 'district'
                    ? 'Data reservasi pada kecamatan login'
                    : 'Data reservasi seluruh sistem',
                'icon' => 'ti ti-calendar-event',
                'tone' => 'primary',
            ],
            [
                'label' => 'Gedung',
                'value' => count($buildings),
                'description' => ($roleContext['scope_type'] ?? 'all') === 'district'
                    ? 'Gedung aktif pada kecamatan login'
                    : 'Gedung aktif seluruh sistem',
                'icon' => 'ti ti-building-community',
                'tone' => 'success',
            ],
            [
                'label' => 'UMKM',
                'value' => count($umkmItems),
                'description' => ($roleContext['scope_type'] ?? 'all') === 'district'
                    ? 'UMKM yang terhubung ke kecamatan login'
                    : 'UMKM aktif seluruh sistem',
                'icon' => 'ti ti-building-store',
                'tone' => 'warning',
            ],
            [
                'label' => 'User',
                'value' => count($activeUsers),
                'description' => ($roleContext['scope_type'] ?? 'all') === 'district'
                    ? 'User aktif pada kecamatan login'
                    : 'Akun user aktif seluruh sistem',
                'icon' => 'ti ti-users',
                'tone' => 'info',
            ],
            [
                'label' => 'Reservasi',
                'value' => count($reservations),
                'description' => ($roleContext['scope_type'] ?? 'all') === 'district'
                    ? 'Data reservasi pada kecamatan login'
                    : 'Data reservasi seluruh sistem',
                'icon' => 'ti ti-calendar-event',
                'tone' => 'primary',
            ],
            [
                'label' => 'Reservasi',
                'value' => count($reservations),
                'description' => ($roleContext['scope_type'] ?? 'all') === 'district'
                    ? 'Data reservasi pada kecamatan login'
                    : 'Data reservasi seluruh sistem',
                'icon' => 'ti ti-calendar-event',
                'tone' => 'primary',
            ],
        ];

        $this->view('admin.index', [
            'title' => ($roleContext['dashboard_title'] ?? 'Infografis Admin') . ' - SIGAP',
            'admin' => $admin,
            'roleContext' => $roleContext,
            'dashboardCards' => $dashboardCards,
            'reservationStats' => $reservationStats,
        ]);
    }

    public function calendar()
    {
        require_admin_menu_access('dashboard.kalender');

        $admin = admin_user() ?? [];

        $this->view('admin.kalender.index', [
            'title' => 'Kalender - SIGAP',
            'admin' => $admin,
            'roleContext' => resolve_admin_role_context($admin),
        ]);
    }

    public function accessSettings()
    {
        require_admin_menu_access('pengaturan.hak-akses');

        $admin = admin_user() ?? [];
        $menuModel = $this->model('Menu');
        $roleContext = resolve_admin_role_context($admin);
        $menuBlueprint = admin_menu_blueprint();
        $leafMenuItems = admin_flatten_leaf_menu_items($menuBlueprint);
        $roles = array_values(array_filter($menuModel->getRoles(), function (array $role): bool {
            return (int) ($role['id'] ?? 0) > 0;
        }));
        $accessSelections = [];

        foreach ($roles as $role) {
            $roleId = (int) ($role['id'] ?? 0);
            $accessSelections[$roleId] = admin_role_access_keys($roleId);
        }

        csrf_token();
        $success = session()->pull('success', '');
        $error = session()->pull('error', '');

        $this->view('admin.pengaturan.akses', [
            'title' => 'Hak Akses Admin - SIGAP',
            'admin' => $admin,
            'roleContext' => $roleContext,
            'accessOverview' => resolve_admin_access_overview(),
            'menuBlueprint' => $menuBlueprint,
            'leafMenuItems' => $leafMenuItems,
            'roles' => $roles,
            'accessSelections' => $accessSelections,
            'canManageAccess' => (int) ($admin['role_id'] ?? 0) === 1,
            'messages' => [
                'success' => $success,
                'error' => $error,
            ],
        ]);
    }

    public function saveAccessSettings()
    {
        require_admin_menu_access('pengaturan.hak-akses');
        verify_csrf();

        $admin = admin_user() ?? [];
        if ((int) ($admin['role_id'] ?? 0) !== 1) {
            session(['error' => 'Hanya Super Admin yang dapat mengubah hak akses menu']);
            $this->redirect('/admin/pengaturan/akses');
            return;
        }

        $menuModel = $this->model('Menu');
        $menuBlueprint = admin_menu_blueprint();
        $leafMenuItems = admin_flatten_leaf_menu_items($menuBlueprint);
        $validMenuIds = array_values(array_filter(array_map(function (array $item): int {
            return (int) ($item['menu_id'] ?? 0);
        }, $leafMenuItems)));
        $validMenuLookup = array_fill_keys($validMenuIds, true);

        foreach ($menuModel->getRoles() as $role) {
            $roleId = (int) ($role['id'] ?? 0);
            if ($roleId <= 1) {
                continue;
            }

            $selectedMenuIds = array_map('intval', (array) request("access.{$roleId}", []));
            $selectedMenuIds = array_values(array_unique(array_filter($selectedMenuIds, function (int $menuId) use ($validMenuLookup): bool {
                return isset($validMenuLookup[$menuId]);
            })));

            $menuModel->saveRoleAccessMenuIds($roleId, $selectedMenuIds, $validMenuIds);
        }

        session(['success' => 'Hak akses menu berhasil diperbarui']);
        $this->redirect('/admin/pengaturan/akses');
    }

    private function filterReservationsByDistrict(array $reservations, int $districtId): array
    {
        return array_values(array_filter($reservations, function (array $reservation) use ($districtId): bool {
            return (int) ($reservation['district_id'] ?? 0) === $districtId;
        }));
    }

    private function filterBuildingsByDistrict(array $buildings, int $districtId): array
    {
        return array_values(array_filter($buildings, function (array $building) use ($districtId): bool {
            return (int) ($building['district_id'] ?? 0) === $districtId;
        }));
    }

    private function filterUmkmByDistrict(array $umkmItems, string $districtName): array
    {
        if (trim($districtName) === '') {
            return [];
        }

        return array_values(array_filter($umkmItems, function (array $item) use ($districtName): bool {
            $districts = $item['gsg_districts'] ?? [];

            return in_array($districtName, $districts, true);
        }));
    }

    private function filterUsersByDistrict(array $users, int $districtId): array
    {
        return array_values(array_filter($users, function (array $user) use ($districtId): bool {
            return (int) ($user['district_id'] ?? 0) === $districtId;
        }));
    }

    private function countReservationsByStatuses(array $reservations, array $statuses): int
    {
        return count(array_filter($reservations, function (array $reservation) use ($statuses): bool {
            return reservation_status_matches($reservation['status'] ?? '', $statuses);
        }));
    }
}
