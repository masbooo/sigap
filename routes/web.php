<?php

use App\Http\Controllers\CaptchaController;
use App\Http\Controllers\Landing;
use App\Http\Controllers\User;
use Illuminate\Support\Facades\Route;

Route::get('/', [Landing\HomeController::class, 'index'])->name('home');
Route::get('gedung', [Landing\GedungController::class, 'index'])->name('gedung.index');
Route::get('jadwal', [Landing\JadwalController::class, 'index'])->name('jadwal.index');
Route::get('umkm', [Landing\UmkmController::class, 'index'])->name('umkm.index');
Route::get('umkm/{page}', [Landing\UmkmController::class, 'index'])->whereNumber('page')->name('umkm.page');
Route::get('kontak', [Landing\KontakController::class, 'index'])->name('kontak.index');
Route::get('faq', [Landing\FaqController::class, 'index'])->name('faq.index');
Route::get('captcha', [CaptchaController::class, 'image'])->name('captcha');

Route::controller(User\AuthController::class)->group(function (): void {
    Route::get('login', 'login')->name('login');
    Route::post('login', 'authenticate')->name('login.authenticate');
    Route::get('daftar', 'register')->name('register');
    Route::post('daftar', 'createUser')->name('register.store');
    Route::get('lupa-sandi', 'forget')->name('password.request');
    Route::get('lupa-sandi/batal', 'cancelForgotPassword')->name('password.cancel');
    Route::post('lupa-sandi/verifikasi', 'verifyForgotPassword')->name('password.verify');
    Route::post('lupa-sandi/reset', 'resetForgotPassword')->name('password.update');
    Route::get('cek-username', 'checkUsername')->name('username.check');
    Route::get('logout', 'logout')->name('logout');
});

Route::prefix('user')->name('user.')->middleware('user')->group(function (): void {
    Route::match(['get', 'post'], 'dasbor', [User\DashboardController::class, 'index'])->name('dashboard');
    Route::post('session/keepalive', [User\AuthController::class, 'keepAlive'])->name('session.keepalive');

    Route::controller(User\ReservasiController::class)->prefix('reservasi')->name('reservasi.')->group(function (): void {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->name('store');
        Route::get('rubah/{id}', 'index')->whereNumber('id')->name('edit');
        Route::get('panel', 'panel')->name('panel');
        Route::get('panel/rubah/{id}', 'panel')->whereNumber('id')->name('panel.edit');
        Route::post('permohonan/cetak', 'printApplication')->name('application.print');
        Route::get('pembayaran/cetak/{id}/{filename}', 'printPaymentDocument')
            ->whereNumber('id')->where('filename', '[^/]+')->name('payment.print.named');
        Route::get('pembayaran/cetak/{id}', 'printPaymentDocument')->whereNumber('id')->name('payment.print');
        Route::post('pembayaran/proses', 'processPaymentMethod')->name('payment.process');
        Route::post('pembayaran/revisi', 'revisePaymentMethod')->name('payment.revise');
        Route::post('update', 'update')->name('update');
        Route::post('hapus', 'destroy')->name('destroy');
        Route::post('batal', 'cancel')->name('cancel');
    });

    Route::get('pembayaran', [User\PembayaranController::class, 'index'])->name('pembayaran.index');
    Route::post('pembayaran/upload', [User\PembayaranController::class, 'uploadProof'])->name('pembayaran.upload');
    Route::get('profil', [User\ProfilController::class, 'index'])->name('profil.index');
    Route::post('profil/foto', [User\ProfilController::class, 'updatePhoto'])->name('profil.photo.update');
    Route::post('profil/foto/reset', [User\ProfilController::class, 'resetPhoto'])->name('profil.photo.reset');
    Route::post('profil/password', [User\ProfilController::class, 'updatePassword'])->name('profil.password.update');
    Route::get('faq', [User\FaqController::class, 'index'])->name('faq.index');
    Route::get('rating', [User\RatingController::class, 'index'])->name('rating.index');
    Route::post('rating', [User\RatingController::class, 'store'])->name('rating.store');
});

require __DIR__.'/admin.php';
