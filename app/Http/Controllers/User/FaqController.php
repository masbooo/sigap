<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;

class FaqController extends Controller
{
    public function index()
    {
        $user = $this->requireAuthenticatedUser();
        if ($user === null) {
            return;
        }

        $this->view('user.faq.index', [
            'title' => 'FAQ User - SIGAP',
            'user' => $user,
        ]);
    }

    private function requireAuthenticatedUser(): ?array
    {
        $sessionUser = session('user');
        if (!session('user_auth') || empty($sessionUser['id'])) {
            $this->redirect('/login');
            return null;
        }

        $userModel = $this->model('User');
        $user = $userModel->findById((int) $sessionUser['id']);

        if (!$user) {
            session()->forget(['user_auth', 'user']);
            $this->redirect('/login');
            return null;
        }

        return $user;
    }
}
