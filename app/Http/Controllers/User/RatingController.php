<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;

class RatingController extends Controller
{
    public function index()
    {
        $user = $this->requireAuthenticatedUser();
        if ($user === null) {
            return;
        }

        csrf_token();

        $userModel = $this->model('User');
        if ($userModel->hasPendingProfileStatus($user)) {
            session(['error' => 'Lengkapi profil Anda terlebih dahulu di Dasbor sebelum membuka menu rating']);
            $this->redirect('/user/dasbor');
            return;
        }

        $ratingModel = $this->model('Rating');
        $pageData = $ratingModel->getUserRatingPageData((int) $user['id']);

        $messages = [
            'success' => session()->pull('success', ''),
            'error' => session()->pull('error', ''),
        ];

        $this->view('user.rating.index', [
            'title' => 'Rating Saya - SIGAP',
            'user' => $user,
            'pageData' => $pageData,
            'messages' => $messages,
        ]);
    }

    public function store()
    {
        $user = $this->requireAuthenticatedUser();
        if ($user === null) {
            return;
        }

        verify_csrf();

        $ratingModel = $this->model('Rating');
        $result = $ratingModel->saveUserRating((int) $user['id'], request()->all());

        if (empty($result['success'])) {
            session(['error' => (string) ($result['message'] ?? 'Rating gagal disimpan.')]);
            $this->redirect('/user/rating');
            return;
        }

        session(['success' => (string) ($result['message'] ?? 'Rating berhasil disimpan.')]);

        $anchor = trim((string) ($result['anchor'] ?? ''));
        $redirectPath = '/user/rating';

        if ($anchor !== '') {
            $redirectPath .= '#' . $anchor;
        }

        $this->redirect($redirectPath);
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
