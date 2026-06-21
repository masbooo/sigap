<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;

class DashboardController extends Controller
{
    private const PROFILE_ID_UPLOAD_DIRECTORY = 'user/identitas';
    private const PROFILE_ID_MAX_BYTES = 1048576;

    public function index()
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        if (empty($_SESSION['user_auth']) || empty($_SESSION['user']['id'])) {
            $this->redirect('/login');
            return;
        }

        if (
            ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' &&
            isset($_POST['save_biodata']) &&
            $_POST['save_biodata'] === '1'
        ) {
            $this->handleBiodataSubmit();
            return;
        }

        $userModel = $this->model('User');
        $wilayahModel = $this->model('Wilayah');
        $reservasiModel = $this->model('Reservasi');

        $user = $userModel->findById((int) $_SESSION['user']['id']);

        if (!$user) {
            unset($_SESSION['user_auth'], $_SESSION['user']);
            session_destroy();
            $this->redirect('/login');
            return;
        }

        $oldBiodata = $_SESSION['old_biodata'] ?? [];
        if (!empty($oldBiodata) && is_array($oldBiodata)) {
            $user = array_merge($user, $oldBiodata);
        }

        $error = $_SESSION['error'] ?? '';
        $success = $_SESSION['success'] ?? '';

        unset($_SESSION['error'], $_SESSION['success'], $_SESSION['old_biodata']);

        $districts = $wilayahModel->getDistricts();
        $districtVillageMap = $wilayahModel->getDistrictVillageMap();
        $dashboardCards = $this->buildDashboardCards(
            $reservasiModel->byUserDetailed((int) $user['id'])
        );

