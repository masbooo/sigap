<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
class PembayaranController extends Controller
{
    public function index()
    {
        require_admin_menu_access('data.pembayaran');

        csrf_token();

        $admin = admin_user() ?? [];
        $roleContext = resolve_admin_role_context($admin);
        $districtId = (int) ($admin['district_id'] ?? 0);

        $reservasiModel = $this->model('Reservasi');
        $paymentReservations = $this->appendIdentityUrls(
            $this->getScopedReservations($reservasiModel, $roleContext, $districtId, [
                'MENUNGGU PEMBAYARAN',
                'CEK PEMBAYARAN',
                'PEMBAYARAN LUNAS',
                'ACARA SELESAI',
            ])
        );
        $reservations = array_values(array_filter(
            $paymentReservations,
            static function (array $reservation): bool {
                return reservation_status_matches($reservation['status'] ?? '', ['MENUNGGU PEMBAYARAN', 'CEK PEMBAYARAN']);
            }
        ));

        $summaryCards = [
            [
                'label' => 'Total Data',
                'value' => count($paymentReservations),
                'tone' => 'primary',
                'icon' => 'ti ti-database',
            ],
            [
                'label' => 'Menunggu Pembayaran',
                'value' => $this->countByStatuses($paymentReservations, ['MENUNGGU PEMBAYARAN']),
                'tone' => 'primary',
                'icon' => 'ti ti-receipt-2',
            ],
            [
                'label' => 'Cek Pembayaran',
                'value' => $this->countByStatuses($paymentReservations, ['CEK PEMBAYARAN']),
                'tone' => 'warning',
                'icon' => 'ti ti-search',
            ],
            [
                'label' => 'Pembayaran Lunas',
                'value' => $this->countByStatuses($paymentReservations, ['PEMBAYARAN LUNAS']),
                'tone' => 'success',
                'icon' => 'ti ti-cash',
            ],
        ];

        $error = session()->pull('error', '');
        $success = session()->pull('success', '');

        $this->view('admin.pembayaran.index', [
            'title' => 'Pembayaran - SIGAP',
            'admin' => $admin,
            'roleContext' => $roleContext,
            'reservations' => $reservations,
            'summaryCards' => $summaryCards,
            'statusClasses' => $this->getStatusClasses(),
            'messages' => [
                'success' => $success,
                'error' => $error,
            ],
        ]);
    }

    public function markAsPaid()
    {
        require_admin_menu_access('data.pembayaran');
        verify_csrf();

        $admin = admin_user() ?? [];
        $roleContext = resolve_admin_role_context($admin);
        $districtId = (int) ($admin['district_id'] ?? 0);
        $reservationId = (int) request('reservation_id', 0);

        if ($reservationId <= 0) {
            session(['error' => 'Reservasi yang dipilih tidak valid']);
            $this->redirect('/admin/pembayaran');
            return;
        }

        $reservasiModel = $this->model('Reservasi');
        $reservation = $reservasiModel->findDetailed($reservationId);

        if (!$reservation || !$this->canAccessReservation($reservation, $roleContext, $districtId)) {
            session(['error' => 'Data reservasi tidak ditemukan pada cakupan akses Anda']);
            $this->redirect('/admin/pembayaran');
            return;
        }

        if (!reservation_status_matches($reservation['status'] ?? '', ['CEK PEMBAYARAN'])) {
            session(['error' => 'Hanya reservasi berstatus Cek Pembayaran yang dapat ditandai lunas']);
            $this->redirect('/admin/pembayaran');
            return;
        }

        if (!$reservasiModel->updateStatus($reservationId, 'PEMBAYARAN LUNAS')) {
            session(['error' => 'Status pembayaran gagal diperbarui. Silakan coba lagi']);
            $this->redirect('/admin/pembayaran');
            return;
        }

        session(['success' => 'Pembayaran berhasil dikonfirmasi dan statusnya menjadi Pembayaran Lunas']);
        $this->redirect('/admin/pembayaran');
    }

