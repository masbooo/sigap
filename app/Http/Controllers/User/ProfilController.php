<?php

class ProfilController extends Controller
{
    private const PROFILE_PHOTO_UPLOAD_DIRECTORY = 'user/profile';
    private const PROFILE_PHOTO_MAX_BYTES = 1048576;

    public function index()
    {
        $user = $this->requireAuthenticatedUser();
        if ($user === null) {
            return;
        }

        $userModel = $this->model('User');
        $wilayahModel = $this->model('Wilayah');

        $districtName = '-';
        $villageName = '-';

        $districtId = (int) ($user['district_id'] ?? 0);
        if ($districtId > 0) {
            $district = $wilayahModel->findDistrictById($districtId);
            $districtName = trim((string) ($district['district'] ?? '')) !== ''
                ? (string) $district['district']
                : '-';
        }

        $villageId = (int) ($user['subdistrict_id'] ?? 0);
        if ($villageId > 0) {
            foreach ($wilayahModel->getVillages() as $village) {
                if ((int) ($village['id'] ?? 0) !== $villageId) {
                    continue;
                }

                $villageName = trim((string) ($village['subdistrict'] ?? '')) !== ''
                    ? (string) $village['subdistrict']
                    : '-';
                break;
            }
        }

        $success = $_SESSION['success'] ?? '';
        $error = $_SESSION['error'] ?? '';
        unset($_SESSION['success'], $_SESSION['error']);

        $this->view('user.profil.index', [
            'title' => 'Profil Saya - SIGAP',
            'user' => $user,
            'districtName' => $districtName,
            'villageName' => $villageName,
            'profileIncomplete' => $userModel->isProfileIncomplete($user),
            'messages' => [
                'success' => $success,
                'error' => $error,
            ],
        ]);
    }

    public function updatePhoto()
    {
        $user = $this->requireAuthenticatedUser();
        if ($user === null) {
            return;
        }

        verify_csrf_or_redirect('/user/profil', 'Sesi Anda telah habis. Silakan ulangi upload foto profil.');
        require_once BASE_PATH . '/app/helpers/upload_helper.php';

        $userModel = $this->model('User');
        $file = $_FILES['profile_photo'] ?? null;
        $validationError = $this->validateProfilePhotoUpload($file);

        if ($validationError !== null) {
            $_SESSION['error'] = $validationError;
            $this->redirect('/user/profil');
            return;
        }

        $uploadedFilename = upload_file(
            $file,
            self::PROFILE_PHOTO_UPLOAD_DIRECTORY,
            ['jpg', 'jpeg', 'png']
        );

        if ($uploadedFilename === null) {
            $_SESSION['error'] = 'Foto profil gagal diunggah. Silakan coba lagi.';
            $this->redirect('/user/profil');
            return;
        }

        $newRelativePath = self::PROFILE_PHOTO_UPLOAD_DIRECTORY . '/' . $uploadedFilename;
        $currentPhotoPath = $this->normalizeRelativeUploadPath((string) ($user['pic_path'] ?? ''));

        $updated = $userModel->updateProfilePhotoPath((int) ($user['id'] ?? 0), $newRelativePath);
        if (!$updated) {
            $this->deleteUploadFile($newRelativePath);
            $_SESSION['error'] = 'Foto profil gagal disimpan ke akun. Silakan coba lagi.';
            $this->redirect('/user/profil');
            return;
        }

        if ($currentPhotoPath !== '' && $currentPhotoPath !== $newRelativePath) {
            $this->deleteUploadFile($currentPhotoPath);
        }

        $freshUser = $userModel->findById((int) ($user['id'] ?? 0));
        if (is_array($freshUser) && !empty($freshUser)) {
            set_authenticated_user_session($freshUser);
        }

        $_SESSION['success'] = 'Foto profil berhasil diperbarui.';
        $this->redirect('/user/profil');
    }

    public function resetPhoto()
    {
        $user = $this->requireAuthenticatedUser();
        if ($user === null) {
            return;
        }

        verify_csrf_or_redirect('/user/profil', 'Sesi Anda telah habis. Silakan ulangi reset foto profil.');

        $userModel = $this->model('User');
        $currentPhotoPath = $this->normalizeRelativeUploadPath((string) ($user['pic_path'] ?? ''));

        if ($currentPhotoPath === '') {
            $_SESSION['success'] = 'Foto profil sudah menggunakan gambar default.';
            $this->redirect('/user/profil');
            return;
        }

        $updated = $userModel->updateProfilePhotoPath((int) ($user['id'] ?? 0), null);
        if (!$updated) {
            $_SESSION['error'] = 'Foto profil gagal direset. Silakan coba lagi.';
            $this->redirect('/user/profil');
            return;
        }

        $this->deleteUploadFile($currentPhotoPath);

        $freshUser = $userModel->findById((int) ($user['id'] ?? 0));
        if (is_array($freshUser) && !empty($freshUser)) {
            set_authenticated_user_session($freshUser);
        }

        $_SESSION['success'] = 'Foto profil berhasil direset ke gambar default.';
        $this->redirect('/user/profil');
    }

