<?php

use App\Http\Controllers\Admin;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->name('admin.')->group(function (): void {
    Route::controller(Admin\AuthController::class)->group(function (): void {
        Route::get('login', 'login')->name('login');
        Route::post('login', 'authenticate')->name('login.authenticate');
        Route::get('logout', 'logout')->name('logout');
    });

    Route::middleware('admin')->group(function (): void {
        Route::get('/', [Admin\DashboardController::class, 'index'])->name('index');
        Route::get('dasbor', [Admin\DashboardController::class, 'index'])->name('dashboard');
        Route::get('kalender', [Admin\DashboardController::class, 'calendar'])->name('calendar');
        Route::get('pengaturan/akses', [Admin\DashboardController::class, 'accessSettings'])->name('access.index');
        Route::post('pengaturan/akses', [Admin\DashboardController::class, 'saveAccessSettings'])->name('access.store');

        Route::get('laporan/rating', [Admin\LaporanController::class, 'rating'])->name('report.rating');
        Route::get('laporan/rating/gedung', [Admin\LaporanController::class, 'gedung'])->name('report.rating.building');
        Route::get('laporan/rating/umkm', [Admin\LaporanController::class, 'umkm'])->name('report.rating.umkm');

        Route::get('reservasi', [Admin\ReservasiController::class, 'index'])->name('reservation.index');
        Route::post('reservasi/setujui', [Admin\ReservasiController::class, 'approve'])->name('reservation.approve');
        Route::post('reservasi/kembali', [Admin\ReservasiController::class, 'returnToApplicant'])->name('reservation.return');
        Route::post('reservasi/tolak', [Admin\ReservasiController::class, 'reject'])->name('reservation.reject');
        Route::get('riwayat/gedung', [Admin\ReservasiController::class, 'gedung'])->name('history.building');
        Route::post('riwayat/gedung/hapus', [Admin\ReservasiController::class, 'destroy'])->name('history.building.destroy');
        Route::get('riwayat/umkm', [Admin\ReservasiController::class, 'umkm'])->name('history.umkm');
        Route::post('riwayat/umkm/hapus', [Admin\ReservasiController::class, 'destroyUmkm'])->name('history.umkm.destroy');

        Route::get('pembayaran', [Admin\PembayaranController::class, 'index'])->name('payment.index');
        Route::post('pembayaran/lunas', [Admin\PembayaranController::class, 'markAsPaid'])->name('payment.paid');
        Route::post('pembayaran/kembali', [Admin\PembayaranController::class, 'returnToApplicant'])->name('payment.return');

        Route::get('verifikasi', [Admin\VerifikasiController::class, 'index'])->name('verification.index');
        Route::post('verifikasi/setuju', [Admin\VerifikasiController::class, 'approve'])->name('verification.approve');
        Route::post('verifikasi/kembali', [Admin\VerifikasiController::class, 'returnToApplicant'])->name('verification.return');
        Route::post('session/keepalive', [Admin\AuthController::class, 'keepAlive'])->name('session.keepalive');
    });
});
