<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

class AuthController extends Controller
{
    public function login()
    {
        csrf_token();

        $error = session()->pull('error', '');
        $authSessionRedirectConfig = is_admin_logged_in()
            ? [
                'active' => true,
                'storageKey' => auth_browser_session_storage_key('admin'),
                'dashboardUrl' => url('admin/dasbor'),
                'logoutUrl' => url('admin/logout')
            ]
            : [
                'active' => false
            ];

        $this->view('admin.login', [
            'title' => 'Login Admin SIGAP',
            'error' => $error,
            'authLogoUrl' => url('admin/login'),
            'authSessionRedirectConfig' => $authSessionRedirectConfig
        ]);
    }

    public function authenticate()
    {
        verify_csrf_or_redirect('/admin/login', 'Sesi admin Anda telah habis. Silakan masuk ulang.');

        $username = trim((string) request('username', ''));
        $password = (string) request('password', '');
        $captcha = trim((string) request('captcha', ''));

        session(['old_admin_username' => $username]);

        if ($username === '' || $password === '' || $captcha === '') {
            session(['error' => 'Username, password, dan captcha wajib diisi']);
            $this->redirect('/admin/login');
            return;
        }

        $sessionCaptcha = session('captcha');
        if (empty($sessionCaptcha) || $captcha !== $sessionCaptcha) {
            session(['error' => 'Captcha tidak sesuai']);
            $this->redirect('/admin/login');
            return;
        }

        $adminModel = $this->model('Admin');
        $admin = $adminModel->findForLogin($username);

        if (!$admin) {
            session(['error' => 'Username admin tidak ditemukan atau akun tidak aktif']);
            $this->redirect('/admin/login');
            return;
        }

        if ($admin['status'] !== 'AKTIF') {
            session(['error' => 'Akun admin tidak aktif']);
            $this->redirect('/admin/login');
            return;
        }

        if (!$adminModel->verifyPassword($admin, $password)) {
            session(['error' => 'Password admin salah']);
            $this->redirect('/admin/login');
            return;
        }

        session()->forget(['old_admin_username', 'captcha']);
        session()->regenerate();
        set_authenticated_admin_session($admin);

        $adminModel->updateLastLogin((int) $admin['id']);

        $this->redirect('/admin/dasbor');
    }

    public function logout()
    {
        $reason = trim((string) request('reason', ''));
        destroy_admin_auth_session();

        if ($reason === 'expired') {
            session(['error' => auth_session_expired_message('admin')]);
        }

        $this->redirect('/admin/login');
    }

    public function keepAlive()
    {
        if (!is_admin_logged_in()) {
            return response()->json([
                'authenticated' => false
            ], 401)->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        }

        touch_admin_session_activity();

        return response()->json([
            'authenticated' => true,
            'idle_timeout_seconds' => admin_session_idle_timeout_seconds()
        ])->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
    }
}