    public function updatePassword()
    {
        $user = $this->requireAuthenticatedUser();
        if ($user === null) {
            return;
        }

        verify_csrf_or_redirect('/user/profil', 'Sesi Anda telah habis. Silakan ulangi perubahan password.');

        $currentPassword = trim((string) ($_POST['current_password'] ?? ''));
        $newPassword = trim((string) ($_POST['password'] ?? ''));
        $passwordConfirmation = trim((string) ($_POST['password_confirmation'] ?? ''));

        if ($currentPassword === '' || $newPassword === '' || $passwordConfirmation === '') {
            $_SESSION['error'] = 'Semua field password wajib diisi';
            $this->redirect('/user/profil');
            return;
        }

        if (!password_verify($currentPassword, (string) ($user['password'] ?? ''))) {
            $_SESSION['error'] = 'Password sekarang tidak sesuai';
            $this->redirect('/user/profil');
            return;
        }

        if (!preg_match('/^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{8,}$/', $newPassword)) {
            $_SESSION['error'] = 'Password baru minimal 8 karakter dan harus terdiri dari huruf dan angka';
            $this->redirect('/user/profil');
            return;
        }

        if ($newPassword !== $passwordConfirmation) {
            $_SESSION['error'] = 'Ulangi Password tidak sesuai';
            $this->redirect('/user/profil');
            return;
        }

        if (password_verify($newPassword, (string) ($user['password'] ?? ''))) {
            $_SESSION['error'] = 'Password baru harus berbeda dari password sekarang';
            $this->redirect('/user/profil');
            return;
        }

        $userModel = $this->model('User');
        $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
        $updated = $userModel->updatePassword((int) ($user['id'] ?? 0), $hashedPassword);

        if (!$updated) {
            $_SESSION['error'] = 'Gagal mengubah password. Silakan coba lagi';
            $this->redirect('/user/profil');
            return;
        }

        $freshUser = $userModel->findById((int) ($user['id'] ?? 0));
        if (is_array($freshUser) && !empty($freshUser)) {
            set_authenticated_user_session($freshUser);
        }

        $_SESSION['success'] = 'Password berhasil diubah';
        $this->redirect('/user/profil');
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

    private function validateProfilePhotoUpload(?array $file): ?string
    {
        if ($file === null || !isset($file['error'])) {
            return 'Pilih gambar profil terlebih dahulu.';
        }

        $errorCode = (int) $file['error'];
        if ($errorCode !== UPLOAD_ERR_OK) {
            return $this->getProfilePhotoUploadErrorMessage($errorCode);
        }

        $tmpPath = trim((string) ($file['tmp_name'] ?? ''));
        if ($tmpPath === '' || !is_uploaded_file($tmpPath)) {
            return 'Upload foto profil tidak valid.';
        }

        $size = (int) ($file['size'] ?? 0);
        if ($size <= 0) {
            return 'File gambar profil tidak valid.';
        }

        if ($size > self::PROFILE_PHOTO_MAX_BYTES) {
            return 'Ukuran gambar profil maksimal 1MB.';
        }

        $extension = strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
        $allowedMimeByExtension = [
            'jpg' => ['image/jpeg', 'image/pjpeg'],
            'jpeg' => ['image/jpeg', 'image/pjpeg'],
            'png' => ['image/png', 'image/x-png'],
        ];

        if (!isset($allowedMimeByExtension[$extension])) {
            return 'Format gambar profil harus JPG, JPEG, atau PNG.';
        }

        $mimeType = $this->detectMimeType($tmpPath);
        if ($mimeType === '' || !in_array($mimeType, $allowedMimeByExtension[$extension], true)) {
            return 'File yang dipilih bukan gambar JPG, JPEG, atau PNG yang valid.';
        }

        if (@getimagesize($tmpPath) === false) {
            return 'File yang dipilih bukan gambar yang valid.';
        }

        return null;
    }

    private function getProfilePhotoUploadErrorMessage(int $errorCode): string
    {
        return match ($errorCode) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'Ukuran gambar profil terlalu besar.',
            UPLOAD_ERR_PARTIAL => 'Upload gambar profil tidak selesai. Silakan ulangi.',
            UPLOAD_ERR_NO_FILE => 'Pilih gambar profil terlebih dahulu.',
            default => 'Terjadi kendala saat mengunggah gambar profil.',
        };
    }

    private function detectMimeType(string $path): string
    {
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo !== false) {
                $mimeType = finfo_file($finfo, $path);
                finfo_close($finfo);

                if (is_string($mimeType) && trim($mimeType) !== '') {
                    return trim($mimeType);
                }
            }
        }

        if (function_exists('mime_content_type')) {
            $mimeType = mime_content_type($path);
            if (is_string($mimeType) && trim($mimeType) !== '') {
                return trim($mimeType);
            }
        }

        return '';
    }

    private function normalizeRelativeUploadPath(string $relativePath): string
    {
        $normalizedPath = trim(str_replace('\\', '/', $relativePath));

        if ($normalizedPath === '' || preg_match('#^https?://#i', $normalizedPath)) {
            return '';
        }

        if (strpos($normalizedPath, 'assets/uploads/') === 0) {
            return ltrim(substr($normalizedPath, strlen('assets/uploads/')), '/');
        }

        if (strpos($normalizedPath, 'uploads/') === 0) {
            return ltrim(substr($normalizedPath, strlen('uploads/')), '/');
        }

        return ltrim($normalizedPath, '/');
    }

    private function deleteUploadFile(string $relativePath): void
    {
        $normalizedPath = $this->normalizeRelativeUploadPath($relativePath);
        if ($normalizedPath === '') {
            return;
        }

        legacy_delete_upload_file($normalizedPath);
    }
}
