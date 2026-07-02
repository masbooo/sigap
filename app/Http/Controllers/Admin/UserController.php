<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

class UserController extends Controller
{
    private const USER_STATUSES = ['PROSES', 'AKTIF', 'TIDAK AKTIF', 'BLOKIR'];

    public function index()
    {
        require_admin_menu_access('pengaturan.user');

        $admin = admin_user() ?? [];
        $userModel = $this->model('User');

        csrf_token();
        $success = $_SESSION['success'] ?? '';
        $error = $_SESSION['error'] ?? '';
        unset($_SESSION['success'], $_SESSION['error']);

        $this->view('admin.pengaturan.user', [
            'title' => 'User - SIGAP',
            'admin' => $admin,
            'roleContext' => resolve_admin_role_context($admin),
            'userAccounts' => $userModel->getManagedUsers(),
            'messages' => [
                'success' => $success,
                'error' => $error,
            ],
            'userStatuses' => self::USER_STATUSES,
        ]);
    }

    public function store()
    {
        require_admin_menu_access('pengaturan.user');
        verify_csrf();

        $payload = $this->validatePayload(true);

        if ($payload !== null) {
            $this->storeUserAccount($payload);
        }

        $this->redirect('/admin/pengaturan/user');
    }

    public function update()
    {
        require_admin_menu_access('pengaturan.user');
        verify_csrf();

        $id = (int) ($_POST['id'] ?? 0);
        if ($id <= 0) {
            $_SESSION['error'] = 'Data akun user tidak valid';
            $this->redirect('/admin/pengaturan/user');
            return;
        }

        $payload = $this->validatePayload(false);

        if ($payload !== null) {
            $this->updateUserAccount($id, $payload);
        }

        $this->redirect('/admin/pengaturan/user');
    }

    public function destroy()
    {
        require_admin_menu_access('pengaturan.user');
        verify_csrf();

        $id = (int) ($_POST['id'] ?? 0);
        if ($id <= 0) {
            $_SESSION['error'] = 'Data akun user tidak valid';
            $this->redirect('/admin/pengaturan/user');
            return;
        }

        $this->destroyUserAccount($id);
        $this->redirect('/admin/pengaturan/user');
    }

    private function storeUserAccount(array $payload): void
    {
        $userModel = $this->model('User');

        if ($userModel->usernameExistsForOther($payload['username'])) {
            $_SESSION['error'] = 'Username user sudah digunakan';
            return;
        }

        $created = $userModel->createManagedAccount([
            'username' => $payload['username'],
            'password' => password_hash($payload['password'], PASSWORD_BCRYPT),
            'nik' => null,
            'name' => $payload['name'],
            'address' => '',
            'subdistrict_id' => null,
            'district_id' => null,
            'phone' => $payload['phone'],
            'status' => $payload['status'],
        ]);

        $_SESSION[$created ? 'success' : 'error'] = $created
            ? 'Akun user berhasil ditambahkan'
            : 'Gagal menambahkan akun user';
    }

    private function updateUserAccount(int $id, array $payload): void
    {
        $userModel = $this->model('User');

        if (!$userModel->findById($id)) {
            $_SESSION['error'] = 'Akun user tidak ditemukan';
            return;
        }

        if ($userModel->usernameExistsForOther($payload['username'], $id)) {
            $_SESSION['error'] = 'Username user sudah digunakan';
            return;
        }

        $updated = $userModel->updateManagedAccount($id, [
            'username' => $payload['username'],
            'password' => $payload['password'] !== '' ? password_hash($payload['password'], PASSWORD_BCRYPT) : '',
            'name' => $payload['name'],
            'phone' => $payload['phone'],
            'status' => $payload['status'],
        ]);

        $_SESSION[$updated ? 'success' : 'error'] = $updated
            ? 'Akun user berhasil diperbarui'
            : 'Gagal memperbarui akun user';
    }

    private function destroyUserAccount(int $id): void
    {
        $userModel = $this->model('User');

        if (!$userModel->findById($id)) {
            $_SESSION['error'] = 'Akun user tidak ditemukan';
            return;
        }

        if ($userModel->deleteAccount($id)) {
            $_SESSION['success'] = 'Akun user berhasil dihapus';
            return;
        }

        $deactivated = $userModel->updateStatus($id, 'TIDAK AKTIF');
        $_SESSION[$deactivated ? 'success' : 'error'] = $deactivated
            ? 'Akun user dinonaktifkan karena masih memiliki data terkait'
            : 'Gagal menghapus akun user';
    }

    private function validatePayload(bool $isCreate): ?array
    {
        $name = trim((string) ($_POST['name'] ?? ''));
        $username = trim((string) ($_POST['username'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $phone = trim((string) ($_POST['phone'] ?? ''));
        $status = strtoupper(trim((string) ($_POST['status'] ?? 'AKTIF')));

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

        if (!in_array($status, self::USER_STATUSES, true)) {
            $_SESSION['error'] = 'Status akun user tidak valid';
            return null;
        }

        return [
            'name' => $name,
            'username' => $username,
            'password' => $password,
            'phone' => $phone,
            'status' => $status,
        ];
    }
}
