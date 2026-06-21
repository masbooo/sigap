<?php

namespace App\Http\Controllers\Admin;

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
        $authSessionRedirectConfig = is_admin_logged_in()
            ? [
                'active' => true,
                'storageKey' => auth_browser_session_storage_key('admin'),
                'dashboardUrl' => base_url('admin/dasbor'),
                'logoutUrl' => base_url('admin/logout')
            ]
            : [
                'active' => false
            ];
        unset($_SESSION['error']);

        $this->view('admin.login', [
            'title' => 'Login Admin SIGAP',
            'error' => $error,
            'authLogoUrl' => base_url('admin/login'),
            'authSessionRedirectConfig' => $authSessionRedirectConfig
        ]);
    }

    public function authenticate()
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        verify_csrf_or_redirect('/admin/login', 'Sesi admin Anda telah habis. Silakan masuk ulang.');

        $username = trim($_POST['username'] ?? '');
        $password = (string) ($_POST['password'] ?? '');
        $captcha = trim($_POST['captcha'] ?? '');

        $_SESSION['old_admin_username'] = $username;

        if ($username === '' || $password === '' || $captcha === '') {
            $_SESSION['error'] = 'Username, password, dan captcha wajib diisi';
            $this->redirect('/admin/login');
            return;
        }

        if (empty($_SESSION['captcha']) || $captcha !== $_SESSION['captcha']) {
            $_SESSION['error'] = 'Captcha tidak sesuai';
            $this->redirect('/admin/login');
            return;
        }

        $adminModel = $this->model('Admin');
        $admin = $adminModel->findForLogin($username);

        if (!$admin) {
            $_SESSION['error'] = 'Username admin tidak ditemukan atau akun tidak aktif';
            $this->redirect('/admin/login');
            return;
        }

        if ($admin['status'] !== 'AKTIF') {
            $_SESSION['error'] = 'Akun admin tidak aktif';
            $this->redirect('/admin/login');
            return;
        }

        if (!$adminModel->verifyPassword($admin, $password)) {
            $_SESSION['error'] = 'Password admin salah';
            $this->redirect('/admin/login');
            return;
        }

        unset($_SESSION['old_admin_username'], $_SESSION['captcha']);
        session_regenerate_id(true);
        set_authenticated_admin_session($admin);

        $adminModel->updateLastLogin((int) $admin['id']);

        $this->redirect('/admin/dasbor');
    }

    public function logout()
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $reason = trim((string) ($_GET['reason'] ?? ''));
        destroy_admin_auth_session();

        if ($reason === 'expired') {
            if (session_status() !== PHP_SESSION_ACTIVE) {
                session_start();
            }

            $_SESSION['error'] = auth_session_expired_message('admin');
        }

        $this->redirect('/admin/login');
    }

    public function keepAlive()
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

        if (!is_admin_logged_in()) {
            http_response_code(401);
            echo json_encode([
                'authenticated' => false
            ]);
            exit;
        }

        touch_admin_session_activity();

        echo json_encode([
            'authenticated' => true,
            'idle_timeout_seconds' => admin_session_idle_timeout_seconds()
        ]);
        exit;
    }
}
