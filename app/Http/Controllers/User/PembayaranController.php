<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;

class PembayaranController extends Controller
{
    public function index()
    {
        $user = $this->requireAuthenticatedUser();
        if ($user === null) {
            return;
        }

        csrf_token();

        $reservasiModel = $this->model('Reservasi');
        $reservations = array_values(array_filter(
            $this->appendReservationFileUrls(
                $reservasiModel->byUserDetailed((int) ($user['id'] ?? 0))
            ),
            static function (array $reservation): bool {
                return reservation_status_matches($reservation['status'] ?? '', [
                    'MENUNGGU PEMBAYARAN',
                    'CEK PEMBAYARAN',
                    'BERKAS PEMBAYARAN TIDAK SESUAI',
                ]);
            }
        ));

        $error = $_SESSION['error'] ?? '';
        $success = $_SESSION['success'] ?? '';
        unset($_SESSION['error'], $_SESSION['success']);

        $this->view('user.pembayaran.index', [
            'title' => 'Pembayaran - SIGAP',
            'user' => $user,
            'reservations' => $reservations,
            'messages' => [
                'success' => $success,
                'error' => $error,
            ],
        ]);
    }

    public function uploadProof()
    {
        $user = $this->requireAuthenticatedUser();
        if ($user === null) {
            return;
        }

        verify_csrf();
        require_once base_path('app/Supports/Upload/UploadFile.php');

        $reservationId = (int) ($_POST['reservation_id'] ?? 0);
        if ($reservationId <= 0) {
            $_SESSION['error'] = 'Tagihan yang dipilih tidak valid';
            $this->redirect('/user/pembayaran');
            return;
        }

        $reservasiModel = $this->model('Reservasi');
        $pembayaranModel = $this->model('Pembayaran');
        $reservation = $reservasiModel->findByUserId($reservationId, (int) ($user['id'] ?? 0));

        if (!$reservation) {
            $_SESSION['error'] = 'Data tagihan tidak ditemukan';
            $this->redirect('/user/pembayaran');
            return;
        }

        if (!reservation_status_matches($reservation['status'] ?? '', ['MENUNGGU PEMBAYARAN', 'BERKAS PEMBAYARAN TIDAK SESUAI'])) {
            $_SESSION['error'] = 'Hanya tagihan berstatus Menunggu Pembayaran atau Berkas Pembayaran Tidak Sesuai yang dapat diunggah';
            $this->redirect('/user/pembayaran');
            return;
        }

        $paymentFile = $_FILES['payment_file'] ?? null;
        $paymentUploadErrorCode = (int) ($paymentFile['error'] ?? UPLOAD_ERR_NO_FILE);

        if (!$paymentFile || $paymentUploadErrorCode !== UPLOAD_ERR_OK) {
            $_SESSION['error'] = $this->getUploadErrorMessage($paymentUploadErrorCode, 'Bukti bayar');
            $this->redirect('/user/pembayaran');
            return;
        }

        $uploadedFilename = upload_file($paymentFile, 'reservasi/pembayaran');
        if ($uploadedFilename === null) {
            $_SESSION['error'] = 'Bukti bayar gagal diunggah. Pastikan format file JPG, JPEG, PNG, atau PDF';
            $this->redirect('/user/pembayaran');
            return;
        }

        $relativePaymentPath = 'reservasi/pembayaran/' . $uploadedFilename;
        $saved = $pembayaranModel->create([
            'reservasi_id' => $reservationId,
            'nominal' => (float) ($reservation['total_price'] ?? 0),
            'metode' => 'Upload User',
            'bukti_pembayaran' => $relativePaymentPath,
            'tanggal_bayar' => date('Y-m-d H:i:s'),
            'status_verifikasi' => 'PENDING',
        ]);

        if (!$saved) {
            $this->deleteUploadedFile($relativePaymentPath);
            $_SESSION['error'] = 'Bukti bayar gagal disimpan. Silakan coba lagi';
            $this->redirect('/user/pembayaran');
            return;
        }

        if (!$reservasiModel->updateStatusWithMetadata($reservationId, 'CEK PEMBAYARAN', '', 1, 0)) {
            $pembayaranModel->deleteByReservationAndProofPath($reservationId, $relativePaymentPath);
            $this->deleteUploadedFile($relativePaymentPath);
            $_SESSION['error'] = 'Status pembayaran gagal diperbarui. Silakan coba lagi';
            $this->redirect('/user/pembayaran');
            return;
        }

        $_SESSION['success'] = 'Bukti bayar berhasil diunggah dan statusnya menjadi Cek Pembayaran';
        $this->redirect('/user/pembayaran');
    }

    private function appendReservationFileUrls(array $reservations): array
    {
        foreach ($reservations as &$reservation) {
            $identityRelativePath = $this->normalizeRelativeUploadPath((string) ($reservation['id_path'] ?? ''));
            $applicationRelativePath = $this->normalizeRelativeUploadPath((string) ($reservation['form_path'] ?? ''));
            $umkmRelativePath = $this->normalizeRelativeUploadPath((string) ($reservation['umkm_path'] ?? ''));
            $paymentRelativePath = $this->normalizeRelativeUploadPath((string) ($reservation['payment_proof_path'] ?? ''));

            $reservation['identity_file_url'] = $identityRelativePath !== ''
                ? asset_url('assets/uploads/' . ltrim($identityRelativePath, '/'))
                : '';
            $reservation['application_file_url'] = $applicationRelativePath !== ''
                ? asset_url('assets/uploads/' . ltrim($applicationRelativePath, '/'))
                : '';
            $reservation['umkm_file_url'] = $umkmRelativePath !== ''
                ? asset_url('assets/uploads/' . ltrim($umkmRelativePath, '/'))
                : '';
            $reservation['payment_file_url'] = $paymentRelativePath !== ''
                ? asset_url('assets/uploads/' . ltrim($paymentRelativePath, '/'))
                : '';
        }
        unset($reservation);

        return $reservations;
    }

    private function normalizeRelativeUploadPath(?string $relativePath): string
    {
        $normalizedPath = trim(str_replace('\\', '/', (string) $relativePath));

        if (str_starts_with($normalizedPath, 'user/identity/')) {
            $normalizedPath = 'user/identitas/' . substr($normalizedPath, strlen('user/identity/'));
        }

        return $normalizedPath !== '' ? ltrim($normalizedPath, '/') : '';
    }

    private function deleteUploadedFile(string $relativePath): void
    {
        $relativePath = $this->normalizeRelativeUploadPath($relativePath);
        if ($relativePath === '') {
            return;
        }

        legacy_delete_upload_file($relativePath);
    }

    private function getUploadErrorMessage(int $errorCode, string $label = 'Bukti bayar'): string
    {
        return match ($errorCode) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'Ukuran ' . strtolower($label) . ' terlalu besar',
            UPLOAD_ERR_PARTIAL => $label . ' gagal terunggah secara utuh',
            UPLOAD_ERR_NO_FILE => $label . ' wajib diunggah',
            default => 'Terjadi kendala saat mengunggah ' . strtolower($label),
        };
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
