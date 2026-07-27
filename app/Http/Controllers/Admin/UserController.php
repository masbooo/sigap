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
        $success = session()->pull('success', '');
        $error = session()->pull('error', '');

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

        $id = (int) request('id', 0);
        if ($id <= 0) {
            session(['error' => 'Data akun user tidak valid']);
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

        $id = (int) request('id', 0);
        if ($id <= 0) {
            session(['error' => 'Data akun user tidak valid']);
            $this->redirect('/admin/pengaturan/user');
            return;
        }

        $this->deleteUserAccount($id);
        $this->redirect('/admin/pengaturan/user');
    }

    private function storeUserAccount(array $payload): void
    {
        $userModel = $this->model('User');

        if ($userModel->findByUsername($payload['username'])) {
            session(['error' => 'Username user sudah digunakan']);
            return;
        }

        $created = $userModel->createUserAccount($payload);
        session([$created ? 'success' : 'error' => $created
            ? 'Akun user berhasil ditambahkan'
            : 'Gagal menambahkan akun user']);
    }

    private function updateUserAccount(int $id, array $payload): void
    {
        $userModel = $this->model('User');
        $existing = $userModel->findById($id);

        if (!$existing) {
            session(['error' => 'Akun user tidak ditemukan pada menu User']);
            return;
        }

        if ($userModel->usernameExistsForOther($payload['username'], $id)) {
            session(['error' => 'Username user sudah digunakan']);
            return;
        }

        $updated = $userModel->updateUserAccount($id, $payload);
        session([$updated ? 'success' : 'error' => $updated
            ? 'Akun user berhasil diperbarui'
            : 'Gagal memperbarui akun user']);
    }

    private function deleteUserAccount(int $id): void
    {
        $userModel = $this->model('User');
        $existing = $userModel->findById($id);

        if (!$existing) {
            session(['error' => 'Akun user tidak ditemukan pada menu User']);
            return;
        }

        if (!$userModel->hasAssociatedRecords($id)) {
            $userModel->deleteById($id);
            session(['success' => 'Akun user berhasil dihapus']);
            return;
        }

        $deactivated = $userModel->updateStatus($id, 'TIDAK AKTIF');
        session([$deactivated ? 'success' : 'error' => $deactivated
            ? 'Akun user dinonaktifkan karena masih memiliki data terkait'
            : 'Gagal menghapus akun user']);
    }

    private function validatePayload(bool $isCreate): ?array
    {
        $name = trim((string) request('name', ''));
        $username = trim((string) request('username', ''));
        $password = (string) request('password', '');
        $phone = trim((string) request('phone', ''));
        $status = strtoupper(trim((string) request('status', 'AKTIF')));

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

        if (!in_array($status, self::USER_STATUSES, true)) {
            session(['error' => 'Status akun user tidak valid']);
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
