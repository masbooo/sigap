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

        $error = session()->pull('error', '');
        $success = session()->pull('success', '');

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

        $reservationId = (int) request('reservation_id', 0);
        if ($reservationId <= 0) {
            session(['error' => 'Tagihan yang dipilih tidak valid']);
            $this->redirect('/user/pembayaran');
            return;
        }

        $reservasiModel = $this->model('Reservasi');
        $pembayaranModel = $this->model('Pembayaran');
        $reservation = $reservasiModel->findByUserId($reservationId, (int) ($user['id'] ?? 0));

        if (!$reservation) {
            session(['error' => 'Data tagihan tidak ditemukan']);
            $this->redirect('/user/pembayaran');
            return;
        }

        if (!reservation_status_matches($reservation['status'] ?? '', ['MENUNGGU PEMBAYARAN', 'BERKAS PEMBAYARAN TIDAK SESUAI'])) {
            session(['error' => 'Hanya tagihan berstatus Menunggu Pembayaran atau Berkas Pembayaran Tidak Sesuai yang dapat diunggah']);
            $this->redirect('/user/pembayaran');
            return;
        }

        $paymentFile = request()->file('payment_file');
        $paymentUploadErrorCode = (int) ($_FILES['payment_file']['error'] ?? UPLOAD_ERR_NO_FILE);

        if (!$paymentFile || $paymentUploadErrorCode !== UPLOAD_ERR_OK) {
            session(['error' => $this->getUploadErrorMessage($paymentUploadErrorCode, 'Bukti bayar')]);
            $this->redirect('/user/pembayaran');
            return;
        }

        $uploadedFilename = upload_file($_FILES['payment_file'], 'reservasi/pembayaran');
        if ($uploadedFilename === null) {
            session(['error' => 'Bukti bayar gagal diunggah. Pastikan format file JPG, JPEG, PNG, atau PDF']);
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
            session(['error' => 'Bukti bayar gagal disimpan. Silakan coba lagi']);
            $this->redirect('/user/pembayaran');
            return;
        }

        if (!$reservasiModel->updateStatusWithMetadata($reservationId, 'CEK PEMBAYARAN', '', 1, 0)) {
            $pembayaranModel->deleteByReservationAndProofPath($reservationId, $relativePaymentPath);
            $this->deleteUploadedFile($relativePaymentPath);
            session(['error' => 'Status pembayaran gagal diperbarui. Silakan coba lagi']);
            $this->redirect('/user/pembayaran');
            return;
        }

        session(['success' => 'Bukti bayar berhasil diunggah dan statusnya menjadi Cek Pembayaran']);
        $this->redirect('/user/pembayaran');
    }

    private function appendReservationFileUrls(array $reservations): array
    {
        foreach ($reservations as &$reservation) {
            $identityRelativePath = $this->normalizeRelativeUploadPath((string) ($reservation['id_path'] ?? ''));
            $applicationRelativePath = $this->normalizeRelativeUploadPath((string) ($reservation['form_path'] ?? ''));
            $umkmRelativePath = $this->normalizeRelativeUploadPath((string) ($reservation['umkm_path'] ?? ''));
            $paymentRelativePath = $this->normalizeRelativeUploadPath((string) ($reservation['proof_path'] ?? ''));

            $reservation['id_url'] = $identityRelativePath !== '' ? upload_url($identityRelativePath) : null;
            $reservation['form_url'] = $applicationRelativePath !== '' ? upload_url($applicationRelativePath) : null;
            $reservation['umkm_url'] = $umkmRelativePath !== '' ? upload_url($umkmRelativePath) : null;
            $reservation['proof_url'] = $paymentRelativePath !== '' ? upload_url($paymentRelativePath) : null;
        }
        unset($reservation);

        return $reservations;
    }

    private function normalizeRelativeUploadPath(string $path): string
    {
        $path = trim($path);
        if ($path === '') {
            return '';
        }

        return preg_replace('#^uploads/#', '', $path);
    }

    private function deleteUploadedFile(string $relativePath): void
    {
        $relativePath = $this->normalizeRelativeUploadPath($relativePath);
        if ($relativePath === '') {
            return;
        }

        $fullPath = uploads_path($relativePath);
        if (file_exists($fullPath) && is_file($fullPath)) {
            @unlink($fullPath);
        }
    }

    private function getUploadErrorMessage(int $errorCode, string $label = 'File'): string
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
