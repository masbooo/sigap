<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;

class AuthController extends Controller
{
    public function login()
    {
        csrf_token();

        $error = session()->pull('error', '');
        $success = session()->pull('success', '');
        $authSessionRedirectConfig = is_user_logged_in()
            ? [
                'active' => true,
                'storageKey' => auth_browser_session_storage_key('user'),
                'dashboardUrl' => url('user/dasbor'),
                'logoutUrl' => url('logout')
            ]
            : [
                'active' => false
            ];

        $this->view('user.login', [
            'title' => 'Login SIGAP',
            'error' => $error,
            'success' => $success,
            'authSessionRedirectConfig' => $authSessionRedirectConfig
        ]);
    }

    public function register()
    {
        csrf_token();

        $error = session()->pull('error', '');
        $success = session()->pull('success', '');

        $this->view('user.daftar', [
            'title' => 'Daftar SIGAP',
            'error' => $error,
            'success' => $success
        ]);
    }

    public function forget()
    {
        csrf_token();

        $error = session()->pull('error', '');
        $success = session()->pull('success', '');
        $oldForgotPassword = session('old_forgot_password', []);
        $step = (
            !empty(session('forgot_password_verified')) &&
            !empty(session('forgot_password_user_id'))
        ) ? 2 : 1;

        $this->view('user.lupa-sandi', [
            'title' => 'Lupa Sandi SIGAP',
            'error' => $error,
            'success' => $success,
            'step' => $step,
            'oldForgotPassword' => $oldForgotPassword
        ]);
    }

    public function verifyForgotPassword()
    {
        verify_csrf_or_redirect('/lupa-sandi', 'Sesi Anda telah habis. Silakan ulangi verifikasi.');

        $this->clearForgotPasswordSession();

        $nik = trim(request()->input('nik', ''));
        $phone = trim(request()->input('phone', ''));
        $captcha = trim(request()->input('captcha', ''));

        session(['old_forgot_password' => [
            'nik' => $nik,
            'phone' => $phone
        ]]);

        if ($nik === '' || $phone === '' || $captcha === '') {
            session(['error' => 'Semua field wajib diisi']);
            $this->redirect('/lupa-sandi');
            return;
        }

        if (!preg_match('/^[0-9]{16}$/', $nik)) {
            session(['error' => 'NIK harus 16 digit angka']);
            $this->redirect('/lupa-sandi');
            return;
        }

        if (!preg_match('/^[0-9]{10,15}$/', $phone)) {
            session(['error' => 'Telp / HP harus 10-15 digit angka']);
            $this->redirect('/lupa-sandi');
            return;
        }

        if (empty(session('captcha')) || $captcha !== session('captcha')) {
            session(['error' => 'Captcha tidak sesuai']);
            $this->redirect('/lupa-sandi');
            return;
        }

        $userModel = $this->model('User');
        $user = $userModel->findActiveByNikAndPhone($nik, $phone);

        if (!$user) {
            session(['error' => 'NIK atau Telp / HP tidak sesuai']);
            $this->redirect('/lupa-sandi');
            return;
        }

        session([
            'forgot_password_verified' => true,
            'forgot_password_user_id' => (int) $user['id'],
        ]);
        session()->forget('captcha');

        $this->redirect('/lupa-sandi');
    }

    public function resetForgotPassword()
    {
        verify_csrf_or_redirect('/lupa-sandi', 'Sesi Anda telah habis. Silakan ulangi reset password.');

        $userId = (int) session('forgot_password_user_id', 0);

        if (empty(session('forgot_password_verified')) || $userId <= 0) {
            $this->clearForgotPasswordSession();
            session(['error' => 'Silakan verifikasi NIK dan nomor HP terlebih dahulu']);
            $this->redirect('/lupa-sandi');
            return;
        }

        $password = trim(request()->input('password', ''));
        $passwordConfirmation = trim(request()->input('password_confirmation', ''));

        if ($password === '' || $passwordConfirmation === '') {
            session(['error' => 'Semua field wajib diisi']);
            $this->redirect('/lupa-sandi');
            return;
        }

        if (!preg_match('/^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{8,}$/', $password)) {
            session(['error' => 'Password minimal 8 karakter dan harus terdiri dari huruf dan angka']);
            $this->redirect('/lupa-sandi');
            return;
        }

        if ($password !== $passwordConfirmation) {
            session(['error' => 'Ulangi Password tidak sesuai']);
            $this->redirect('/lupa-sandi');
            return;
        }

        $userModel = $this->model('User');
        $user = $userModel->findById($userId);

        if (!$user || $user['status'] !== 'AKTIF') {
            $this->clearForgotPasswordSession();
            session(['error' => 'Akun tidak dapat digunakan untuk reset password']);
            $this->redirect('/lupa-sandi');
            return;
        }

        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
        $updated = $userModel->updatePassword($userId, $hashedPassword);

        if (!$updated) {
            session(['error' => 'Gagal mengubah password. Silakan coba lagi']);
            $this->redirect('/lupa-sandi');
            return;
        }

        $this->clearForgotPasswordSession();
        session()->forget('old_forgot_password');

        session(['success' => 'Password berhasil diubah. Silakan login dengan password baru']);
        $this->redirect('/login');
    }

    public function cancelForgotPassword()
    {
        $this->clearForgotPasswordSession(true);
        session()->forget(['error', 'success']);

        $this->redirect('/login');
    }

