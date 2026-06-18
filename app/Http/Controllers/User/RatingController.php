<?php

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
            $_SESSION['error'] = 'Lengkapi profil Anda terlebih dahulu di Dasbor sebelum membuka menu rating';
            $this->redirect('/user/dasbor');
            return;
        }

        $ratingModel = $this->model('Rating');
        $pageData = $ratingModel->getUserRatingPageData((int) $user['id']);

        $messages = [
            'success' => $_SESSION['success'] ?? '',
            'error' => $_SESSION['error'] ?? '',
        ];

        unset($_SESSION['success'], $_SESSION['error']);

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
        $result = $ratingModel->saveUserRating((int) $user['id'], $_POST);

        if (empty($result['success'])) {
            $_SESSION['error'] = (string) ($result['message'] ?? 'Rating gagal disimpan.');
            $this->redirect('/user/rating');
            return;
        }

        $_SESSION['success'] = (string) ($result['message'] ?? 'Rating berhasil disimpan.');

        $anchor = trim((string) ($result['anchor'] ?? ''));
        $redirectPath = '/user/rating';

        if ($anchor !== '') {
            $redirectPath .= '#' . $anchor;
        }

        $this->redirect($redirectPath);
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