        $this->view('user.index', [
            'title' => 'Infografis User',
            'user' => $user,
            'forceProfileModal' => $userModel->shouldShowProfileCompletionModal($user),
            'error' => $error,
            'success' => $success,
            'districts' => $districts,
            'districtVillageMap' => $districtVillageMap,
            'dashboardCards' => $dashboardCards,
        ]);
    }

    private function buildDashboardCards(array $reservations): array
    {
        return [
            [
                'label' => 'Reservasi Baru',
                'value' => $this->countReservationsByStatuses($reservations, ['RESERVASI BARU']),
                'tone' => 'warning',
                'icon' => 'ti ti-calendar-event',
            ],
            [
                'label' => 'Berkas Reservasi Tidak Sesuai',
                'value' => $this->countReservationsByStatuses($reservations, ['BERKAS RESERVASI TIDAK SESUAI']),
                'tone' => 'dark',
                'icon' => 'ti ti-file-alert',
            ],
            [
                'label' => 'Kerjasama UMKM',
                'value' => $this->countReservationsByStatuses($reservations, ['KERJASAMA UMKM']),
                'tone' => 'info',
                'icon' => 'ti ti-building-store',
            ],
            [
                'label' => 'Proses Verifikasi',
                'value' => $this->countReservationsByStatuses($reservations, ['PROSES VERIFIKASI']),
                'tone' => 'warning',
                'icon' => 'ti ti-clipboard-check',
            ],
            [
                'label' => 'Berkas Verifikasi Tidak Sesuai',
                'value' => $this->countReservationsByStatuses($reservations, ['BERKAS VERIFIKASI TIDAK SESUAI']),
                'tone' => 'dark',
                'icon' => 'ti ti-clipboard-x',
            ],
            [
                'label' => 'Menunggu Pembayaran',
                'value' => $this->countReservationsByStatuses($reservations, ['MENUNGGU PEMBAYARAN']),
                'tone' => 'primary',
                'icon' => 'ti ti-credit-card',
            ],
            [
                'label' => 'Cek Pembayaran',
                'value' => $this->countReservationsByStatuses($reservations, ['CEK PEMBAYARAN']),
                'tone' => 'warning',
                'icon' => 'ti ti-search',
            ],
            [
                'label' => 'Berkas Pembayaran Tidak Sesuai',
                'value' => $this->countReservationsByStatuses($reservations, ['BERKAS PEMBAYARAN TIDAK SESUAI']),
                'tone' => 'dark',
                'icon' => 'ti ti-file-dollar',
            ],
            [
                'label' => 'Permohonan Ditolak',
                'value' => $this->countReservationsByStatuses($reservations, ['PERMOHONAN DITOLAK']),
                'tone' => 'danger',
                'icon' => 'ti ti-file-x',
            ],
            [
                'label' => 'Pembayaran Lunas',
                'value' => $this->countReservationsByStatuses($reservations, ['PEMBAYARAN LUNAS']),
                'tone' => 'success',
                'icon' => 'ti ti-cash',
            ],
            [
                'label' => 'Dibatalkan Pemohon',
                'value' => $this->countReservationsByStatuses($reservations, ['DIBATALKAN PEMOHON']),
                'tone' => 'danger',
                'icon' => 'ti ti-circle-x',
            ],
            [
                'label' => 'Acara Selesai',
                'value' => $this->countReservationsByStatuses($reservations, ['ACARA SELESAI']),
                'tone' => 'dark',
                'icon' => 'ti ti-checklist',
            ],
        ];
    }

    private function countReservationsByStatuses(array $reservations, array $statuses): int
    {
        return count(array_filter($reservations, static function (array $reservation) use ($statuses): bool {
            return reservation_status_matches($reservation['status'] ?? '', $statuses);
        }));
    }

    private function handleBiodataSubmit(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        if (empty($_SESSION['user_auth']) || empty($_SESSION['user']['id'])) {
            $this->redirect('/login');
            return;
        }

        verify_csrf_or_redirect('/user/dasbor', 'Sesi Anda telah habis. Silakan ulangi simpan profil.');
        require_once BASE_PATH . '/app/Supports/Upload/UploadFile.php';

        $userId = (int) $_SESSION['user']['id'];
        $userModel = $this->model('User');
        $currentUser = $userModel->findById($userId);

        if (!$currentUser) {
            unset($_SESSION['user_auth'], $_SESSION['user']);
            session_destroy();
            $this->redirect('/login');
            return;
        }

        $nik = trim($_POST['nik'] ?? '');
        $name = trim($_POST['name'] ?? '');
        $gender = strtoupper(trim((string) ($_POST['gender'] ?? '')));
        $address = trim($_POST['address'] ?? '');
        $districtId = (int) ($_POST['district_id'] ?? 0);
        $subdistrictId = (int) ($_POST['subdistrict_id'] ?? 0);
        $phone = trim($_POST['phone'] ?? '');

        $_SESSION['old_biodata'] = [
            'nik' => $nik,
            'name' => $name,
            'gender' => $gender,
            'address' => $address,
            'district_id' => $districtId,
            'subdistrict_id' => $subdistrictId,
            'phone' => $phone
        ];

        if ($nik === '' || $name === '' || $gender === '' || $address === '' || $phone === '' || $districtId <= 0 || $subdistrictId <= 0) {
            $_SESSION['error'] = 'Semua field wajib diisi';
            $this->redirect('/user/dasbor');
            return;
        }

        if (!in_array($gender, ['L', 'P'], true)) {
            $_SESSION['error'] = 'Jenis kelamin wajib dipilih';
            $this->redirect('/user/dasbor');
            return;
        }

        if (!preg_match('/^[0-9]{16}$/', $nik)) {
            $_SESSION['error'] = 'NIK harus 16 digit angka';
            $this->redirect('/user/dasbor');
            return;
        }

        if (!preg_match('/^[0-9]{10,15}$/', $phone)) {
            $_SESSION['error'] = 'Telp / HP harus 10-15 digit angka';
            $this->redirect('/user/dasbor');
            return;
        }

        $wilayahModel = $this->model('Wilayah');

        if (!$wilayahModel->districtExists($districtId)) {
            $_SESSION['error'] = 'Kecamatan tidak valid';
            $this->redirect('/user/dasbor');
            return;
        }

        if (!$wilayahModel->villageBelongsToDistrict($subdistrictId, $districtId)) {
            $_SESSION['error'] = 'Kelurahan tidak valid';
            $this->redirect('/user/dasbor');
            return;
        }

        if ($userModel->nikExistsForOther($nik, $userId)) {
            $_SESSION['error'] = 'NIK sudah digunakan akun lain';
            $this->redirect('/user/dasbor');
            return;
        }

        $currentIdentityPath = $this->normalizeRelativeUploadPath((string) ($currentUser['id_path'] ?? ''));
        $identityFile = $_FILES['id_file'] ?? null;
        $identityValidationError = $this->validateIdentityUpload($identityFile, $currentIdentityPath !== '');

        if ($identityValidationError !== null) {
            $_SESSION['error'] = $identityValidationError;
            $this->redirect('/user/dasbor');
            return;
        }

        $newIdentityPath = $currentIdentityPath;
        $hasNewIdentityUpload = $identityFile !== null
            && isset($identityFile['error'])
            && (int) $identityFile['error'] !== UPLOAD_ERR_NO_FILE;

        if ($hasNewIdentityUpload) {
            $uploadedFilename = upload_file($identityFile, self::PROFILE_ID_UPLOAD_DIRECTORY);

            if ($uploadedFilename === null) {
                $_SESSION['error'] = 'File KTP gagal diunggah. Pastikan format file JPG, JPEG, PNG, atau PDF';
                $this->redirect('/user/dasbor');
                return;
            }

            $newIdentityPath = self::PROFILE_ID_UPLOAD_DIRECTORY . '/' . $uploadedFilename;
        }

        $updated = $userModel->activateWithBiodata($userId, [
            'nik' => $nik,
            'name' => $name,
            'gender' => $gender,
            'address' => $address,
            'district_id' => $districtId,
            'subdistrict_id' => $subdistrictId,
            'phone' => $phone,
            'id_path' => $newIdentityPath,
        ]);

        if (!$updated) {
            if ($hasNewIdentityUpload && $newIdentityPath !== '' && $newIdentityPath !== $currentIdentityPath) {
                $this->deleteUploadFile($newIdentityPath);
            }

            $_SESSION['error'] = 'Gagal menyimpan profil. Silakan coba lagi';
            $this->redirect('/user/dasbor');
            return;
        }

        if ($hasNewIdentityUpload && $currentIdentityPath !== '' && $newIdentityPath !== $currentIdentityPath) {
            $this->deleteUploadFile($currentIdentityPath);
        }

        $freshUser = $userModel->findById($userId);

        set_authenticated_user_session($freshUser);

        unset($_SESSION['old_biodata']);

        $_SESSION['success'] = 'Profil berhasil disimpan';
        $this->redirect('/user/dasbor');
    }

    private function validateIdentityUpload(?array $file, bool $hasExistingUpload): ?string
    {
        if ($file === null || !isset($file['error'])) {
            return $hasExistingUpload ? null : 'Upload KTP wajib diisi';
        }

        $errorCode = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($errorCode === UPLOAD_ERR_NO_FILE) {
            return $hasExistingUpload ? null : 'Upload KTP wajib diisi';
        }

        if ($errorCode !== UPLOAD_ERR_OK) {
            return $this->resolveIdentityUploadErrorMessage($errorCode);
        }

        if (!isset($file['tmp_name']) || !is_uploaded_file((string) $file['tmp_name'])) {
            return 'Upload file KTP tidak valid.';
        }

        $fileSize = (int) ($file['size'] ?? 0);
        if ($fileSize <= 0) {
            return 'File KTP tidak valid.';
        }

        if ($fileSize > self::PROFILE_ID_MAX_BYTES) {
            return 'Ukuran file KTP maksimal 1MB.';
        }

        $extension = strtolower((string) pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
        if (!in_array($extension, ['jpg', 'jpeg', 'png', 'pdf'], true)) {
            return 'Format file KTP harus JPG, JPEG, PNG, atau PDF.';
        }

        return null;
    }

    private function resolveIdentityUploadErrorMessage(int $errorCode): string
    {
        return match ($errorCode) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'Ukuran file KTP terlalu besar.',
            UPLOAD_ERR_PARTIAL => 'Upload file KTP tidak selesai. Silakan ulangi.',
            UPLOAD_ERR_NO_FILE => 'Upload KTP wajib diisi',
            default => 'Terjadi kendala saat mengunggah file KTP.',
        };
    }

    private function normalizeRelativeUploadPath(?string $relativePath): string
    {
        $normalizedPath = trim(str_replace('\\', '/', (string) $relativePath));

        if (str_starts_with($normalizedPath, 'user/identity/')) {
            $normalizedPath = 'user/identitas/' . substr($normalizedPath, strlen('user/identity/'));
        }

        return $normalizedPath !== '' ? ltrim($normalizedPath, '/') : '';
    }

    private function deleteUploadFile(?string $relativePath): void
    {
        $normalizedPath = $this->normalizeRelativeUploadPath($relativePath);
        if ($normalizedPath === '') {
            return;
        }

        legacy_delete_upload_file($normalizedPath);
    }
}
