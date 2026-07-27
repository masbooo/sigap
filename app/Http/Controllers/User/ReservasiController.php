<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Services\ReservationApplicationPdf;
use App\Services\ReservationPaymentPdf;
use App\Supports\Payment\PaymentTestGateway;
use DateTime;
use Illuminate\Http\Exceptions\HttpResponseException;
use Throwable;

class ReservasiController extends Controller
{
    public function index(?string $editId = null)
    {
        $user = $this->requireAuthenticatedUser();
        if ($user === null) {
            return;
        }

        csrf_token();
        $viewData = $this->getReservationViewData($user, false, $this->resolveRouteReservationId($editId));
        if ($viewData === null) {
            return;
        }

        $viewData['title'] = 'Reservasi User';

        $this->view('user.reservasi.index', $viewData);
    }

    public function panel(?string $editId = null)
    {
        $user = $this->requireAuthenticatedUser();
        if ($user === null) {
            return;
        }

        csrf_token();

        $viewData = $this->getReservationViewData($user, true, $this->resolveRouteReservationId($editId));
        if ($viewData === null) {
            return;
        }

        $this->view('user.reservasi.partials.panel', $viewData);
    }

    public function printApplication()
    {
        $user = $this->requireAuthenticatedUser();
        if ($user === null) {
            return;
        }

        verify_csrf();

        $documentData = $this->buildReservationApplicationDocumentData($user, request()->input());
        if ($documentData === null) {
            return;
        }

        $filename = 'permohonan-reservasi-' . date('Ymd-His') . '.pdf';
        $pdf = new ReservationApplicationPdf();
        $pdf->outputInline($documentData, $filename);
    }

    public function printPaymentDocument(?string $id = null, ?string $filename = null)
    {
        $user = $this->requireAuthenticatedUser();
        if ($user === null) {
            return;
        }

        $reservationId = (int) trim((string) $id);
        $method = $this->normalizePaymentDocumentMethod((string) request()->query('method', ''));
        $isDownloadMode = (string) request()->query('download', '') === '1';

        if ($reservationId <= 0 || $method === '') {
            session(['error' => 'Dokumen pembayaran yang dipilih tidak valid']);
            $this->redirect('/user/reservasi');
            return;
        }

        $reservasiModel = $this->model('Reservasi');
        $reservation = $reservasiModel->findDetailed($reservationId);

        if (!$reservation || (int) ($reservation['user_id'] ?? 0) !== (int) ($user['id'] ?? 0)) {
            session(['error' => 'Data reservasi tidak ditemukan']);
            $this->redirect('/user/reservasi');
            return;
        }

        if (!reservation_status_matches($reservation['status'] ?? '', ['MENUNGGU PEMBAYARAN'])) {
            session(['error' => 'Dokumen pembayaran hanya tersedia untuk reservasi berstatus Menunggu Pembayaran']);
            $this->redirect('/user/reservasi');
            return;
        }

        $payment = $this->paymentGateway()->findActivePayment($reservationId, $method);
        if ($payment === null) {
            session(['error' => 'Metode pembayaran belum diproses atau sudah kedaluwarsa']);
            $this->redirect('/user/reservasi');
            return;
        }

        $document = $this->buildReservationPaymentDocumentData($user, $reservation, $method, $payment);
        $viewData = [
            'title' => $method === 'va' ? 'Preview Virtual Account' : 'Preview QRIS',
            'document' => $document,
            'isDownloadMode' => $isDownloadMode,
            'downloadUrl' => $this->buildPaymentDocumentPreviewUrl($reservationId, $document, true),
        ];

        if ($method === 'va') {
            $filename = $this->buildPaymentDocumentDownloadFilename($document);
            $pdf = new ReservationPaymentPdf();

            if ($isDownloadMode) {
                $pdf->outputDownload($document, $filename);
                return;
            }

            $pdf->outputInline($document, $filename);
            return;
        }

        if ($isDownloadMode) {
            $filename = $this->buildPaymentDocumentDownloadFilename($document);

            if ($method === 'qris') {
                $svg = $this->renderQrisPaymentDocumentSvg($document);

                throw new HttpResponseException(
                    response($svg)
                        ->header('Content-Type', 'image/svg+xml; charset=UTF-8')
                        ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
                        ->header('Content-Length', (string) strlen($svg))
                        ->header('Cache-Control', 'private, max-age=0, must-revalidate')
                        ->header('Pragma', 'public')
                );
            }

            $html = $this->renderViewToString('user.reservasi.partials.invoice', $viewData);
            $contentType = $method === 'va'
                ? 'application/msword; charset=UTF-8'
                : 'text/html; charset=UTF-8';

            throw new HttpResponseException(
                response($html)
                    ->header('Content-Type', $contentType)
                    ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
                    ->header('Content-Length', (string) strlen($html))
                    ->header('Cache-Control', 'private, max-age=0, must-revalidate')
                    ->header('Pragma', 'public')
            );
        }

        $this->view('user.reservasi.partials.invoice', $viewData);
    }

    public function processPaymentMethod()
    {
        $user = $this->requireAuthenticatedUser();
        if ($user === null) {
            return;
        }

        verify_csrf();

        $reservationId = (int) request()->input('reservation_id', 0);
        $method = $this->normalizePaymentDocumentMethod((string) request()->input('method', ''));

        if ($reservationId <= 0 || $method === '') {
            $this->respondReservationPaymentJson(false, 'Metode pembayaran yang dipilih tidak valid', [], 422);
        }

        if ($method !== 'va') {
            $this->respondReservationPaymentJson(false, 'Metode QRIS belum tersedia untuk mode testing saat ini', [], 422);
        }

        $reservation = $this->findUserPaymentReservation($reservationId, $user);
        if ($reservation === null) {
            return;
        }

        try {
            $payment = $this->paymentGateway()->requestVirtualAccount($reservation);
        } catch (Throwable $exception) {
            $this->respondReservationPaymentJson(false, $exception->getMessage() ?: 'Virtual Account gagal dibuat', [], 500);
        }

        $this->respondReservationPaymentJson(true, 'Virtual Account berhasil dibuat', [
            'payment' => $this->buildReservationPaymentState($user, $reservation, $payment),
        ]);
    }

    public function revisePaymentMethod()
    {
        $user = $this->requireAuthenticatedUser();
        if ($user === null) {
            return;
        }

        verify_csrf();

        $reservationId = (int) request()->input('reservation_id', 0);
        if ($reservationId <= 0) {
            $this->respondReservationPaymentJson(false, 'Reservasi pembayaran tidak valid', [], 422);
        }

        $reservation = $this->findUserPaymentReservation($reservationId, $user);
        if ($reservation === null) {
            return;
        }

        $this->paymentGateway()->cancelActivePayments($reservationId);

        $this->respondReservationPaymentJson(true, 'Metode pembayaran berhasil direvisi', [
            'payment' => $this->buildEmptyReservationPaymentState($reservation),
        ]);
    }