    public function checkUsername()
    {
        $username = trim((string) request()->query('username', ''));

        if ($username === '') {
            return response()->json([
                'available' => false,
                'message' => 'Username belum diisi'
            ]);
        }

        if (!preg_match('/^[A-Za-z0-9]{6,30}$/', $username)) {
            return response()->json([
                'available' => false,
                'message' => 'Username minimal 6 karakter dan hanya boleh terdiri dari huruf dan angka'
            ]);
        }

        $userModel = $this->model('User');
        $exists = $userModel->usernameExists($username);

        return response()->json([
            'available' => !$exists,
            'message' => $exists ? 'Username telah digunakan' : 'Username belum digunakan (tersedia)'
        ]);
    }

    public function createUser()
    {
        verify_csrf_or_redirect('/daftar', 'Sesi Anda telah habis. Silakan daftar ulang.');

        $username = trim(request()->input('username', ''));
        $password = trim(request()->input('password', ''));
        $passwordConfirmation = trim(request()->input('password_confirmation', ''));
        $captcha = trim(request()->input('captcha', ''));

        session(['old_username' => $username]);

        if ($username === '' || $password === '' || $passwordConfirmation === '' || $captcha === '') {
            session(['error' => 'Semua field wajib diisi']);
            $this->redirect('/daftar');
            return;
        }

        if (!preg_match('/^[A-Za-z0-9]{6,30}$/', $username)) {
            session(['error' => 'Username minimal 6 karakter dan hanya boleh terdiri dari huruf dan angka']);
            $this->redirect('/daftar');
            return;
        }

        if ($this->model('User')->usernameExists($username)) {
            session(['error' => 'Username sudah digunakan']);
            $this->redirect('/daftar');
            return;
        }

        if (!preg_match('/^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{8,}$/', $password)) {
            session(['error' => 'Password minimal 8 karakter dan harus terdiri dari huruf dan angka']);
            $this->redirect('/daftar');
            return;
        }

        if ($password !== $passwordConfirmation) {
            session(['error' => 'Ulangi Password tidak sesuai']);
            $this->redirect('/daftar');
            return;
        }

        if ($captcha === '') {
            session(['error' => 'Captcha belum diisi']);
            $this->redirect('/daftar');
            return;
        }

        if (empty(session('captcha')) || $captcha !== session('captcha')) {
            session(['error' => 'Captcha tidak sesuai']);
            $this->redirect('/daftar');
            return;
        }

        $userModel = $this->model('User');

        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

        $created = $userModel->createSimple([
            'username' => $username,
            'password' => $hashedPassword,
            'nik' => '',
            'name' => '',
            'address' => '',
            'subdistrict_id' => null,
            'district_id' => null,
            'phone' => '',
            'status' => 'PROSES'
        ]);

        if (!$created) {
            session(['error' => 'Gagal membuat akun. Silakan coba lagi']);
            $this->redirect('/daftar');
            return;
        }

        session()->forget(['old_username', 'captcha']);

        session(['success' => 'Akun berhasil dibuat. Silakan login']);
        $this->redirect('/login');
    }

    public function authenticate()
    {
        verify_csrf_or_redirect('/login', 'Sesi login Anda telah habis. Silakan masuk ulang.');

        $username = trim(request()->input('username', ''));
        $password = trim(request()->input('password', ''));
        $captcha  = trim(request()->input('captcha', ''));

        session(['old_username' => $username]);

        if ($username === '' || $password === '' || $captcha === '') {
            session(['error' => 'Username, Password dan Captcha wajib diisi']);
            $this->redirect('/login');
            return;
        }

        $userModel = $this->model('User');
        $user = $userModel->findForLogin($username);

        if (!$user) {
            session(['error' => 'Username tidak ditemukan']);
            $this->redirect('/login');
            return;
        }

        if ($user['status'] === 'BLOKIR' || $user['status'] === 'TIDAK AKTIF') {
            session(['error' => 'Akun tidak dapat digunakan']);
            $this->redirect('/login');
            return;
        }

        if (!password_verify($password, $user['password'])) {
            session(['error' => 'Password tidak sesuai']);
            $this->redirect('/login');
            return;
        }

        if (empty(session('captcha')) || $captcha !== session('captcha')) {
            session(['error' => 'Captcha tidak sesuai']);
            $this->redirect('/login');
            return;
        }

        session()->regenerate();

        session()->forget(['old_username', 'captcha']);

        set_authenticated_user_session($user);

        $userModel->updateLastLogin((int) $user['id']);

        $this->redirect('/user/dasbor');
    }

    public function logout()
    {
        $reason = trim((string) (request()->query('reason', '')));
        destroy_user_auth_session();

        if ($reason === 'expired') {
            session(['error' => auth_session_expired_message('user')]);
        }

        $this->redirect('/login');
    }

    public function keepAlive()
    {
        if (!is_user_logged_in()) {
            return response()->json([
                'authenticated' => false
            ], 401)->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        }

        touch_user_session_activity();

        return response()->json([
            'authenticated' => true,
            'idle_timeout_seconds' => user_session_idle_timeout_seconds()
        ])->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
    }

    private function clearForgotPasswordSession(bool $clearOldData = false): void
    {
        session()->forget(['forgot_password_verified', 'forgot_password_user_id']);

        if ($clearOldData) {
            session()->forget(['old_forgot_password', 'captcha']);
        }
    }
}
