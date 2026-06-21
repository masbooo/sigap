<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;

class AuthController extends Controller
{
    public function login()
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        csrf_token();

        $error = $_SESSION['error'] ?? '';
        $success = $_SESSION['success'] ?? '';
        $authSessionRedirectConfig = is_user_logged_in()
            ? [
                'active' => true,
                'storageKey' => auth_browser_session_storage_key('user'),
                'dashboardUrl' => base_url('user/dasbor'),
                'logoutUrl' => base_url('logout')
            ]
            : [
                'active' => false
            ];

        unset($_SESSION['error'], $_SESSION['success']);

        $this->view('user.login', [
            'title' => 'Login SIGAP',
            'error' => $error,
            'success' => $success,
            'authSessionRedirectConfig' => $authSessionRedirectConfig
        ]);
    }

    public function register()
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        csrf_token();

        $error = $_SESSION['error'] ?? '';
        $success = $_SESSION['success'] ?? '';

        unset($_SESSION['error'], $_SESSION['success']);

        $this->view('user.daftar', [
            'title' => 'Daftar SIGAP',
            'error' => $error,
            'success' => $success
        ]);
    }

    public function forget()
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        csrf_token();

        $error = $_SESSION['error'] ?? '';
        $success = $_SESSION['success'] ?? '';
        $oldForgotPassword = $_SESSION['old_forgot_password'] ?? [];
        $step = (
            !empty($_SESSION['forgot_password_verified']) &&
            !empty($_SESSION['forgot_password_user_id'])
        ) ? 2 : 1;

        unset($_SESSION['error'], $_SESSION['success']);

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
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        verify_csrf_or_redirect('/lupa-sandi', 'Sesi Anda telah habis. Silakan ulangi verifikasi.');

        $this->clearForgotPasswordSession();

        $nik = trim($_POST['nik'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $captcha = trim($_POST['captcha'] ?? '');

        $_SESSION['old_forgot_password'] = [
            'nik' => $nik,
            'phone' => $phone
        ];

        if ($nik === '' || $phone === '' || $captcha === '') {
            $_SESSION['error'] = 'Semua field wajib diisi';
            $this->redirect('/lupa-sandi');
            return;
        }

        if (!preg_match('/^[0-9]{16}$/', $nik)) {
            $_SESSION['error'] = 'NIK harus 16 digit angka';
            $this->redirect('/lupa-sandi');
            return;
        }

        if (!preg_match('/^[0-9]{10,15}$/', $phone)) {
            $_SESSION['error'] = 'Telp / HP harus 10-15 digit angka';
            $this->redirect('/lupa-sandi');
            return;
        }

        if (empty($_SESSION['captcha']) || $captcha !== $_SESSION['captcha']) {
            $_SESSION['error'] = 'Captcha tidak sesuai';
            $this->redirect('/lupa-sandi');
            return;
        }

        $userModel = $this->model('User');
        $user = $userModel->findActiveByNikAndPhone($nik, $phone);

        if (!$user) {
            $_SESSION['error'] = 'NIK atau Telp / HP tidak sesuai';
            $this->redirect('/lupa-sandi');
            return;
        }

        $_SESSION['forgot_password_verified'] = true;
        $_SESSION['forgot_password_user_id'] = (int) $user['id'];
        unset($_SESSION['captcha']);

        $this->redirect('/lupa-sandi');
    }

    public function resetForgotPassword()
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        verify_csrf_or_redirect('/lupa-sandi', 'Sesi Anda telah habis. Silakan ulangi reset password.');

        $userId = (int) ($_SESSION['forgot_password_user_id'] ?? 0);

        if (empty($_SESSION['forgot_password_verified']) || $userId <= 0) {
            $this->clearForgotPasswordSession();
            $_SESSION['error'] = 'Silakan verifikasi NIK dan nomor HP terlebih dahulu';
            $this->redirect('/lupa-sandi');
            return;
        }

        $password = trim($_POST['password'] ?? '');
        $passwordConfirmation = trim($_POST['password_confirmation'] ?? '');

        if ($password === '' || $passwordConfirmation === '') {
            $_SESSION['error'] = 'Semua field wajib diisi';
            $this->redirect('/lupa-sandi');
            return;
        }

        if (!preg_match('/^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{8,}$/', $password)) {
            $_SESSION['error'] = 'Password minimal 8 karakter dan harus terdiri dari huruf dan angka';
            $this->redirect('/lupa-sandi');
            return;
        }

        if ($password !== $passwordConfirmation) {
            $_SESSION['error'] = 'Ulangi Password tidak sesuai';
            $this->redirect('/lupa-sandi');
            return;
        }

        $userModel = $this->model('User');
        $user = $userModel->findById($userId);

        if (!$user || $user['status'] !== 'AKTIF') {
            $this->clearForgotPasswordSession();
            $_SESSION['error'] = 'Akun tidak dapat digunakan untuk reset password';
            $this->redirect('/lupa-sandi');
            return;
        }

        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
        $updated = $userModel->updatePassword($userId, $hashedPassword);

        if (!$updated) {
            $_SESSION['error'] = 'Gagal mengubah password. Silakan coba lagi';
            $this->redirect('/lupa-sandi');
            return;
        }

        $this->clearForgotPasswordSession();
        unset($_SESSION['old_forgot_password']);

        $_SESSION['success'] = 'Password berhasil diubah. Silakan login dengan password baru';
        $this->redirect('/login');
    }

    public function cancelForgotPassword()
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $this->clearForgotPasswordSession(true);
        unset($_SESSION['error'], $_SESSION['success']);

        $this->redirect('/login');
    }

    public function checkUsername()
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        header('Content-Type: application/json; charset=utf-8');

        $username = trim($_GET['username'] ?? '');

        if ($username === '') {
            echo json_encode([
                'available' => false,
                'message' => 'Username belum diisi'
            ]);
            exit;
        }

        if (!preg_match('/^[A-Za-z0-9]{6,30}$/', $username)) {
            echo json_encode([
                'available' => false,
                'message' => 'Username minimal 6 karakter dan hanya boleh terdiri dari huruf dan angka'
            ]);
            exit;
        }

        $userModel = $this->model('User');
        $exists = $userModel->usernameExists($username);

        echo json_encode([
            'available' => !$exists,
            'message' => $exists ? 'Username telah digunakan' : 'Username belum digunakan (tersedia)'
        ]);
        exit;
    }

    public function createUser()
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        verify_csrf_or_redirect('/daftar', 'Sesi Anda telah habis. Silakan daftar ulang.');

        $username = trim($_POST['username'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $passwordConfirmation = trim($_POST['password_confirmation'] ?? '');
        $captcha = trim($_POST['captcha'] ?? '');

        $_SESSION['old_username'] = $username;

        if ($username === '' || $password === '' || $passwordConfirmation === '' || $captcha === '') {
            $_SESSION['error'] = 'Semua field wajib diisi';
            $this->redirect('/daftar');
            return;
        }

        if (!preg_match('/^[A-Za-z0-9]{6,30}$/', $username)) {
            $_SESSION['error'] = 'Username minimal 6 karakter dan hanya boleh terdiri dari huruf dan angka';
            $this->redirect('/daftar');
            return;
        }

        if ($this->model('User')->usernameExists($username)) {
            $_SESSION['error'] = 'Username sudah digunakan';
            $this->redirect('/daftar');
            return;
        }

        if (!preg_match('/^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{8,}$/', $password)) {
            $_SESSION['error'] = 'Password minimal 8 karakter dan harus terdiri dari huruf dan angka';
            $this->redirect('/daftar');
            return;
        }

        if ($password !== $passwordConfirmation) {
            $_SESSION['error'] = 'Ulangi Password tidak sesuai';
            $this->redirect('/daftar');
            return;
        }

        if ($captcha === '') {
            $_SESSION['error'] = 'Captcha belum diisi';
            $this->redirect('/daftar');
            return;
        }

        if (empty($_SESSION['captcha']) || $captcha !== $_SESSION['captcha']) {
            $_SESSION['error'] = 'Captcha tidak sesuai';
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
            $_SESSION['error'] = 'Gagal membuat akun. Silakan coba lagi';
            $this->redirect('/daftar');
            return;
        }

        unset($_SESSION['old_username'], $_SESSION['captcha']);

        $_SESSION['success'] = 'Akun berhasil dibuat. Silakan login';
        $this->redirect('/login');
    }

    public function authenticate()
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        verify_csrf_or_redirect('/login', 'Sesi login Anda telah habis. Silakan masuk ulang.');

        $username = trim($_POST['username'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $captcha  = trim($_POST['captcha'] ?? '');

        $_SESSION['old_username'] = $username;

        if ($username === '' || $password === '' || $captcha === '') {
            $_SESSION['error'] = 'Username, Password dan Captcha wajib diisi';
            $this->redirect('/login');
            return;
        }

        $userModel = $this->model('User');
        $user = $userModel->findForLogin($username);

        if (!$user) {
            $_SESSION['error'] = 'Username tidak ditemukan';
            $this->redirect('/login');
            return;
        }

        if ($user['status'] === 'BLOKIR' || $user['status'] === 'TIDAK AKTIF') {
            $_SESSION['error'] = 'Akun tidak dapat digunakan';
            $this->redirect('/login');
            return;
        }

        if (!password_verify($password, $user['password'])) {
            $_SESSION['error'] = 'Password tidak sesuai';
            $this->redirect('/login');
            return;
        }

        if (empty($_SESSION['captcha']) || $captcha !== $_SESSION['captcha']) {
            $_SESSION['error'] = 'Captcha tidak sesuai';
            $this->redirect('/login');
            return;
        }

        session_regenerate_id(true);

        unset($_SESSION['old_username'], $_SESSION['captcha']);

        set_authenticated_user_session($user);

        $userModel->updateLastLogin((int) $user['id']);

        $this->redirect('/user/dasbor');
    }

    public function logout()
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $reason = trim((string) ($_GET['reason'] ?? ''));
        destroy_user_auth_session();

        if ($reason === 'expired') {
            if (session_status() !== PHP_SESSION_ACTIVE) {
                session_start();
            }

            $_SESSION['error'] = auth_session_expired_message('user');
        }

        $this->redirect('/login');
    }

    public function keepAlive()
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

        if (!is_user_logged_in()) {
            http_response_code(401);
            echo json_encode([
                'authenticated' => false
            ]);
            exit;
        }

        touch_user_session_activity();

        echo json_encode([
            'authenticated' => true,
            'idle_timeout_seconds' => user_session_idle_timeout_seconds()
        ]);
        exit;
    }

    private function clearForgotPasswordSession(bool $clearOldData = false): void
    {
        unset($_SESSION['forgot_password_verified'], $_SESSION['forgot_password_user_id']);

        if ($clearOldData) {
            unset($_SESSION['old_forgot_password'], $_SESSION['captcha']);
        }
    }
}
