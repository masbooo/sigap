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
        $success = $_SESSION['success'] ?? '';
        $error = $_SESSION['error'] ?? '';
        unset($_SESSION['success'], $_SESSION['error']);

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
            $this->storeAccount($payload);
        }

        $this->redirect('/admin/pengaturan/admin');
    }

    public function update()
    {
        require_admin_menu_access('pengaturan.admin');
        verify_csrf();

        $id = (int) ($_POST['id'] ?? 0);
        if ($id <= 0) {
            $_SESSION['error'] = 'Data akun admin tidak valid';
            $this->redirect('/admin/pengaturan/admin');
            return;
        }

        $payload = $this->validatePayload(false);

        if ($payload !== null) {
            $this->updateAccount($id, $payload);
        }

        $this->redirect('/admin/pengaturan/admin');
    }

    public function destroy()
    {
        require_admin_menu_access('pengaturan.admin');
        verify_csrf();

        $id = (int) ($_POST['id'] ?? 0);
        if ($id <= 0) {
            $_SESSION['error'] = 'Data akun admin tidak valid';
            $this->redirect('/admin/pengaturan/admin');
            return;
        }

        $this->destroyAccount($id);
        $this->redirect('/admin/pengaturan/admin');
    }

    private function storeAccount(array $payload): void
    {
        $adminModel = $this->model('Admin');

        if ($adminModel->usernameExistsForOther($payload['username'])) {
            $_SESSION['error'] = 'Username admin sudah digunakan';
            return;
        }

        $created = $adminModel->createManagedAccount([
            'username' => $payload['username'],
            'password' => password_hash($payload['password'], PASSWORD_BCRYPT),
            'name' => $payload['name'],
            'role_id' => $payload['role_id'],
            'district_id' => $payload['district_id'],
            'status' => $payload['status'],
        ]);

        $_SESSION[$created ? 'success' : 'error'] = $created
            ? 'Akun admin berhasil ditambahkan'
            : 'Gagal menambahkan akun admin';
    }

    private function updateAccount(int $id, array $payload): void
    {
        $adminModel = $this->model('Admin');
        $account = $adminModel->findById($id);
        $currentAdmin = admin_user() ?? [];
        $currentAdminId = (int) ($currentAdmin['id'] ?? 0);

        if (!$account || !in_array((int) ($account['role_id'] ?? 0), self::MANAGED_ROLE_IDS, true)) {
            $_SESSION['error'] = 'Akun admin tidak ditemukan pada menu Admin';
            return;
        }

        if ($adminModel->usernameExistsForOther($payload['username'], $id)) {
            $_SESSION['error'] = 'Username admin sudah digunakan';
            return;
        }

        if ($id === $currentAdminId && $payload['status'] !== 'AKTIF') {
            $_SESSION['error'] = 'Admin yang sedang login tidak dapat menonaktifkan akunnya sendiri';
            return;
        }

        $updated = $adminModel->updateManagedAccount($id, [
            'username' => $payload['username'],
            'password' => $payload['password'] !== '' ? password_hash($payload['password'], PASSWORD_BCRYPT) : '',
            'name' => $payload['name'],
            'role_id' => $payload['role_id'],
            'district_id' => $payload['district_id'],
            'status' => $payload['status'],
        ]);

        $_SESSION[$updated ? 'success' : 'error'] = $updated
            ? 'Akun admin berhasil diperbarui'
            : 'Gagal memperbarui akun admin';
    }

    private function destroyAccount(int $id): void
    {
        $adminModel = $this->model('Admin');
        $account = $adminModel->findById($id);
        $currentAdmin = admin_user() ?? [];
        $currentAdminId = (int) ($currentAdmin['id'] ?? 0);

        if (!$account || !in_array((int) ($account['role_id'] ?? 0), self::MANAGED_ROLE_IDS, true)) {
            $_SESSION['error'] = 'Akun admin tidak ditemukan pada menu Admin';
            return;
        }

        if ($id === $currentAdminId) {
            $_SESSION['error'] = 'Admin yang sedang login tidak dapat menghapus akunnya sendiri';
            return;
        }

        $deleted = $adminModel->deleteAccount($id);
        $_SESSION[$deleted ? 'success' : 'error'] = $deleted
            ? 'Akun admin berhasil dihapus'
            : 'Gagal menghapus akun admin';
    }

    private function validatePayload(bool $isCreate): ?array
    {
        $roleId = (int) ($_POST['role_id'] ?? 0);
        $name = trim((string) ($_POST['name'] ?? ''));
        $username = trim((string) ($_POST['username'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $districtId = (int) ($_POST['district_id'] ?? 0);
        $status = strtoupper(trim((string) ($_POST['status'] ?? 'AKTIF')));
        $requiresDistrict = in_array($roleId, self::DISTRICT_SCOPED_ROLE_IDS, true);

        if (!in_array($roleId, self::MANAGED_ROLE_IDS, true)) {
            $_SESSION['error'] = 'Role admin tidak valid';
            return null;
        }

        if ($name === '' || $username === '') {
            $_SESSION['error'] = 'Nama dan username wajib diisi';
            return null;
        }

        if (!preg_match('/^[A-Za-z0-9_]{3,50}$/', $username)) {
            $_SESSION['error'] = 'Username minimal 3 karakter dan hanya boleh berisi huruf, angka, atau underscore';
            return null;
        }

        if ($isCreate && strlen($password) < 6) {
            $_SESSION['error'] = 'Password minimal 6 karakter';
            return null;
        }

        if (!$isCreate && $password !== '' && strlen($password) < 6) {
            $_SESSION['error'] = 'Password minimal 6 karakter';
            return null;
        }

        if (!in_array($status, self::STATUSES, true)) {
            $_SESSION['error'] = 'Status akun admin tidak valid';
            return null;
        }

        if ($requiresDistrict && $districtId <= 0) {
            $_SESSION['error'] = 'Kecamatan wajib dipilih untuk role ini';
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
