<?php

class ReservasiController extends Controller
{
    public function index()
    {
        require_admin_menu_access('data.reservasi');

        csrf_token();

        $admin = admin_user() ?? [];
        $roleContext = resolve_admin_role_context($admin);
        $districtId = (int) ($admin['district_id'] ?? 0);

        $reservasiModel = $this->model('Reservasi');
        $allReservations = $this->appendIdentityUrls(
            $this->getScopedReservations($reservasiModel, $roleContext, $districtId)
        );
        $queueReservations = array_values(array_filter($allReservations, function (array $reservation): bool {
            return reservation_status_display_key($reservation['status'] ?? '') === 'RESERVASI BARU';
        }));

        $summaryCards = [
            [
                'label' => 'Reservasi Baru',
                'value' => count($queueReservations),
                'tone' => 'warning',
                'icon' => 'ti ti-loader-2',
            ],
            [
                'label' => 'Kerjasama UMKM',
                'value' => $this->countByStatuses($allReservations, ['KERJASAMA UMKM']),
                'tone' => 'info',
                'icon' => 'ti ti-building-store',
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
        ];

        $error = $_SESSION['error'] ?? '';
        $success = $_SESSION['success'] ?? '';
        unset($_SESSION['error'], $_SESSION['success']);

        $this->view('admin.reservasi.index', [
            'title' => 'Reservasi - SIGAP',
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

    public function gedung()
    {
        require_admin_menu_access('data.riwayat.gedung');

        csrf_token();

        $admin = admin_user() ?? [];
        $roleContext = resolve_admin_role_context($admin);
        $districtId = (int) ($admin['district_id'] ?? 0);

        $reservasiModel = $this->model('Reservasi');
        $reservations = $this->appendIdentityUrls(
            $this->getScopedReservations($reservasiModel, $roleContext, $districtId)
        );

        $summaryCards = $this->buildHistorySummaryCards($reservations);

        $error = $_SESSION['error'] ?? '';
        $success = $_SESSION['success'] ?? '';
        unset($_SESSION['error'], $_SESSION['success']);

        $this->view('admin.riwayat.gedung', [
            'title' => 'Riwayat Gedung - SIGAP',
            'admin' => $admin,
            'roleContext' => $roleContext,
            'reservations' => $reservations,
            'summaryCards' => $summaryCards,
            'statusClasses' => $this->getStatusClasses(),
            'canDeleteReservation' => $this->canDeleteReservation($admin),
            'messages' => [
                'success' => $success,
                'error' => $error,
            ],
        ]);
    }

    public function umkm()
    {
        require_admin_menu_access('data.riwayat.umkm');

        csrf_token();

        $admin = admin_user() ?? [];
        $roleContext = resolve_admin_role_context($admin);
        $districtId = (int) ($admin['district_id'] ?? 0);

        $reservasiModel = $this->model('Reservasi');
        $reservations = $this->appendIdentityUrls(
            $this->getScopedReservations($reservasiModel, $roleContext, $districtId)
        );
        $reservations = array_values(array_filter($reservations, static function (array $reservation): bool {
            return (int) ($reservation['umkm_id'] ?? 0) > 0;
        }));

        $summaryCards = $this->buildHistorySummaryCards($reservations);

        $error = $_SESSION['error'] ?? '';
        $success = $_SESSION['success'] ?? '';
        unset($_SESSION['error'], $_SESSION['success']);

        $this->view('admin.riwayat.umkm', [
            'title' => 'Riwayat UMKM - SIGAP',
            'admin' => $admin,
            'roleContext' => $roleContext,
            'reservations' => $reservations,
            'summaryCards' => $summaryCards,
            'statusClasses' => $this->getStatusClasses(),
            'canDeleteReservation' => $this->canDeleteReservation($admin),
            'messages' => [
                'success' => $success,
                'error' => $error,
            ],
        ]);
    }

    public function approve()
    {
        require_admin_menu_access('data.reservasi');
        verify_csrf();

        $admin = admin_user() ?? [];
        $roleContext = resolve_admin_role_context($admin);
        $districtId = (int) ($admin['district_id'] ?? 0);
        $reservationId = (int) ($_POST['reservation_id'] ?? 0);

        if ($reservationId <= 0) {
            $_SESSION['error'] = 'Reservasi yang dipilih tidak valid';
            $this->redirect('/admin/reservasi');
            return;
        }

        $reservasiModel = $this->model('Reservasi');
        $reservation = $reservasiModel->findDetailed($reservationId);

        if (!$reservation || !$this->canAccessReservation($reservation, $roleContext, $districtId)) {
            $_SESSION['error'] = 'Data reservasi tidak ditemukan pada cakupan akses Anda';
            $this->redirect('/admin/reservasi');
            return;
        }

        $status = reservation_status_display_key($reservation['status'] ?? '');
        if ($status !== 'RESERVASI BARU') {
            $_SESSION['error'] = 'Hanya reservasi berstatus Reservasi Baru yang dapat dilanjutkan ke tahap Kerjasama UMKM';
            $this->redirect('/admin/reservasi');
            return;
        }

        if (!$reservasiModel->updateStatus($reservationId, 'KERJASAMA UMKM')) {
            $_SESSION['error'] = 'Reservasi gagal dilanjutkan ke tahap Kerjasama UMKM. Silakan coba lagi';
            $this->redirect('/admin/reservasi');
            return;
        }

        $_SESSION['success'] = 'Reservasi berhasil dilanjutkan dan statusnya menjadi Kerjasama UMKM';
        $this->redirect('/admin/reservasi');
    }

    public function returnToApplicant()
    {
        require_admin_menu_access('data.reservasi');
        verify_csrf();

        $admin = admin_user() ?? [];
        $roleContext = resolve_admin_role_context($admin);
        $districtId = (int) ($admin['district_id'] ?? 0);
        $reservationId = (int) ($_POST['reservation_id'] ?? 0);
        $returnNote = trim((string) ($_POST['rejection_note'] ?? ''));

        if ($reservationId <= 0) {
            $_SESSION['error'] = 'Reservasi yang dipilih tidak valid';
            $this->redirect('/admin/reservasi');
            return;
        }

        if ($returnNote === '') {
            $_SESSION['error'] = 'Catatan pengembalian wajib diisi sebelum reservasi dapat dikembalikan';
            $this->redirect('/admin/reservasi');
            return;
        }

        $reservasiModel = $this->model('Reservasi');
        $reservation = $reservasiModel->findDetailed($reservationId);

        if (!$reservation || !$this->canAccessReservation($reservation, $roleContext, $districtId)) {
            $_SESSION['error'] = 'Data reservasi tidak ditemukan pada cakupan akses Anda';
            $this->redirect('/admin/reservasi');
            return;
        }

        $status = reservation_status_display_key($reservation['status'] ?? '');
        if ($status !== 'RESERVASI BARU') {
            $_SESSION['error'] = 'Hanya reservasi berstatus Reservasi Baru yang dapat dikembalikan ke pemohon';
            $this->redirect('/admin/reservasi');
            return;
        }

        if (!$reservasiModel->updateStatusWithMetadata($reservationId, 'BERKAS RESERVASI TIDAK SESUAI', $returnNote, 1, 1)) {
            $_SESSION['error'] = 'Reservasi gagal dikembalikan ke pemohon. Silakan coba lagi';
            $this->redirect('/admin/reservasi');
            return;
        }

        $_SESSION['success'] = 'Reservasi berhasil dikembalikan ke pemohon dan statusnya menjadi Berkas Reservasi Tidak Sesuai';
        $this->redirect('/admin/reservasi');
    }

    public function reject()
    {
        require_admin_menu_access('data.reservasi');
        verify_csrf();

        $admin = admin_user() ?? [];
        $roleContext = resolve_admin_role_context($admin);
        $districtId = (int) ($admin['district_id'] ?? 0);
        $reservationId = (int) ($_POST['reservation_id'] ?? 0);
        $rejectionNote = trim((string) ($_POST['rejection_note'] ?? ''));

        if ($reservationId <= 0) {
            $_SESSION['error'] = 'Reservasi yang dipilih tidak valid';
            $this->redirect('/admin/reservasi');
            return;
        }

        if ($rejectionNote === '') {
            $_SESSION['error'] = 'Catatan penolakan wajib diisi sebelum permohonan dapat ditolak';
            $this->redirect('/admin/reservasi');
            return;
        }

        $reservasiModel = $this->model('Reservasi');
        $reservation = $reservasiModel->findDetailed($reservationId);

        if (!$reservation || !$this->canAccessReservation($reservation, $roleContext, $districtId)) {
            $_SESSION['error'] = 'Data reservasi tidak ditemukan pada cakupan akses Anda';
            $this->redirect('/admin/reservasi');
            return;
        }

        $status = reservation_status_display_key($reservation['status'] ?? '');
        if ($status !== 'RESERVASI BARU') {
            $_SESSION['error'] = 'Hanya reservasi berstatus Reservasi Baru yang dapat ditolak';
            $this->redirect('/admin/reservasi');
            return;
        }

        if (!$reservasiModel->updateStatusWithMetadata($reservationId, 'PERMOHONAN DITOLAK', $rejectionNote, 0, 0)) {
            $_SESSION['error'] = 'Reservasi gagal ditolak. Silakan coba lagi';
            $this->redirect('/admin/reservasi');
            return;
        }

        $_SESSION['success'] = 'Reservasi berhasil ditolak dan statusnya menjadi Permohonan Ditolak';
        $this->redirect('/admin/reservasi');
    }

    public function destroy()
    {
        $this->destroyHistoryReservation('data.riwayat.gedung', '/admin/riwayat/gedung');
    }

    public function destroyUmkm()
    {
        $this->destroyHistoryReservation('data.riwayat.umkm', '/admin/riwayat/umkm');
    }

    private function countByStatuses(array $reservations, array $statuses): int
    {
        return count(array_filter($reservations, function (array $reservation) use ($statuses): bool {
            return reservation_status_matches($reservation['status'] ?? '', $statuses);
        }));
    }

    private function buildHistorySummaryCards(array $reservations): array
    {
        return [
            [
                'label' => 'Total Ajuan',
                'value' => count($reservations),
                'tone' => 'info',
                'icon' => 'assets/custom/images/svgs/icon-reservasi.svg',
            ],
            [
                'label' => 'Reservasi Baru / Kerjasama / Verifikasi',
                'value' => $this->countByStatuses($reservations, [
                    'RESERVASI BARU',
                    'BERKAS RESERVASI TIDAK SESUAI',
                    'KERJASAMA UMKM',
                    'PROSES VERIFIKASI',
                    'BERKAS VERIFIKASI TIDAK SESUAI',
                ]),
                'tone' => 'warning',
                'icon' => 'assets/custom/images/svgs/icon-proses.svg',
            ],
            [
                'label' => 'Menunggu / Cek Pembayaran',
                'value' => $this->countByStatuses($reservations, ['MENUNGGU PEMBAYARAN', 'CEK PEMBAYARAN', 'BERKAS PEMBAYARAN TIDAK SESUAI']),
                'tone' => 'primary',
                'icon' => 'assets/custom/images/svgs/icon-setuju.svg',
            ],
            [
                'label' => 'Permohonan Ditolak',
                'value' => $this->countByStatuses($reservations, ['PERMOHONAN DITOLAK']),
                'tone' => 'danger',
                'icon' => 'assets/custom/images/svgs/icon-tolak.svg',
            ],
            [
                'label' => 'Pembayaran Lunas',
                'value' => $this->countByStatuses($reservations, ['PEMBAYARAN LUNAS']),
                'tone' => 'success',
                'icon' => 'assets/custom/images/svgs/icon-bayar.svg',
            ],
            [
                'label' => 'Dibatalkan Pemohon',
                'value' => $this->countByStatuses($reservations, ['DIBATALKAN PEMOHON']),
                'tone' => 'danger',
                'icon' => 'assets/custom/images/svgs/icon-batal.svg',
            ],
            [
                'label' => 'Acara Selesai',
                'value' => $this->countByStatuses($reservations, ['ACARA SELESAI']),
                'tone' => 'dark',
                'icon' => 'assets/custom/images/svgs/icon-selesai.svg',
            ],
            [
                'label' => 'Berkas Tidak Sesuai',
                'value' => $this->countByStatuses($reservations, [
                    'BERKAS RESERVASI TIDAK SESUAI',
                    'BERKAS VERIFIKASI TIDAK SESUAI',
                    'BERKAS PEMBAYARAN TIDAK SESUAI',
                    'BERKAS TIDAK SESUAI',
                ]),
                'tone' => 'dark',
                'icon' => 'assets/custom/images/svgs/icon-verif.svg',
            ],
        ];
    }

    private function canDeleteReservation(array $admin): bool
    {
        $roleId = (int) ($admin['role_id'] ?? 0);

        return in_array($roleId, [1, 2], true);
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

    private function destroyHistoryReservation(string $accessKey, string $redirectPath): void
    {
        require_admin_menu_access($accessKey);
        verify_csrf();

        $admin = admin_user() ?? [];

        if (!$this->canDeleteReservation($admin)) {
            $_SESSION['error'] = 'Hanya Super Admin dan Admin yang dapat menghapus reservasi';
            $this->redirect($redirectPath);
            return;
        }

        $reservationId = (int) ($_POST['reservation_id'] ?? 0);
        if ($reservationId <= 0) {
            $_SESSION['error'] = 'Reservasi yang dipilih tidak valid';
            $this->redirect($redirectPath);
            return;
        }

        $reservasiModel = $this->model('Reservasi');
        $reservation = $reservasiModel->findDetailed($reservationId);

        if (!$reservation) {
            $_SESSION['error'] = 'Data reservasi tidak ditemukan';
            $this->redirect($redirectPath);
            return;
        }

        $deleted = $reservasiModel->deleteById($reservationId);
        if (!$deleted) {
            $_SESSION['error'] = 'Reservasi gagal dihapus. Silakan coba lagi';
            $this->redirect($redirectPath);
            return;
        }

        $this->deleteUploadedIdentityFile((string) ($reservation['id_path'] ?? ''));

        $_SESSION['success'] = 'Reservasi berhasil dihapus';
        $this->redirect($redirectPath);
    }

    private function deleteUploadedIdentityFile(string $relativePath): void
    {
        $relativePath = $this->normalizeRelativeUploadPath($relativePath);
        if ($relativePath === '') {
            return;
        }

        legacy_delete_upload_file($relativePath);
    }
}
