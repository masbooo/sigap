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
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        if (empty($_SESSION['user_auth']) || empty($_SESSION['user']['id'])) {
            $this->redirect('/login');
            return null;
        }

        $userModel = $this->model('User');
        $user = $userModel->findById((int) $_SESSION['user']['id']);

        if (!$user) {
            unset($_SESSION['user_auth'], $_SESSION['user']);
            session_destroy();
            $this->redirect('/login');
            return null;
        }

        return $user;
    }
}