    public function returnToApplicant()
    {
        require_admin_menu_access('data.pembayaran');
        verify_csrf();

        $admin = admin_user() ?? [];
        $roleContext = resolve_admin_role_context($admin);
        $districtId = (int) ($admin['district_id'] ?? 0);
        $reservationId = (int) request('reservation_id', 0);
        $returnNote = trim((string) request('rejection_note', ''));

        if ($reservationId <= 0) {
            session(['error' => 'Reservasi yang dipilih tidak valid']);
            $this->redirect('/admin/pembayaran');
            return;
        }

        if ($returnNote === '') {
            session(['error' => 'Catatan pengembalian wajib diisi sebelum pembayaran dapat dikembalikan']);
            $this->redirect('/admin/pembayaran');
            return;
        }

        $reservasiModel = $this->model('Reservasi');
        $reservation = $reservasiModel->findDetailed($reservationId);

        if (!$reservation || !$this->canAccessReservation($reservation, $roleContext, $districtId)) {
            session(['error' => 'Data reservasi tidak ditemukan pada cakupan akses Anda']);
            $this->redirect('/admin/pembayaran');
            return;
        }

        if (!reservation_status_matches($reservation['status'] ?? '', ['CEK PEMBAYARAN'])) {
            session(['error' => 'Hanya reservasi berstatus Cek Pembayaran yang dapat dikembalikan']);
            $this->redirect('/admin/pembayaran');
            return;
        }

        if (!$reservasiModel->updateStatusWithMetadata($reservationId, 'BERKAS PEMBAYARAN TIDAK SESUAI', $returnNote, 1, 1)) {
            session(['error' => 'Status pembayaran gagal dikembalikan. Silakan coba lagi']);
            $this->redirect('/admin/pembayaran');
            return;
        }

        session(['success' => 'Pembayaran berhasil dikembalikan dan statusnya menjadi Berkas Pembayaran Tidak Sesuai']);
        $this->redirect('/admin/pembayaran');
    }

    private function countByStatuses(array $reservations, array $statuses): int
    {
        return count(array_filter($reservations, function (array $reservation) use ($statuses): bool {
            return reservation_status_matches($reservation['status'] ?? '', $statuses);
        }));
    }

    private function getScopedReservations(Reservasi $reservasiModel, array $roleContext, int $districtId, array $statuses = []): array
    {
        if (($roleContext['scope_type'] ?? 'all') === 'district' && $districtId > 0) {
            return $reservasiModel->allDetailed($districtId, $statuses);
        }

        return $reservasiModel->allDetailed(null, $statuses);
    }

    private function getStatusClasses(): array
    {
        return reservation_status_class_lookup();
    }

    private function canAccessReservation(array $reservation, array $roleContext, int $districtId): bool
    {
        if (($roleContext['scope_type'] ?? 'all') !== 'district') {
            return true;
        }

        if ($districtId <= 0) {
            return false;
        }

        return (int) ($reservation['district_id'] ?? 0) === $districtId;
    }

    private function appendIdentityUrls(array $reservations): array
    {
        foreach ($reservations as &$reservation) {
            $identityRelativePath = $this->normalizeRelativeUploadPath((string) ($reservation['id_path'] ?? ''));
            $applicationRelativePath = $this->normalizeRelativeUploadPath((string) ($reservation['form_path'] ?? ''));
            $umkmRelativePath = $this->normalizeRelativeUploadPath((string) ($reservation['umkm_path'] ?? ''));
            $paymentRelativePath = $this->normalizeRelativeUploadPath((string) ($reservation['payment_proof_path'] ?? ''));

            $reservation['identity_file_url'] = $identityRelativePath !== ''
                ? asset('assets/upload/' . ltrim($identityRelativePath, '/'))
                : '';
            $reservation['application_file_url'] = $applicationRelativePath !== ''
                ? asset('assets/upload/' . ltrim($applicationRelativePath, '/'))
                : '';
            $reservation['umkm_file_url'] = $umkmRelativePath !== ''
                ? asset('assets/upload/' . ltrim($umkmRelativePath, '/'))
                : '';
            $reservation['payment_file_url'] = $paymentRelativePath !== ''
                ? asset('assets/upload/' . ltrim($paymentRelativePath, '/'))
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
}
