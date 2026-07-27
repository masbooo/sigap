<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
class VerifikasiController extends Controller
{
    public function index()
    {
        require_admin_menu_access('data.verifikasi');

        csrf_token();

        $admin = admin_user() ?? [];
        $roleContext = resolve_admin_role_context($admin);
        $districtId = (int) ($admin['district_id'] ?? 0);

        $reservasiModel = $this->model('Reservasi');
        $allReservations = $this->appendIdentityUrls(
            $this->getScopedReservations($reservasiModel, $roleContext, $districtId)
        );
        $queueReservations = array_values(array_filter($allReservations, function (array $reservation): bool {
            $status = reservation_status_display_key($reservation['status'] ?? '');

            return $status === 'PROSES VERIFIKASI';
        }));

        $summaryCards = [
            [
                'label' => 'Proses Verifikasi',
                'value' => count($queueReservations),
                'tone' => 'warning',
                'icon' => 'ti ti-clipboard-check',
            ],
            [
                'label' => 'Pembayaran / Selesai',
                'value' => $this->countByStatuses($allReservations, [
                    'MENUNGGU PEMBAYARAN',
                    'CEK PEMBAYARAN',
                    'BERKAS PEMBAYARAN TIDAK SESUAI',
                    'PEMBAYARAN LUNAS',
                    'ACARA SELESAI',
                ]),
                'tone' => 'success',
                'icon' => 'ti ti-checklist',
            ],
            [
                'label' => 'Ditolak / Dibatalkan',
                'value' => $this->countByStatuses($allReservations, ['PERMOHONAN DITOLAK', 'DIBATALKAN PEMOHON']),
                'tone' => 'danger',
                'icon' => 'ti ti-xbox-x',
            ],
            [
                'label' => 'Total Data Cakupan',
                'value' => count($allReservations),
                'tone' => 'primary',
                'icon' => 'ti ti-database',
            ],
        ];

        $error = session()->pull('error', '');
        $success = session()->pull('success', '');

        $this->view('admin.verifikasi.index', [
            'title' => 'Proses Verifikasi - SIGAP',
            'admin' => $admin,
            'roleContext' => $roleContext,
            'reservations' => $queueReservations,
            'summaryCards' => $summaryCards,
            'statusClasses' => $this->getStatusClasses(),
            'messages' => [
                'success' => $success,
                'error' => $error,
            ],
        ]);
    }

    public function approve()
    {
        $this->updateVerificationStatus('MENUNGGU PEMBAYARAN', 'Reservasi berhasil dilanjutkan dan statusnya menjadi Menunggu Pembayaran');
    }

    public function returnToApplicant()
    {
        $this->updateVerificationStatus('BERKAS VERIFIKASI TIDAK SESUAI', 'Reservasi berhasil dikembalikan ke pemohon dengan status Berkas Verifikasi Tidak Sesuai');
    }

    private function updateVerificationStatus(string $nextStatus, string $successMessage): void
    {
        require_admin_menu_access('data.verifikasi');
        verify_csrf();

        $admin = admin_user() ?? [];
        $roleContext = resolve_admin_role_context($admin);
        $districtId = (int) ($admin['district_id'] ?? 0);
        $reservationId = (int) request('reservation_id', 0);

        if ($reservationId <= 0) {
            session(['error' => 'Reservasi yang dipilih tidak valid']);
            $this->redirect('/admin/verifikasi');
            return;
        }

        $reservasiModel = $this->model('Reservasi');
        $reservation = $reservasiModel->findDetailed($reservationId);

        if (!$reservation || !$this->canAccessReservation($reservation, $roleContext, $districtId)) {
            session(['error' => 'Data reservasi tidak ditemukan pada cakupan akses Anda']);
            $this->redirect('/admin/verifikasi');
            return;
        }

        $status = reservation_status_display_key($reservation['status'] ?? '');
        if ($status !== 'PROSES VERIFIKASI') {
            session(['error' => 'Reservasi ini sudah tidak berada pada antrean proses verifikasi']);
            $this->redirect('/admin/verifikasi');
            return;
        }

        $updated = false;

        if ($nextStatus === 'BERKAS VERIFIKASI TIDAK SESUAI') {
            $returnNote = trim((string) request('rejection_note', ''));

            if ($returnNote === '') {
                session(['error' => 'Catatan pengembalian wajib diisi sebelum reservasi dapat dikembalikan']);
                $this->redirect('/admin/verifikasi');
                return;
            }

            $updated = $reservasiModel->updateStatusWithMetadata(
                $reservationId,
                $nextStatus,
                $returnNote,
                1,
                1
            );
        } else {
            $updated = $reservasiModel->updateStatus($reservationId, $nextStatus);
        }

        if (!$updated) {
            session(['error' => 'Status reservasi gagal diperbarui. Silakan coba lagi']);
            $this->redirect('/admin/verifikasi');
            return;
        }

        session(['success' => $successMessage]);
        $this->redirect('/admin/verifikasi');
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

    private function getStatusClasses(): array
    {
        return reservation_status_class_lookup();
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