    public function store()
    {
        $user = $this->requireAuthenticatedUser();
        if ($user === null) {
            return;
        }

        verify_csrf();

        require_once base_path('app/Supports/Upload/UploadFile.php');

        $userModel = $this->model('User');
        $reservasiModel = $this->model('Reservasi');
        $umkmModel = $this->model('Umkm');

        if ($userModel->hasPendingProfileStatus($user)) {
            session(['error' => 'Lengkapi profil Anda terlebih dahulu di Dasbor sebelum mengajukan reservasi']);
            $this->redirect('/user/dasbor');
            return;
        }

        $buildingId = (int) request()->input('building_id', 0);
        $eventId = (int) request()->input('event_id', 0);
        $umkmId = (int) request()->input('umkm_id', 0);
        $sessionOption = trim((string) request()->input('session_option', ''));
        $startDate = trim((string) request()->input('start_date', ''));
        $endDate = trim((string) request()->input('end_date', $startDate));
        $startTime = trim((string) request()->input('start_time', ''));
        $endTime = trim((string) request()->input('end_time', ''));
        $estPerson = (int) request()->input('est_person', 0);
        session(['old_reservasi' => [
            'building_id' => $buildingId,
            'event_id' => $eventId,
            'umkm_id' => $umkmId,
            'session_option' => $sessionOption,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'est_person' => $estPerson,
        ]]);

        if (
            $buildingId <= 0 ||
            $eventId <= 0 ||
            $sessionOption === '' ||
            $startDate === '' ||
            $endDate === '' ||
            $estPerson <= 0
        ) {
            session(['error' => 'Semua field wajib terisi. Cek kembali']);
            $this->redirect('/user/reservasi');
            return;
        }

        if (!$this->isValidDate($startDate) || !$this->isValidDate($endDate)) {
            session(['error' => 'Format tanggal reservasi tidak valid']);
            $this->redirect('/user/reservasi');
            return;
        }

        if ($startDate !== $endDate) {
            session(['error' => 'Saat ini reservasi user hanya mendukung pemesanan untuk satu tanggal']);
            $this->redirect('/user/reservasi');
            return;
        }

        $minimumDate = (new DateTime('today'))->modify('+14 days')->format('Y-m-d');
        if ($startDate < $minimumDate) {
            session(['error' => 'Reservasi hanya dapat diajukan minimal H-14 dari tanggal pelaksanaan']);
            $this->redirect('/user/reservasi');
            return;
        }

        $building = $reservasiModel->findActiveBuildingById($buildingId);
        if (!$building) {
            session(['error' => 'Gedung yang dipilih tidak valid atau tidak aktif']);
            $this->redirect('/user/reservasi');
            return;
        }

        $requiresUmkmSelection = $this->buildingHasUmkmReservationOptions($umkmModel, $buildingId);
        if ($requiresUmkmSelection && $umkmId <= 0) {
            session(['error' => 'UMKM wajib dipilih sebelum reservasi dikirim']);
            $this->redirect('/user/reservasi');
            return;
        }

        $event = $reservasiModel->findActiveEventById($eventId);
        if (!$event) {
            session(['error' => 'Jenis acara yang dipilih tidak valid']);
            $this->redirect('/user/reservasi');
            return;
        }

        $sessionSelection = $reservasiModel->resolveSessionSelection($sessionOption, $startTime, $endTime);
        if (!$sessionSelection) {
            session(['error' => $sessionOption === 'lainnya'
                ? 'Jam mulai dan jam selesai untuk opsi Lainnya wajib diisi dengan benar'
                : 'Sesi reservasi yang dipilih tidak valid']);
            $this->redirect('/user/reservasi');
            return;
        }

        $capacity = (int) ($building['capacity'] ?? 0);
        if ($capacity > 0 && $estPerson > $capacity) {
            session(['error' => 'Estimasi orang tidak boleh 0, maksimum ' . number_format($capacity, 0, ',', '.') . ' orang']);
            $this->redirect('/user/reservasi');
            return;
        }

        if ($umkmId > 0) {
            $umkmOption = $umkmModel->findReservationOptionById($umkmId);
            if (!$umkmOption || !in_array($buildingId, $umkmOption['building_ids'] ?? [], true)) {
                session(['error' => 'UMKM yang dipilih tidak tersedia untuk gedung tersebut']);
                $this->redirect('/user/reservasi');
                return;
            }
        }

        if ($reservasiModel->hasScheduleConflict(
            $buildingId,
            $startDate,
            $endDate,
            (string) ($sessionSelection['start_time'] ?? ''),
            (string) ($sessionSelection['end_time'] ?? '')
        )) {
            session(['error' => 'Jadwal gedung pada tanggal dan sesi tersebut sudah terpakai']);
            $this->redirect('/user/reservasi');
            return;
        }

        $requestFile = $_FILES['request_file'] ?? null;
        if (!$requestFile || (int) ($requestFile['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            session(['error' => $this->getUploadErrorMessage((int) ($requestFile['error'] ?? UPLOAD_ERR_NO_FILE), 'File permohonan')]);
            $this->redirect('/user/reservasi');
            return;
        }

        $uploadedRequestFilename = upload_file($requestFile, 'reservasi/permohonan');
        if ($uploadedRequestFilename === null) {
            session(['error' => 'File permohonan gagal diunggah. Pastikan format file JPG, JPEG, PNG, atau PDF']);
            $this->redirect('/user/reservasi');
            return;
        }

        $relativeFormPath = 'reservasi/permohonan/' . $uploadedRequestFilename;
        $relativeIdPath = $this->normalizeRelativeUploadPath((string) ($user['id_path'] ?? ''));
        if ($relativeIdPath === null) {
            $this->deleteUploadedFile($relativeFormPath);
            session(['error' => 'Identitas KTP pada profil belum tersedia. Lengkapi profil terlebih dahulu sebelum mengajukan reservasi.']);
            $this->redirect('/user/reservasi');
            return;
        }
        $pricing = $reservasiModel->calculateReservationPricing($building, $sessionSelection);

        $created = $reservasiModel->create([
            'user_id' => (int) $user['id'],
            'district_id' => (int) ($building['district_id'] ?? 0),
            'building_id' => $buildingId,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'event_id' => $eventId,
            'est_person' => $estPerson,
            'umkm_id' => $umkmId > 0 ? $umkmId : null,
            'start_time' => $sessionSelection['start_time'] ?? null,
            'end_time' => $sessionSelection['end_time'] ?? null,
            'hour_count' => $pricing['hour_count'] ?? 0,
            'total_price' => $pricing['total_price'] ?? 0,
            'status' => 'RESERVASI BARU',
            'return_form' => 0,
            'id_path' => $relativeIdPath,
            'form_path' => $relativeFormPath,
        ]);

        if (!$created) {
            $this->deleteUploadedFile($relativeFormPath);

            session(['error' => 'Reservasi gagal disimpan. Silakan coba lagi']);
            $this->redirect('/user/reservasi');
            return;
        }

        session()->forget('old_reservasi');

        session(['success' => 'Reservasi berhasil diajukan dan statusnya menjadi Reservasi Baru']);
        $this->redirect('/user/reservasi');
    }

    public function update()
    {
        $user = $this->requireAuthenticatedUser();
        if ($user === null) {
            return;
        }

        verify_csrf();

        require_once base_path('app/Supports/Upload/UploadFile.php');

        $userModel = $this->model('User');
        $reservasiModel = $this->model('Reservasi');
        $umkmModel = $this->model('Umkm');

        if ($userModel->hasPendingProfileStatus($user)) {
            session(['error' => 'Lengkapi profil Anda terlebih dahulu di Dasbor sebelum mengubah reservasi']);
            $this->redirect('/user/dasbor');
            return;
        }

        $reservationId = (int) request()->input('reservation_id', 0);
        if ($reservationId <= 0) {
            session(['error' => 'Data reservasi yang ingin diubah tidak valid']);
            $this->redirect('/user/reservasi');
            return;
        }

        $reservation = $reservasiModel->findByUserId($reservationId, (int) $user['id']);
        if (!$reservation) {
            session(['error' => 'Data reservasi yang ingin diubah tidak ditemukan']);
            $this->redirect('/user/reservasi');
            return;
        }

        if (!$this->canUserEditReservation($reservation)) {
            session(['error' => 'Hanya reservasi dengan status Reservasi Baru, Kerjasama UMKM, Berkas Reservasi Tidak Sesuai, atau Berkas Verifikasi Tidak Sesuai yang dapat diubah']);
            $this->redirect('/user/reservasi');
            return;
        }

        $currentStatusKey = reservation_status_display_key($reservation['status'] ?? '');

        $buildingId = (int) request()->input('building_id', 0);
        $eventId = (int) request()->input('event_id', 0);
        $umkmId = (int) request()->input('umkm_id', 0);
        $sessionOption = trim((string) request()->input('session_option', ''));
        $startDate = trim((string) request()->input('start_date', ''));
        $endDate = trim((string) request()->input('end_date', $startDate));
        $startTime = trim((string) request()->input('start_time', ''));
        $endTime = trim((string) request()->input('end_time', ''));
        $estPerson = (int) request()->input('est_person', 0);
        session(['old_reservasi' => [
            'reservation_id' => $reservationId,
            'building_id' => $buildingId,
            'event_id' => $eventId,
            'umkm_id' => $umkmId,
            'session_option' => $sessionOption,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'est_person' => $estPerson,
        ]]);

        if (
            $buildingId <= 0 ||
            $eventId <= 0 ||
            $sessionOption === '' ||
            $startDate === '' ||
            $endDate === '' ||
            $estPerson <= 0
        ) {
            session(['error' => 'Semua field wajib terisi. Cek kembali']);
            $this->redirectToReservationForm($reservationId);
            return;
        }

        if (!$this->isValidDate($startDate) || !$this->isValidDate($endDate)) {
            session(['error' => 'Format tanggal reservasi tidak valid']);
            $this->redirectToReservationForm($reservationId);
            return;
        }

        if ($startDate !== $endDate) {
            session(['error' => 'Saat ini reservasi user hanya mendukung pemesanan untuk satu tanggal']);
            $this->redirectToReservationForm($reservationId);
            return;
        }

        $minimumDate = (new DateTime('today'))->modify('+14 days')->format('Y-m-d');
        if ($currentStatusKey === 'RESERVASI BARU' && $startDate < $minimumDate) {
            session(['error' => 'Reservasi hanya dapat diajukan minimal H-14 dari tanggal pelaksanaan']);
            $this->redirectToReservationForm($reservationId);
            return;
        }

        $building = $reservasiModel->findActiveBuildingById($buildingId);
        if (!$building) {
            session(['error' => 'Gedung yang dipilih tidak valid atau tidak aktif']);
            $this->redirectToReservationForm($reservationId);
            return;
        }

        $requiresUmkmSelection = $this->buildingHasUmkmReservationOptions($umkmModel, $buildingId);
        if ($requiresUmkmSelection && $umkmId <= 0) {
            session(['error' => 'UMKM wajib dipilih sebelum reservasi dikirim']);
            $this->redirectToReservationForm($reservationId);
            return;
        }

        $event = $reservasiModel->findActiveEventById($eventId);
        if (!$event) {
            session(['error' => 'Jenis acara yang dipilih tidak valid']);
            $this->redirectToReservationForm($reservationId);
            return;
        }

        $sessionSelection = $reservasiModel->resolveSessionSelection($sessionOption, $startTime, $endTime);
        if (!$sessionSelection) {
            session(['error' => $sessionOption === 'lainnya'
                ? 'Jam mulai dan jam selesai untuk opsi Lainnya wajib diisi dengan benar'
                : 'Sesi reservasi yang dipilih tidak valid']);
            $this->redirectToReservationForm($reservationId);
            return;
        }

        $capacity = (int) ($building['capacity'] ?? 0);
        if ($capacity > 0 && $estPerson > $capacity) {
            session(['error' => 'Estimasi orang tidak boleh 0, maksimum ' . number_format($capacity, 0, ',', '.') . ' orang']);
            $this->redirectToReservationForm($reservationId);
            return;
        }

        if ($umkmId > 0) {
            $umkmOption = $umkmModel->findReservationOptionById($umkmId);
            if (!$umkmOption || !in_array($buildingId, $umkmOption['building_ids'] ?? [], true)) {
                session(['error' => 'UMKM yang dipilih tidak tersedia untuk gedung tersebut']);
                $this->redirectToReservationForm($reservationId);
                return;
            }
        }

        if ($reservasiModel->hasScheduleConflict(
            $buildingId,
            $startDate,
            $endDate,
            (string) ($sessionSelection['start_time'] ?? ''),
            (string) ($sessionSelection['end_time'] ?? ''),
            $reservationId
        )) {
            session(['error' => 'Jadwal gedung pada tanggal dan sesi tersebut sudah terpakai']);
            $this->redirectToReservationForm($reservationId);
            return;
        }

        $currentIdentityPath = trim((string) ($reservation['id_path'] ?? ''));
        $currentFormPath = trim((string) ($reservation['form_path'] ?? ''));
        $currentUmkmPath = trim((string) ($reservation['umkm_path'] ?? ''));
        $newIdentityPath = $this->normalizeRelativeUploadPath((string) ($user['id_path'] ?? ''));
        $newFormPath = $currentFormPath;
        $newUmkmPath = $currentUmkmPath;

        if ($newIdentityPath === null) {
            session(['error' => 'Identitas KTP pada profil belum tersedia. Lengkapi profil terlebih dahulu sebelum mengubah reservasi.']);
            $this->redirectToReservationForm($reservationId);
            return;
        }

        $requestFile = $_FILES['request_file'] ?? null;
        $requestUploadErrorCode = (int) ($requestFile['error'] ?? UPLOAD_ERR_NO_FILE);
        $hasNewRequestUpload = $requestFile !== null && $requestUploadErrorCode !== UPLOAD_ERR_NO_FILE;

        if (in_array($currentStatusKey, ['KERJASAMA UMKM', 'BERKAS VERIFIKASI TIDAK SESUAI'], true) && !$hasNewRequestUpload) {
            session(['error' => 'Upload bukti Kerjasama UMKM wajib diisi sebelum reservasi dapat dikirim ke proses verifikasi']);
            $this->redirectToReservationForm($reservationId);
            return;
        }

        if ($hasNewRequestUpload) {
            if ($requestUploadErrorCode !== UPLOAD_ERR_OK) {
                session(['error' => $this->getUploadErrorMessage(
                    $requestUploadErrorCode,
                    in_array($currentStatusKey, ['KERJASAMA UMKM', 'BERKAS VERIFIKASI TIDAK SESUAI'], true)
                        ? 'Bukti Kerjasama UMKM'
                        : 'File permohonan'
                )]);
                $this->redirectToReservationForm($reservationId);
                return;
            }

            $uploadDirectory = in_array($currentStatusKey, ['KERJASAMA UMKM', 'BERKAS VERIFIKASI TIDAK SESUAI'], true)
                ? 'reservasi/kerjasama-umkm'
                : 'reservasi/permohonan';
            $uploadedRequestFilename = upload_file($requestFile, $uploadDirectory);
            if ($uploadedRequestFilename === null) {
                session(['error' => in_array($currentStatusKey, ['KERJASAMA UMKM', 'BERKAS VERIFIKASI TIDAK SESUAI'], true)
                    ? 'Bukti Kerjasama UMKM gagal diunggah. Pastikan format file JPG, JPEG, PNG, atau PDF'
                    : 'File permohonan gagal diunggah. Pastikan format file JPG, JPEG, PNG, atau PDF']);
                $this->redirectToReservationForm($reservationId);
                return;
            }

            if (in_array($currentStatusKey, ['KERJASAMA UMKM', 'BERKAS VERIFIKASI TIDAK SESUAI'], true)) {
                $newUmkmPath = $uploadDirectory . '/' . $uploadedRequestFilename;
            } else {
                $newFormPath = $uploadDirectory . '/' . $uploadedRequestFilename;
            }
        }

        $pricing = $reservasiModel->calculateReservationPricing($building, $sessionSelection);
        $nextStatus = in_array($currentStatusKey, ['KERJASAMA UMKM', 'BERKAS VERIFIKASI TIDAK SESUAI'], true)
            ? 'PROSES VERIFIKASI'
            : 'RESERVASI BARU';

        $updated = $reservasiModel->updateByUserId($reservationId, (int) $user['id'], [
            'district_id' => (int) ($building['district_id'] ?? 0),
            'building_id' => $buildingId,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'event_id' => $eventId,
            'umkm_id' => $umkmId > 0 ? $umkmId : null,
            'est_person' => $estPerson,
            'start_time' => $sessionSelection['start_time'] ?? null,
            'end_time' => $sessionSelection['end_time'] ?? null,
            'hour_count' => $pricing['hour_count'] ?? 0,
            'total_price' => $pricing['total_price'] ?? 0,
            'status' => $nextStatus,
            'return_form' => 0,
            'id_path' => $newIdentityPath !== '' ? $newIdentityPath : null,
            'form_path' => $newFormPath !== '' ? $newFormPath : null,
            'umkm_path' => $newUmkmPath !== '' ? $newUmkmPath : null,
            'notes' => null,
        ]);

        if (!$updated) {
            if ($hasNewRequestUpload) {
                $uploadedPath = in_array($currentStatusKey, ['KERJASAMA UMKM', 'BERKAS VERIFIKASI TIDAK SESUAI'], true)
                    ? $newUmkmPath
                    : $newFormPath;
                if ($uploadedPath !== '') {
                    $this->deleteUploadedFile($uploadedPath);
                }
            }

            session(['error' => 'Reservasi gagal diperbarui. Silakan coba lagi']);
            $this->redirectToReservationForm($reservationId);
            return;
        }

        if (
            $hasNewRequestUpload &&
            in_array($currentStatusKey, ['KERJASAMA UMKM', 'BERKAS VERIFIKASI TIDAK SESUAI'], true) &&
            $newUmkmPath !== '' &&
            $currentUmkmPath !== '' &&
            $newUmkmPath !== $currentUmkmPath
        ) {
            $this->deleteUploadedFile($currentUmkmPath);
        }

        if (
            $hasNewRequestUpload &&
            !in_array($currentStatusKey, ['KERJASAMA UMKM', 'BERKAS VERIFIKASI TIDAK SESUAI'], true) &&
            $newFormPath !== '' &&
            $currentFormPath !== '' &&
            $newFormPath !== $currentFormPath
        ) {
            $this->deleteUploadedFile($currentFormPath);
        }

        if (
            $newIdentityPath !== '' &&
            $currentIdentityPath !== '' &&
            $newIdentityPath !== $currentIdentityPath &&
            $this->shouldDeleteReservationIdentityFile($currentIdentityPath)
        ) {
            $this->deleteUploadedFile($currentIdentityPath);
        }

        session()->forget('old_reservasi');

        $wasReturnedReservation = $currentStatusKey === 'BERKAS RESERVASI TIDAK SESUAI';
        $wasReturnedVerification = $currentStatusKey === 'BERKAS VERIFIKASI TIDAK SESUAI';
        session(['success' => in_array($currentStatusKey, ['KERJASAMA UMKM', 'BERKAS VERIFIKASI TIDAK SESUAI'], true)
            ? ($wasReturnedVerification
                ? 'Berkas verifikasi berhasil diperbarui dan statusnya kembali menjadi Proses Verifikasi'
                : 'Bukti Kerjasama UMKM berhasil diunggah dan statusnya menjadi Proses Verifikasi')
            : ($wasReturnedReservation
                ? 'Berkas reservasi berhasil diperbarui dan statusnya kembali menjadi Reservasi Baru'
                : 'Reservasi berhasil diperbarui')]);
        $this->redirect('/user/reservasi');
    }

    public function destroy()
    {
        $user = $this->requireAuthenticatedUser();
        if ($user === null) {
            return;
        }

        verify_csrf();

        $reservationId = (int) request()->input('reservation_id', 0);
        if ($reservationId <= 0) {
            session(['error' => 'Riwayat reservasi yang dipilih tidak valid']);
            $this->redirect('/user/reservasi');
            return;
        }

        $reservasiModel = $this->model('Reservasi');
        $reservation = $reservasiModel->findByUserId($reservationId, (int) $user['id']);

        if (!$reservation) {
            session(['error' => 'Riwayat reservasi tidak ditemukan']);
            $this->redirect('/user/reservasi');
            return;
        }

        if (!$this->canUserDeleteReservation($reservation)) {
            session(['error' => 'Hanya riwayat reservasi dengan status Reservasi Baru yang dapat dihapus']);
            $this->redirect('/user/reservasi');
            return;
        }

        $deleted = $reservasiModel->deleteByUserId($reservationId, (int) $user['id']);
        if (!$deleted) {
            session(['error' => 'Riwayat reservasi gagal dihapus. Silakan coba lagi']);
            $this->redirect('/user/reservasi');
            return;
        }

        $this->paymentGateway()->cancelActivePayments($reservationId);

        if ($this->shouldDeleteReservationIdentityFile((string) ($reservation['id_path'] ?? ''))) {
            $this->deleteUploadedFile((string) ($reservation['id_path'] ?? ''));
        }
        $this->deleteUploadedFile((string) ($reservation['form_path'] ?? ''));
        $this->deleteUploadedFile((string) ($reservation['umkm_path'] ?? ''));

        session(['success' => 'Riwayat reservasi berhasil dihapus']);
        $this->redirect('/user/reservasi');
    }

    public function cancel()
    {
        $user = $this->requireAuthenticatedUser();
        if ($user === null) {
            return;
        }

        verify_csrf();

        $reservationId = (int) request()->input('reservation_id', 0);
        if ($reservationId <= 0) {
            session(['error' => 'Riwayat reservasi yang dipilih tidak valid']);
            $this->redirect('/user/reservasi');
            return;
        }

        $reservasiModel = $this->model('Reservasi');
        $reservation = $reservasiModel->findByUserId($reservationId, (int) $user['id']);

        if (!$reservation) {
            session(['error' => 'Riwayat reservasi tidak ditemukan']);
            $this->redirect('/user/reservasi');
            return;
        }

        if (!$this->canUserCancelReservation($reservation)) {
            session(['error' => 'Hanya reservasi pada tahap Kerjasama UMKM, Proses Verifikasi, atau Pembayaran yang dapat dibatalkan']);
            $this->redirect('/user/reservasi');
            return;
        }

        $cancelled = $reservasiModel->cancelByUserId(
            $reservationId,
            (int) $user['id'],
            $this->buildCancellationNote($reservation)
        );

        if (!$cancelled) {
            session(['error' => 'Reservasi gagal dibatalkan. Silakan coba lagi']);
            $this->redirect('/user/reservasi');
            return;
        }

        $this->paymentGateway()->cancelActivePayments($reservationId);

        session(['success' => 'Reservasi berhasil dibatalkan']);
        $this->redirect('/user/reservasi');
    }

    private function requireAuthenticatedUser(): ?array
    {
        if (empty(session('user_auth')) || empty(session('user.id'))) {
            $this->redirect('/login');
            return null;
        }

        $userModel = $this->model('User');
        $user = $userModel->findById((int) session('user.id'));

        if (!$user) {
            destroy_user_auth_session();
            $this->redirect('/login');
            return null;
        }

        return $user;
    }

    private function getReservationViewData(
        array $user,
        bool $consumeReservationFormState = false,
        ?int $requestedEditReservationId = null
    ): ?array
    {
        $jadwalModel = $this->model('Jadwal');
        $reservasiModel = $this->model('Reservasi');
        $umkmModel = $this->model('Umkm');
        $userModel = $this->model('User');

        if ($userModel->hasPendingProfileStatus($user)) {
            session(['error' => 'Lengkapi profil Anda terlebih dahulu di Dasbor sebelum membuka menu reservasi']);
            $this->redirect('/user/dasbor');
            return null;
        }

        $error = session('error', '');
        $success = session()->pull('success', '');
        $oldInput = session('old_reservasi', []);
        $shouldPreserveReservationFormState = !$consumeReservationFormState && !empty($oldInput);

        if (!$shouldPreserveReservationFormState) {
            session()->forget(['error', 'old_reservasi']);
        }

        $editingReservation = null;
        $editReservationId = $requestedEditReservationId ?? (int) request()->query('edit', ($oldInput['reservation_id'] ?? 0));
        if ($editReservationId > 0) {
            $editingReservation = $reservasiModel->findByUserId($editReservationId, (int) $user['id']);
            if (!$editingReservation) {
                session(['error' => 'Data reservasi yang ingin diubah tidak ditemukan']);
                $this->redirect('/user/reservasi');
                return null;
            }

            if (!$this->canUserEditReservation($editingReservation)) {
                session(['error' => 'Hanya reservasi dengan status Reservasi Baru, Kerjasama UMKM, Berkas Reservasi Tidak Sesuai, atau Berkas Verifikasi Tidak Sesuai yang dapat diubah']);
                $this->redirect('/user/reservasi');
                return null;
            }

            $editingReservation['notes'] = $this->normalizeReservationNotesForUser($editingReservation['notes'] ?? null);

            if ((int) ($oldInput['reservation_id'] ?? 0) !== $editReservationId) {
                $sessionOption = $reservasiModel->getSessionOptionIdByTimes(
                    $editingReservation['start_time'] ?? null,
                    $editingReservation['end_time'] ?? null
                );

                $oldInput = [
                    'reservation_id' => (int) $editingReservation['id'],
                    'building_id' => (int) ($editingReservation['building_id'] ?? 0),
                    'event_id' => (int) ($editingReservation['event_id'] ?? 0),
                    'umkm_id' => (int) ($editingReservation['umkm_id'] ?? 0),
                    'session_option' => $sessionOption,
                    'start_date' => (string) ($editingReservation['start_date'] ?? ''),
                    'end_date' => (string) ($editingReservation['end_date'] ?? ''),
                    'start_time' => (string) ($editingReservation['start_time'] ?? ''),
                    'end_time' => (string) ($editingReservation['end_time'] ?? ''),
                    'est_person' => (int) ($editingReservation['est_person'] ?? 0),
                ];
            }
        }

        if ($consumeReservationFormState) {
            session()->forget(['error', 'old_reservasi']);
        }

        $selectedBuilding = null;
        if (!empty($oldInput['building_id'])) {
            $selectedBuilding = $reservasiModel->findActiveBuildingById((int) $oldInput['building_id']);
        }

        $minBookingDate = (new DateTime('today'))->modify('+14 days')->format('Y-m-d');
        $myReservations = $this->appendReservationPaymentStates(
            $user,
            $this->appendReservationFileUrls(
                $reservasiModel->byUserDetailed((int) $user['id'])
            )
        );

        return [
            'user' => $user,
            'error' => $error,
            'success' => $success,
            'oldInput' => $oldInput,
            'selectedBuilding' => $selectedBuilding,
            'profileIncomplete' => false,
            'filterData' => $jadwalModel->getFilterData(),
            'events' => $jadwalModel->getCalendarEvents(),
            'eventOptions' => $reservasiModel->getActiveEvents(),
            'umkmOptions' => $umkmModel->getReservationOptions(),
            'sessionOptions' => $reservasiModel->getActiveSessions(),
            'myReservations' => $myReservations,
            'editingReservation' => $editingReservation,
            'minBookingDate' => $minBookingDate,
            'reservationPanelUrl' => $this->getReservationPanelUrl($editReservationId > 0 ? $editReservationId : null),
            'reservationPrintUrl' => $this->getReservationPrintUrl(),
        ];
    }

    private function appendReservationFileUrls(array $reservations): array
    {
        foreach ($reservations as &$reservation) {
            $identityRelativePath = $this->normalizeRelativeUploadPath((string) ($reservation['id_path'] ?? '')) ?? '';
            $applicationRelativePath = $this->normalizeRelativeUploadPath((string) ($reservation['form_path'] ?? '')) ?? '';
            $umkmRelativePath = $this->normalizeRelativeUploadPath((string) ($reservation['umkm_path'] ?? '')) ?? '';
            $paymentRelativePath = $this->normalizeRelativeUploadPath((string) ($reservation['payment_proof_path'] ?? '')) ?? '';

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
            $reservation['notes'] = $this->normalizeReservationNotesForUser($reservation['notes'] ?? null);
        }
        unset($reservation);

        return $reservations;
    }

    private function appendReservationPaymentStates(array $user, array $reservations): array
    {
        $gateway = $this->paymentGateway();

        foreach ($reservations as &$reservation) {
            $payment = $gateway->findActivePayment((int) ($reservation['id'] ?? 0));
            $reservation = array_merge(
                $reservation,
                $payment !== null
                    ? $this->buildReservationPaymentState($user, $reservation, $payment)
                    : $this->buildEmptyReservationPaymentState($reservation)
            );
        }
        unset($reservation);

        return $reservations;
    }

    private function normalizeReservationNotesForUser($notes): string
    {
        $normalizedNotes = trim((string) $notes);
        if ($normalizedNotes === '') {
            return '';
        }

        $normalizedNotes = preg_replace("/\r\n?/", "\n", $normalizedNotes) ?? $normalizedNotes;
        $filteredLines = array_values(array_filter(
            array_map('trim', explode("\n", $normalizedNotes)),
            static function (string $line): bool {
                if ($line === '') {
                    return false;
                }

                if (preg_match('/^Catatan .+\(\d{2} [A-Za-z]{3} \d{4} \d{2}:\d{2}\)$/', $line)) {
                    return false;
                }

                if (preg_match('/^Pengajuan reservasi dikembalikan ke pemohon untuk diperbaiki\.?$/i', $line)) {
                    return false;
                }

                if (preg_match('/^Reservasi dikembalikan ke pemohon untuk diperbaiki(\. Status menjadi (KEMBALI|BERKAS TIDAK LENGKAP|BERKAS TIDAK SESUAI|BERKAS RESERVASI TIDAK SESUAI|BERKAS VERIFIKASI TIDAK SESUAI|BERKAS PEMBAYARAN TIDAK SESUAI)\.)?$/i', $line)) {
                    return false;
                }

                if (preg_match('/^Status (menjadi (KEMBALI|BERKAS TIDAK LENGKAP|BERKAS TIDAK SESUAI|BERKAS RESERVASI TIDAK SESUAI|BERKAS VERIFIKASI TIDAK SESUAI|BERKAS PEMBAYARAN TIDAK SESUAI)|tetap (PROSES|RESERVASI BARU|PROSES VERIFIKASI|CEK PEMBAYARAN))\.?$/i', $line)) {
                    return false;
                }

                return true;
            }
        ));

        if ($filteredLines === []) {
            return $normalizedNotes;
        }

        return implode(PHP_EOL, $filteredLines);
    }

    private function canUserEditReservation(array $reservation): bool
    {
        $status = $this->normalizeReservationStatus($reservation);

        return in_array($status, ['RESERVASI BARU', 'KERJASAMA UMKM', 'BERKAS RESERVASI TIDAK SESUAI', 'BERKAS VERIFIKASI TIDAK SESUAI'], true);
    }

    private function canUserDeleteReservation(array $reservation): bool
    {
        return $this->normalizeReservationStatus($reservation) === 'RESERVASI BARU';
    }

    private function canUserCancelReservation(array $reservation): bool
    {
        return in_array($this->normalizeReservationStatus($reservation), [
            'KERJASAMA UMKM',
            'PROSES VERIFIKASI',
            'BERKAS VERIFIKASI TIDAK SESUAI',
            'MENUNGGU PEMBAYARAN',
            'CEK PEMBAYARAN',
            'BERKAS PEMBAYARAN TIDAK SESUAI',
        ], true);
    }

    private function buildCancellationNote(array $reservation): string
    {
        $status = $this->normalizeReservationStatus($reservation);

        $statusLabel = match ($status) {
            'KERJASAMA UMKM' => 'Kerjasama UMKM',
            'PROSES VERIFIKASI' => 'Proses Verifikasi',
            'BERKAS VERIFIKASI TIDAK SESUAI' => 'Berkas Verifikasi Tidak Sesuai',
            'MENUNGGU PEMBAYARAN' => 'Menunggu Pembayaran',
            'CEK PEMBAYARAN' => 'Cek Pembayaran',
            'BERKAS PEMBAYARAN TIDAK SESUAI' => 'Berkas Pembayaran Tidak Sesuai',
            'PEMBAYARAN LUNAS' => 'Pembayaran Lunas',
            default => '',
        };

        return $statusLabel !== ''
            ? 'Reservasi dibatalkan oleh pemohon.' . PHP_EOL . 'Status terakhir: ' . $statusLabel . '.'
            : 'Reservasi dibatalkan oleh pemohon';
    }

    private function normalizeReservationStatus(array $reservation): string
    {
        return reservation_status_display_key($reservation['status'] ?? '');
    }

    private function buildReservationApplicationDocumentData(array $user, array $input): ?array
    {
        $reservasiModel = $this->model('Reservasi');
        $umkmModel = $this->model('Umkm');

        $buildingId = (int) ($input['building_id'] ?? 0);
        $eventId = (int) ($input['event_id'] ?? 0);
        $umkmId = (int) ($input['umkm_id'] ?? 0);
        $sessionOption = trim((string) ($input['session_option'] ?? ''));
        $startDate = trim((string) ($input['start_date'] ?? ''));
        $endDate = trim((string) ($input['end_date'] ?? $startDate));
        $startTime = trim((string) ($input['start_time'] ?? ''));
        $endTime = trim((string) ($input['end_time'] ?? ''));
        $estPerson = (int) ($input['est_person'] ?? 0);

        if (
            $buildingId <= 0 ||
            $eventId <= 0 ||
            $sessionOption === '' ||
            $startDate === '' ||
            $endDate === '' ||
            $estPerson <= 0
        ) {
            $this->respondReservationPrintError('Lengkapi data reservasi terlebih dahulu sebelum mencetak permohonan.');
            return null;
        }

        if (!$this->isValidDate($startDate) || !$this->isValidDate($endDate)) {
            $this->respondReservationPrintError('Tanggal reservasi tidak valid.');
            return null;
        }

        if ($startDate !== $endDate) {
            $this->respondReservationPrintError('Dokumen permohonan hanya tersedia untuk reservasi satu tanggal.');
            return null;
        }

        $minimumDate = (new DateTime('today'))->modify('+14 days')->format('Y-m-d');
        if ($startDate < $minimumDate) {
            $this->respondReservationPrintError('Tanggal reservasi harus minimal H-14 dari hari ini.');
            return null;
        }

        $building = $reservasiModel->findActiveBuildingById($buildingId);
        if (!$building) {
            $this->respondReservationPrintError('Gedung yang dipilih tidak ditemukan atau tidak aktif.');
            return null;
        }

        $event = $reservasiModel->findActiveEventById($eventId);
        if (!$event) {
            $this->respondReservationPrintError('Jenis acara yang dipilih tidak valid.');
            return null;
        }

        $sessionSelection = $reservasiModel->resolveSessionSelection($sessionOption, $startTime, $endTime);
        if (!$sessionSelection) {
            $this->respondReservationPrintError('Sesi reservasi atau rentang waktu yang dipilih tidak valid.');
            return null;
        }

        $capacity = (int) ($building['capacity'] ?? 0);
        if ($capacity > 0 && $estPerson > $capacity) {
            $this->respondReservationPrintError(
                'Estimasi peserta melebihi kapasitas gedung, maksimum '
                    . number_format($capacity, 0, ',', '.')
                    . ' orang.'
            );
            return null;
        }

        $umkmLabel = '-';
        if ($umkmId > 0) {
            $umkmOption = $umkmModel->findReservationOptionById($umkmId);
            if (!$umkmOption || !in_array($buildingId, $umkmOption['building_ids'] ?? [], true)) {
                $this->respondReservationPrintError('UMKM yang dipilih tidak tersedia untuk gedung tersebut.');
                return null;
            }

            $productLabel = trim((string) ($umkmOption['product_label'] ?? ''));
            $umkmLabel = trim((string) ($umkmOption['umkm_name'] ?? '-'));
            if ($productLabel !== '') {
                $umkmLabel .= ' (' . $productLabel . ')';
            }
        }

        $startDateObject = DateTime::createFromFormat('Y-m-d', $startDate) ?: new DateTime($startDate);
        $letterDateObject = new DateTime('today');
        $durationLabel = $this->formatReservationDurationLabel(
            (string) ($sessionSelection['start_time'] ?? ''),
            (string) ($sessionSelection['end_time'] ?? '')
        );
        $buildingDistrict = trim((string) ($building['district'] ?? ''));

        return [
            'letter_date' => $this->formatIndonesianDate($letterDateObject),
            'recipient_district' => $buildingDistrict !== ''
                ? $buildingDistrict
                : '-',
            'applicant_nik' => trim((string) ($user['nik'] ?? '')) !== ''
                ? (string) $user['nik']
                : '-',
            'applicant_name' => resolve_user_display_name($user),
            'applicant_address' => trim((string) ($user['address'] ?? '')) !== ''
                ? (string) $user['address']
                : '-',
            'applicant_phone' => trim((string) ($user['phone'] ?? '')) !== ''
                ? (string) $user['phone']
                : '-',
            'building_name' => trim((string) ($building['building_name'] ?? '')) !== ''
                ? (string) $building['building_name']
                : '-',
            'building_address' => trim((string) ($building['address'] ?? '')) !== ''
                ? (string) $building['address']
                : '-',
            'building_subdistrict' => trim((string) ($building['subdistrict'] ?? '')) !== ''
                ? (string) $building['subdistrict']
                : '-',
            'building_district' => $buildingDistrict !== ''
                ? $buildingDistrict
                : '-',
            'event_day' => $this->formatIndonesianDayName($startDateObject),
            'event_date' => $this->formatIndonesianDate($startDateObject),
            'event_start_time' => substr((string) ($sessionSelection['start_time'] ?? ''), 0, 5),
            'event_end_time' => substr((string) ($sessionSelection['end_time'] ?? ''), 0, 5),
            'event_duration' => $durationLabel,
            'event_name' => trim((string) ($event['event_name'] ?? '')) !== ''
                ? (string) $event['event_name']
                : '-',
            'est_person_label' => number_format($estPerson, 0, ',', '.') . ' Orang',
            'umkm_label' => $umkmLabel,
        ];
    }

    private function isValidDate(string $date): bool
    {
        $parsed = DateTime::createFromFormat('Y-m-d', $date);

        return $parsed instanceof DateTime
            && $parsed->format('Y-m-d') === $date;
    }

    private function getUploadErrorMessage(int $errorCode, string $label = 'File KTP'): string
    {
        return match ($errorCode) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'Ukuran ' . strtolower($label) . ' terlalu besar',
            UPLOAD_ERR_PARTIAL => $label . ' gagal terunggah secara utuh',
            UPLOAD_ERR_NO_FILE => $label . ' wajib diunggah',
            default => 'Terjadi kendala saat mengunggah ' . strtolower($label),
        };
    }

    private function deleteUploadedFile(string $relativePath): void
    {
        $relativePath = $this->normalizeRelativeUploadPath($relativePath) ?? '';
        if ($relativePath === '') {
            return;
        }

        legacy_delete_upload_file($relativePath);
    }

    private function normalizeRelativeUploadPath(?string $relativePath): ?string
    {
        $normalizedPath = trim(str_replace('\\', '/', (string) $relativePath));

        if (str_starts_with($normalizedPath, 'user/identity/')) {
            $normalizedPath = 'user/identitas/' . substr($normalizedPath, strlen('user/identity/'));
        }

        return $normalizedPath !== '' ? ltrim($normalizedPath, '/') : null;
    }

    private function shouldDeleteReservationIdentityFile(?string $relativePath): bool
    {
        $normalizedPath = $this->normalizeRelativeUploadPath($relativePath);

        return $normalizedPath !== null && str_starts_with($normalizedPath, 'reservasi/id/');
    }

    private function redirectToReservationForm(?int $reservationId = null): void
    {
        $path = '/user/reservasi';
        if ($reservationId !== null && $reservationId > 0) {
            $path = '/user/reservasi/rubah/' . $reservationId;
        }

        $this->redirect($path);
    }

    private function getReservationPanelUrl(?int $editReservationId = null): string
    {
        $path = 'user/reservasi/panel';
        if ($editReservationId !== null && $editReservationId > 0) {
            $path .= '/rubah/' . $editReservationId;
        }

        return url($path);
    }

    private function resolveRouteReservationId(?string $editId): ?int
    {
        $reservationId = (int) trim((string) $editId);

        return $reservationId > 0 ? $reservationId : null;
    }

    private function getReservationPrintUrl(): string
    {
        return url('user/reservasi/permohonan/cetak');
    }

    private function paymentGateway(): PaymentTestGateway
    {
        return new PaymentTestGateway();
    }

    private function findUserPaymentReservation(int $reservationId, array $user): ?array
    {
        if ($reservationId <= 0) {
            $this->respondReservationPaymentJson(false, 'Reservasi pembayaran tidak valid', [], 422);
        }

        $reservasiModel = $this->model('Reservasi');
        $reservation = $reservasiModel->findDetailed($reservationId);

        if (!$reservation || (int) ($reservation['user_id'] ?? 0) !== (int) ($user['id'] ?? 0)) {
            $this->respondReservationPaymentJson(false, 'Data reservasi tidak ditemukan', [], 404);
        }

        if (!reservation_status_matches($reservation['status'] ?? '', ['MENUNGGU PEMBAYARAN'])) {
            $this->respondReservationPaymentJson(false, 'Pembayaran hanya dapat diproses untuk reservasi berstatus Menunggu Pembayaran', [], 422);
        }

        return $reservation;
    }

    private function buildReservationPaymentState(array $user, array $reservation, array $payment): array
    {
        $method = $this->paymentGateway()->methodKey($payment);

        if ($method === '') {
            return $this->buildEmptyReservationPaymentState($reservation);
        }

        $document = $this->buildReservationPaymentDocumentData($user, $reservation, $method, $payment);
        $reservationId = (int) ($reservation['id'] ?? $payment['reservation_id'] ?? 0);
        $paymentCode = trim((string) ($payment['payment_code'] ?? ''));
        $expiryValue = trim((string) ($payment['expired_at'] ?? ''));
        $expiryLabel = $method === 'va'
            ? (string) ($document['va_expiry_label'] ?? '')
            : (string) ($document['qris_expiry_label'] ?? '');

        return [
            'id' => $reservationId,
            'status' => reservation_status_display_key($reservation['status'] ?? ''),
            'request_id' => (string) ($reservation['request_id'] ?? ''),
            'order_id' => (string) ($reservation['order_id'] ?? ''),
            'total_price' => (float) ($reservation['total_price'] ?? $payment['amount'] ?? 0),
            'payment_method_key' => $method,
            'payment_method_label' => $this->formatReservationPaymentMethodLabel($method),
            'payment_provider' => trim((string) ($payment['provider'] ?? '')) ?: 'BANK JATIM',
            'payment_code_value' => $paymentCode,
            'payment_qris_url' => trim((string) ($payment['qris_url'] ?? '')),
            'payment_expired_at' => $expiryValue,
            'payment_expiry_label' => trim($expiryLabel),
            'payment_preview_url' => $this->buildPaymentDocumentPreviewUrl($reservationId, $document),
            'payment_download_url' => $this->buildPaymentDocumentPreviewUrl($reservationId, $document, true),
        ];
    }

    private function buildEmptyReservationPaymentState(array $reservation): array
    {
        return [
            'id' => (int) ($reservation['id'] ?? 0),
            'status' => reservation_status_display_key($reservation['status'] ?? ''),
            'request_id' => (string) ($reservation['request_id'] ?? ''),
            'order_id' => (string) ($reservation['order_id'] ?? ''),
            'total_price' => (float) ($reservation['total_price'] ?? 0),
            'payment_method_key' => '',
            'payment_method_label' => '',
            'payment_provider' => '',
            'payment_code_value' => '',
            'payment_qris_url' => '',
            'payment_expired_at' => '',
            'payment_expiry_label' => '',
            'payment_preview_url' => '',
            'payment_download_url' => '',
        ];
    }

    private function formatReservationPaymentMethodLabel(string $method): string
    {
        return match ($this->normalizePaymentDocumentMethod($method)) {
            'va' => 'Virtual Account (VA)',
            'qris' => 'QRIS',
            default => '',
        };
    }

    private function respondReservationPaymentJson(bool $success, string $message, array $payload = [], int $statusCode = 200): never
    {
        throw new HttpResponseException(
            response()->json(array_merge([
                'success' => $success,
                'message' => $message,
            ], $payload), $statusCode, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );
    }

    private function buildReservationPaymentDocumentData(array $user, array $reservation, string $method, ?array $payment = null): array
    {
        $createdAt = $this->resolveDateTimeValue((string) ($reservation['created_at'] ?? ''));
        $eventDate = $this->resolveDateTimeValue((string) ($reservation['start_date'] ?? ''));
        $now = new DateTime('now');
        $qrisExpiry = (clone $now)->modify('+15 minutes');
        $paymentExpiry = $this->resolveDateTimeValue((string) ($payment['expired_at'] ?? $reservation['payment_expired_at'] ?? ''));
        $vaExpiry = $paymentExpiry ?? $this->buildReservationVaExpiryDate($eventDate);
        $paymentCode = trim((string) ($payment['payment_code'] ?? $reservation['payment_code_value'] ?? ''));
        $paymentProvider = trim((string) ($payment['provider'] ?? $reservation['payment_provider'] ?? ''));
        $totalPrice = (float) ($reservation['total_price'] ?? 0);
        $reservationCode = $this->resolveReservationDocumentCode($reservation);
        $requesterName = trim((string) ($reservation['user_name'] ?? '')) !== ''
            ? trim((string) ($reservation['user_name'] ?? ''))
            : resolve_user_display_name($user);
        $buildingLocation = trim((string) ($reservation['building_address'] ?? ''));
        $district = trim((string) ($reservation['district'] ?? ''));
        $region = trim((string) ($reservation['region'] ?? ''));

        if ($district !== '' || $region !== '') {
            $buildingLocation .= ($buildingLocation !== '' ? ', ' : '')
                . trim($district . ($region !== '' ? ' - Surabaya ' . $region : ''));
        }

        return [
            'method' => $method,
            'preview_title' => $method === 'va' ? 'Dokumen Virtual Account' : 'Dokumen QRIS',
            'reservation_id' => (int) ($reservation['id'] ?? 0),
            'reservation_code' => $reservationCode,
            'reservation_code_label' => $reservationCode !== '' ? $reservationCode : '-',
            'requester_name' => $requesterName !== '' ? $requesterName : '-',
            'requester_phone' => trim((string) ($reservation['user_phone'] ?? '')) !== ''
                ? trim((string) ($reservation['user_phone'] ?? ''))
                : '-',
            'requester_nik' => trim((string) ($reservation['user_nik'] ?? '')) !== ''
                ? trim((string) ($reservation['user_nik'] ?? ''))
                : '-',
            'building_name' => trim((string) ($reservation['building_name'] ?? '')) !== ''
                ? trim((string) ($reservation['building_name'] ?? ''))
                : '-',
            'building_location' => $buildingLocation !== '' ? $buildingLocation : '-',
            'event_name' => trim((string) ($reservation['event_name'] ?? '')) !== ''
                ? trim((string) ($reservation['event_name'] ?? ''))
                : '-',
            'event_date_label' => $eventDate !== null ? $this->formatIndonesianDate($eventDate) : '-',
            'session_label' => trim((string) ($reservation['session_display_name'] ?? $reservation['session_name'] ?? '')) !== ''
                ? trim((string) ($reservation['session_display_name'] ?? $reservation['session_name'] ?? ''))
                : '-',
            'total_price_label' => 'Rp ' . number_format($totalPrice, 0, ',', '.'),
            'total_price_compact_label' => 'Rp' . number_format($totalPrice, 0, ',', '.'),
            'total_price_plain_label' => number_format($totalPrice, 0, ',', '.'),
            'tarif_sewa_label' => number_format($totalPrice, 0, ',', '.'),
            'tarif_overtime_label' => '-',
            'total_payment_label' => 'Rp ' . number_format($totalPrice, 0, ',', '.'),
            'virtual_account_number' => $paymentCode !== ''
                ? $paymentCode
                : $this->buildReservationVirtualAccountNumber($reservation, $eventDate ?? $createdAt),
            'va_bank_name' => $paymentProvider !== '' ? $paymentProvider : 'Bank Jatim',
            'va_expiry_label' => $vaExpiry !== null
                ? $this->formatIndonesianDateTimeWithZoneLabel($vaExpiry, 'WIB')
                : '-',
            'document_created_label' => $this->formatIndonesianDate($now),
            'invoice_number' => $this->buildReservationInvoiceNumber($reservation, $createdAt),
            'qris_expiry_label' => $this->formatIndonesianDateTime($qrisExpiry),
            'qris_sample_data_uri' => $this->getPaymentDocumentQrSampleDataUri(),
            'notes' => [
                'Segera lakukan pembayaran sebelum masa berlaku Virtual Account (VA) berakhir',
                'Keterlambatan pembayaran akan mengakibatkan pengajuan dibatalkan secara otomatis oleh sistem',
                'Jika pengajuan telah dibatalkan, pemohon wajib mengulangi seluruh proses reservasi dari awal',
            ],
            'footer_note' => 'Jika pembayaran telah berhasil dilakukan, Anda dapat mencetak bukti Surat Setoran Retribusi Daerah (SSRD) pada laman bpkad.surabaya.go.id/cetak-ssrd. Untuk informasi lebih lanjut, silakan hubungi Call Center BPKAD di nomor 0852-5750-5734 (WhatsApp)',
        ];
    }

    private function normalizePaymentDocumentMethod(string $method): string
    {
        $normalized = strtolower(trim($method));

        return in_array($normalized, ['va', 'qris'], true) ? $normalized : '';
    }

    private function resolveReservationDocumentCode(array $reservation): string
    {
        $requestCode = trim((string) ($reservation['request_id'] ?? ''));
        $orderCode = trim((string) ($reservation['order_id'] ?? ''));
        $useOrderCode = reservation_status_uses_order_code($reservation['status'] ?? '');
        $code = $useOrderCode ? $orderCode : $requestCode;

        if ($code !== '') {
            return $code;
        }

        return $requestCode !== '' ? $requestCode : $orderCode;
    }

    private function buildReservationVirtualAccountNumber(array $reservation, ?DateTime $referenceDate = null): string
    {
        $dateValue = trim((string) ($reservation['start_date'] ?? ''));
        if ($dateValue !== '') {
            $reservationDate = $this->resolveDateTimeValue($dateValue);
            if ($reservationDate instanceof DateTime) {
                $referenceDate = $reservationDate;
            }
        }

        $referenceDate = $referenceDate ?? new DateTime('now');

        return '1030801'
            . $referenceDate->format('ymd')
            . str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    private function buildReservationInvoiceNumber(array $reservation, ?DateTime $referenceDate = null): string
    {
        $referenceDate = $referenceDate ?? new DateTime('now');
        $reservationId = (int) ($reservation['id'] ?? 0);

        return $referenceDate->format('ymd')
            . str_pad((string) $reservationId, 12, '0', STR_PAD_LEFT);
    }

    private function resolveDateTimeValue(string $value): ?DateTime
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        try {
            return new DateTime($value);
        } catch (Throwable $exception) {
            return null;
        }
    }

    private function formatIndonesianDateTime(DateTime $date): string
    {
        return $this->formatIndonesianDate($date) . ' ' . $date->format('H:i:s');
    }

    private function formatIndonesianDateTimeWithZoneLabel(DateTime $date, string $zoneLabel = ''): string
    {
        $suffix = trim($zoneLabel);

        return $this->formatIndonesianDate($date)
            . ' '
            . $date->format('H:i:s')
            . ($suffix !== '' ? ' ' . $suffix : '');
    }

    private function formatCountdownLabel(DateTime $start, DateTime $end): string
    {
        $seconds = max(0, $end->getTimestamp() - $start->getTimestamp());
        $minutes = (int) floor($seconds / 60);
        $remainingSeconds = $seconds % 60;

        return $minutes . 'm ' . str_pad((string) $remainingSeconds, 2, '0', STR_PAD_LEFT) . 's';
    }

    private function getPaymentDocumentQrSampleDataUri(): string
    {
        $filePath = legacy_first_existing_asset_path('custom/images/payment/qris-sample-qr.png')
            ?? legacy_asset_path('custom/images/payment/qris-sample-qr.png');

        if (!is_file($filePath)) {
            return '';
        }

        $contents = @file_get_contents($filePath);
        if ($contents === false) {
            return '';
        }

        return 'data:image/png;base64,' . base64_encode($contents);
    }

    private function buildPaymentDocumentDownloadFilename(array $document): string
    {
        $code = preg_replace('/[^A-Za-z0-9-]/', '-', (string) ($document['reservation_code_label'] ?? 'reservasi')) ?: 'reservasi';
        $method = strtolower(trim((string) ($document['method'] ?? 'document')));
        $extension = $method === 'va' ? 'pdf' : 'svg';

        return $method . '-pembayaran-' . $code . '.' . $extension;
    }

    private function buildPaymentDocumentPreviewUrl(int $reservationId, array $document, bool $isDownloadMode = false): string
    {
        $method = strtolower(trim((string) ($document['method'] ?? 'document')));
        $filename = rawurlencode($this->buildPaymentDocumentDownloadFilename($document));
        $query = 'method=' . rawurlencode($method);

        if ($isDownloadMode) {
            $query .= '&download=1';
        }

        return url('user/reservasi/pembayaran/cetak/' . $reservationId . '/' . $filename . '?' . $query);
    }

    private function buildReservationVaExpiryDate(?DateTime $eventDate): ?DateTime
    {
        if (!$eventDate instanceof DateTime) {
            return null;
        }

        $expiryDate = clone $eventDate;
        $expiryDate->setTime(0, 0, 0);
        $expiryDate->modify('-7 days');

        return $expiryDate;
    }

    private function renderQrisPaymentDocumentSvg(array $document): string
    {
        $bookingCode = htmlspecialchars((string) ($document['reservation_code_label'] ?? '-'), ENT_QUOTES | ENT_XML1, 'UTF-8');
        $totalLabel = htmlspecialchars((string) ($document['total_price_compact_label'] ?? '-'), ENT_QUOTES | ENT_XML1, 'UTF-8');
        $expiryLabel = htmlspecialchars((string) ($document['qris_expiry_label'] ?? '-'), ENT_QUOTES | ENT_XML1, 'UTF-8');
        $reservationCode = htmlspecialchars((string) ($document['reservation_code_label'] ?? '-'), ENT_QUOTES | ENT_XML1, 'UTF-8');
        $qrDataUri = htmlspecialchars((string) ($document['qris_sample_data_uri'] ?? ''), ENT_QUOTES | ENT_XML1, 'UTF-8');

        return <<<SVG
<?xml version="1.0" encoding="UTF-8"?>
<svg xmlns="http://www.w3.org/2000/svg" width="900" height="1400" viewBox="0 0 900 1400" role="img" aria-labelledby="title desc">
    <title id="title">QRIS Pembayaran Reservasi {$reservationCode}</title>
    <desc id="desc">Dokumen QRIS untuk pembayaran reservasi {$reservationCode}</desc>
    <rect width="900" height="1400" fill="#ffffff"/>
    <circle cx="450" cy="135" r="84" fill="#ffffff" stroke="#7ad7de" stroke-width="10"/>
    <text x="450" y="168" text-anchor="middle" fill="#74d0d7" font-family="Arial, sans-serif" font-size="118" font-weight="700">i</text>
    <text x="450" y="320" text-anchor="middle" fill="#2d2d2d" font-family="Arial, sans-serif" font-size="72" font-weight="400">QRIS Pembayaran Berhasil</text>
    <text x="450" y="402" text-anchor="middle" fill="#2d2d2d" font-family="Arial, sans-serif" font-size="72" font-weight="400">Dibuat</text>
    <text x="450" y="500" text-anchor="middle" fill="#2d2d2d" font-family="Arial, sans-serif" font-size="34" font-weight="700">Kode Booking: <tspan font-weight="400">{$bookingCode}</tspan></text>
    <text x="450" y="555" text-anchor="middle" fill="#2d2d2d" font-family="Arial, sans-serif" font-size="34" font-weight="700">Total Pembayaran: <tspan font-weight="400">{$totalLabel}</tspan></text>
    <text x="450" y="610" text-anchor="middle" fill="#2d2d2d" font-family="Arial, sans-serif" font-size="30" font-weight="700">Masa Berlaku: <tspan font-weight="400">{$expiryLabel}</tspan></text>
SVG
        . ($qrDataUri !== ''
            ? '
    <image x="205" y="665" width="490" height="490" href="' . $qrDataUri . '" preserveAspectRatio="xMidYMid meet"/>
'
            : '
    <rect x="205" y="665" width="490" height="490" rx="28" fill="#f3f4f6" stroke="#d1d5db" stroke-width="2"/>
    <text x="450" y="930" text-anchor="middle" fill="#6b7280" font-family="Arial, sans-serif" font-size="32" font-weight="600">QRIS tidak tersedia</text>
')
        . <<<SVG
    <text x="450" y="1230" text-anchor="middle" fill="#5b6474" font-family="Arial, sans-serif" font-size="22">Reservasi {$reservationCode} - SIGAP Kota Surabaya</text>
</svg>
SVG;
    }

    private function renderViewToString(string $view, array $data = []): string
    {
        return view($view, $data)->render();
    }

    private function buildingHasUmkmReservationOptions(Umkm $umkmModel, int $buildingId): bool
    {
        if ($buildingId <= 0) {
            return false;
        }

        foreach ($umkmModel->getReservationOptions() as $umkmOption) {
            if (in_array($buildingId, $umkmOption['building_ids'] ?? [], true)) {
                return true;
            }
        }

        return false;
    }

    private function formatIndonesianDate(DateTime $date): string
    {
        $monthNames = [
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember',
        ];

        $day = (int) $date->format('d');
        $month = (int) $date->format('n');
        $year = $date->format('Y');

        return $day . ' ' . ($monthNames[$month] ?? $date->format('F')) . ' ' . $year;
    }

    private function formatIndonesianDayName(DateTime $date): string
    {
        $dayNames = [
            'Sunday' => 'Minggu',
            'Monday' => 'Senin',
            'Tuesday' => 'Selasa',
            'Wednesday' => 'Rabu',
            'Thursday' => 'Kamis',
            'Friday' => 'Jumat',
            'Saturday' => 'Sabtu',
        ];

        $englishName = $date->format('l');

        return $dayNames[$englishName] ?? $englishName;
    }

    private function formatReservationDurationLabel(string $startTime, string $endTime): string
    {
        $start = DateTime::createFromFormat('H:i:s', $startTime) ?: DateTime::createFromFormat('H:i', $startTime);
        $end = DateTime::createFromFormat('H:i:s', $endTime) ?: DateTime::createFromFormat('H:i', $endTime);

        if (!$start || !$end) {
            return '';
        }

        $minutes = ((int) $end->format('H') * 60 + (int) $end->format('i'))
            - ((int) $start->format('H') * 60 + (int) $start->format('i'));

        if ($minutes <= 0) {
            return '';
        }

        if ($minutes % 60 === 0) {
            return (int) ($minutes / 60) . ' Jam';
        }

        return rtrim(rtrim(number_format($minutes / 60, 2, ',', '.'), '0'), ',') . ' Jam';
    }

    private function respondReservationPrintError(string $message): never
    {
        $html = '<!doctype html><html lang="id"><head><meta charset="utf-8"><title>Cetak Permohonan Gagal</title></head><body style="font-family:Arial,sans-serif;padding:24px;color:#1f2937;">'
            . '<h2 style="margin-top:0;">Dokumen Permohonan Belum Bisa Dibuat</h2>'
            . '<p>' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</p>'
            . '<p>Silakan kembali ke form reservasi, periksa datanya, lalu coba cetak lagi.</p>'
            . '</body></html>';

        throw new HttpResponseException(
            response($html, 422)->header('Content-Type', 'text/html; charset=UTF-8')
        );
    }
}
