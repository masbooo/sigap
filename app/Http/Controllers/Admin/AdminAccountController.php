<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

class AdminAccountController extends Controller
{
    private const MANAGED_ROLE_IDS = [2, 3];
    private const DISTRICT_SCOPED_ROLE_IDS = [3];
    private const STATUSES = ['AKTIF', 'TIDAK AKTIF'];

    public function index()
    {
        require_admin_menu_access('pengaturan.admin');

        $admin = admin_user() ?? [];
        $adminModel = $this->model('Admin');
        $menuModel = $this->model('Menu');
        $wilayahModel = $this->model('Wilayah');
        $roles = $this->managedRoles($menuModel->getRoles());

        csrf_token();
        $success = session()->pull('success', '');
        $error = session()->pull('error', '');

        $this->view('admin.pengaturan.admin', [
            'title' => 'Admin - SIGAP',
            'admin' => $admin,
            'roleContext' => resolve_admin_role_context($admin),
            'adminAccounts' => $adminModel->getAccountsByRoleIds(self::MANAGED_ROLE_IDS),
            'roles' => $roles,
            'districts' => $wilayahModel->getDistricts(),
            'messages' => [
                'success' => $success,
                'error' => $error,
            ],
            'statuses' => self::STATUSES,
            'districtScopedRoleIds' => self::DISTRICT_SCOPED_ROLE_IDS,
        ]);
    }

    public function store()
    {
        require_admin_menu_access('pengaturan.admin');
        verify_csrf();

        $payload = $this->validatePayload(true);

        if ($payload !== null) {
            $this->storeAdminAccount($payload);
        }

        $this->redirect('/admin/pengaturan/admin');
    }

    public function update()
    {
        require_admin_menu_access('pengaturan.admin');
        verify_csrf();

        $id = (int) request('id', 0);
        if ($id <= 0) {
            session(['error' => 'Data akun admin tidak valid']);
            $this->redirect('/admin/pengaturan/admin');
            return;
        }

        $payload = $this->validatePayload(false);

        if ($payload !== null) {
            $this->updateAdminAccount($id, $payload);
        }

        $this->redirect('/admin/pengaturan/admin');
    }

    public function destroy()
    {
        require_admin_menu_access('pengaturan.admin');
        verify_csrf();

        $id = (int) request('id', 0);
        if ($id <= 0) {
            session(['error' => 'Data akun admin tidak valid']);
            $this->redirect('/admin/pengaturan/admin');
            return;
        }

        $this->deleteAdminAccount($id);
        $this->redirect('/admin/pengaturan/admin');
    }

    private function storeAdminAccount(array $payload): void
    {
        $adminModel = $this->model('Admin');

        if ($adminModel->findByUsername($payload['username'])) {
            session(['error' => 'Username admin sudah digunakan']);
            return;
        }

        $created = $adminModel->createAccount($payload);
        session([$created ? 'success' : 'error' => $created
            ? 'Akun admin berhasil ditambahkan'
            : 'Gagal menambahkan akun admin']);
    }

    private function updateAdminAccount(int $id, array $payload): void
    {
        $adminModel = $this->model('Admin');
        $currentAdmin = admin_user() ?? [];

        if (!$adminModel->findAccountInRoleIds($id, self::MANAGED_ROLE_IDS)) {
            session(['error' => 'Akun admin tidak ditemukan pada menu Admin']);
            return;
        }

        if ($adminModel->usernameExistsForOther($payload['username'], $id)) {
            session(['error' => 'Username admin sudah digunakan']);
            return;
        }

        if ((int) ($currentAdmin['id'] ?? 0) === $id && $payload['status'] !== 'AKTIF') {
            session(['error' => 'Admin yang sedang login tidak dapat menonaktifkan akunnya sendiri']);
            return;
        }

        $updated = $adminModel->updateAccount($id, $payload);
        session([$updated ? 'success' : 'error' => $updated
            ? 'Akun admin berhasil diperbarui'
            : 'Gagal memperbarui akun admin']);
    }

    private function deleteAdminAccount(int $id): void
    {
        $adminModel = $this->model('Admin');
        $currentAdmin = admin_user() ?? [];

        if (!$adminModel->findAccountInRoleIds($id, self::MANAGED_ROLE_IDS)) {
            session(['error' => 'Akun admin tidak ditemukan pada menu Admin']);
            return;
        }

        if ((int) ($currentAdmin['id'] ?? 0) === $id) {
            session(['error' => 'Admin yang sedang login tidak dapat menghapus akunnya sendiri']);
            return;
        }

        $deleted = $adminModel->deleteAccount($id);
        session([$deleted ? 'success' : 'error' => $deleted
            ? 'Akun admin berhasil dihapus'
            : 'Gagal menghapus akun admin']);
    }

    private function validatePayload(bool $isCreate): ?array
    {
        $roleId = (int) request('role_id', 0);
        $name = trim((string) request('name', ''));
        $username = trim((string) request('username', ''));
        $password = (string) request('password', '');
        $districtId = (int) request('district_id', 0);
        $status = strtoupper(trim((string) request('status', 'AKTIF')));
        $requiresDistrict = in_array($roleId, self::DISTRICT_SCOPED_ROLE_IDS, true);

        if (!in_array($roleId, self::MANAGED_ROLE_IDS, true)) {
            session(['error' => 'Role admin tidak valid']);
            return null;
        }

        if ($name === '' || $username === '') {
            session(['error' => 'Nama dan username wajib diisi']);
            return null;
        }

        if (!preg_match('/^[A-Za-z0-9_]{3,50}$/', $username)) {
            session(['error' => 'Username minimal 3 karakter dan hanya boleh berisi huruf, angka, atau underscore']);
            return null;
        }

        if ($isCreate && strlen($password) < 6) {
            session(['error' => 'Password minimal 6 karakter']);
            return null;
        }

        if (!$isCreate && $password !== '' && strlen($password) < 6) {
            session(['error' => 'Password minimal 6 karakter']);
            return null;
        }

        if (!in_array($status, self::STATUSES, true)) {
            session(['error' => 'Status akun admin tidak valid']);
            return null;
        }

        if ($requiresDistrict && $districtId <= 0) {
            session(['error' => 'Kecamatan wajib dipilih untuk role ini']);
            return null;
        }

        return [
            'role_id' => $roleId,
            'name' => $name,
            'username' => $username,
            'password' => $password,
            'district_id' => $requiresDistrict ? $districtId : null,
            'status' => $status,
        ];
    }

    private function managedRoles(array $roles): array
    {
        $managed = [];

        foreach ($roles as $role) {
            $roleId = (int) ($role['id'] ?? 0);

            if (!in_array($roleId, self::MANAGED_ROLE_IDS, true)) {
                continue;
            }

            $managed[$roleId] = $role;
        }

        ksort($managed);

        return $managed;
    }
}
