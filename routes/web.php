<?php

use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;
use Illuminate\View\Middleware\ShareErrorsFromSession;

require_once base_path('app/Supports/legacy.php');

$legacy = static fn (string $target): Closure => static function (...$parameters) use ($target) {
    return legacy_dispatch($target, $parameters);
};

Route::get('assets/{path}', static function (string $path) {
    $path = trim(str_replace('\\', '/', $path), '/');

    if ($path === '' || str_contains($path, '..')) {
        abort(404);
    }

    foreach (legacy_asset_candidates($path) as $candidate) {
        $realPath = realpath($candidate);

        if ($realPath !== false && is_file($realPath)) {
            $extension = strtolower(pathinfo($realPath, PATHINFO_EXTENSION));
            $mimeTypes = [
                'css' => 'text/css; charset=UTF-8',
                'js' => 'application/javascript; charset=UTF-8',
                'json' => 'application/json; charset=UTF-8',
                'svg' => 'image/svg+xml',
                'png' => 'image/png',
                'jpg' => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'gif' => 'image/gif',
                'webp' => 'image/webp',
                'ico' => 'image/x-icon',
                'pdf' => 'application/pdf',
                'woff' => 'font/woff',
                'woff2' => 'font/woff2',
                'ttf' => 'font/ttf',
                'eot' => 'application/vnd.ms-fontobject',
            ];

            return response()->file($realPath, [
                'Content-Type' => $mimeTypes[$extension] ?? 'application/octet-stream',
            ]);
        }
    }

    abort(404);
})
    ->where('path', '.*')
    ->withoutMiddleware([
        EncryptCookies::class,
        AddQueuedCookiesToResponse::class,
        StartSession::class,
        ShareErrorsFromSession::class,
        ValidateCsrfToken::class,
        SubstituteBindings::class,
    ]);

Route::get('/', $legacy('Landing/HomeController@index'));
Route::get('gedung', $legacy('Landing/GedungController@index'));
Route::get('jadwal/events', $legacy('Landing/JadwalController@events'));
Route::get('jadwal', $legacy('Landing/JadwalController@index'));
Route::get('umkm', $legacy('Landing/UmkmController@index'));
Route::get('umkm/{page}', $legacy('Landing/UmkmController@index'))->whereNumber('page');
Route::get('kontak', $legacy('Landing/KontakController@index'));
Route::get('captcha', $legacy('Landing/CaptchaController@image'));

Route::get('login', $legacy('User/AuthController@login'));
Route::post('login', $legacy('User/AuthController@authenticate'));
Route::get('daftar', $legacy('User/AuthController@register'));
Route::post('daftar', $legacy('User/AuthController@createUser'));
Route::get('lupa-sandi', $legacy('User/AuthController@forget'));
Route::get('lupa-sandi/batal', $legacy('User/AuthController@cancelForgotPassword'));
Route::post('lupa-sandi/verifikasi', $legacy('User/AuthController@verifyForgotPassword'));
Route::post('lupa-sandi/reset', $legacy('User/AuthController@resetForgotPassword'));
Route::get('cek-username', $legacy('User/AuthController@checkUsername'));
Route::get('logout', $legacy('User/AuthController@logout'));
Route::post('user/session/keepalive', $legacy('User/AuthController@keepAlive'));

Route::match(['get', 'post'], 'user/dasbor', $legacy('User/DashboardController@index'));
Route::get('user/reservasi', $legacy('User/ReservasiController@index'));
Route::get('user/reservasi/rubah/{id}', $legacy('User/ReservasiController@index'))->whereNumber('id');
Route::get('user/reservasi/panel', $legacy('User/ReservasiController@panel'));
Route::get('user/reservasi/panel/rubah/{id}', $legacy('User/ReservasiController@panel'))->whereNumber('id');
Route::post('user/reservasi/permohonan/cetak', $legacy('User/ReservasiController@printApplication'));
Route::get('user/reservasi/pembayaran/cetak/{id}/{filename}', $legacy('User/ReservasiController@printPaymentDocument'))
    ->whereNumber('id')
    ->where('filename', '[^/]+');
Route::get('user/reservasi/pembayaran/cetak/{id}', $legacy('User/ReservasiController@printPaymentDocument'))->whereNumber('id');
Route::post('user/reservasi', $legacy('User/ReservasiController@store'));
Route::post('user/reservasi/update', $legacy('User/ReservasiController@update'));
Route::post('user/reservasi/hapus', $legacy('User/ReservasiController@destroy'));
Route::post('user/reservasi/batal', $legacy('User/ReservasiController@cancel'));
Route::get('user/pembayaran', $legacy('User/PembayaranController@index'));
Route::post('user/pembayaran/upload', $legacy('User/PembayaranController@uploadProof'));
Route::get('user/profil', $legacy('User/ProfilController@index'));
Route::post('user/profil/foto', $legacy('User/ProfilController@updatePhoto'));
Route::post('user/profil/foto/reset', $legacy('User/ProfilController@resetPhoto'));
Route::post('user/profil/password', $legacy('User/ProfilController@updatePassword'));
Route::get('user/faq', $legacy('User/FaqController@index'));
Route::get('user/rating', $legacy('User/RatingController@index'));
Route::post('user/rating', $legacy('User/RatingController@store'));

Route::get('admin', $legacy('Admin/DashboardController@index'));
Route::get('admin/login', $legacy('Admin/AuthController@login'));
Route::post('admin/login', $legacy('Admin/AuthController@authenticate'));
Route::get('admin/logout', $legacy('Admin/AuthController@logout'));
Route::get('admin/dasbor', $legacy('Admin/DashboardController@index'));
Route::get('admin/kalender', $legacy('Admin/DashboardController@calendar'));
Route::get('admin/laporan/rating', $legacy('Admin/LaporanController@rating'));
Route::get('admin/laporan/rating/gedung', $legacy('Admin/LaporanController@gedung'));
Route::get('admin/laporan/rating/umkm', $legacy('Admin/LaporanController@umkm'));
Route::get('admin/pengaturan/akses', $legacy('Admin/DashboardController@accessSettings'));
Route::post('admin/pengaturan/akses', $legacy('Admin/DashboardController@saveAccessSettings'));
Route::get('admin/reservasi', $legacy('Admin/ReservasiController@index'));
Route::get('admin/riwayat/gedung', $legacy('Admin/ReservasiController@gedung'));
Route::get('admin/riwayat/umkm', $legacy('Admin/ReservasiController@umkm'));
Route::post('admin/reservasi/setujui', $legacy('Admin/ReservasiController@approve'));
Route::post('admin/reservasi/kembali', $legacy('Admin/ReservasiController@returnToApplicant'));
Route::post('admin/reservasi/tolak', $legacy('Admin/ReservasiController@reject'));
Route::post('admin/riwayat/gedung/hapus', $legacy('Admin/ReservasiController@destroy'));
Route::post('admin/riwayat/umkm/hapus', $legacy('Admin/ReservasiController@destroyUmkm'));
Route::get('admin/pembayaran', $legacy('Admin/PembayaranController@index'));
Route::post('admin/pembayaran/lunas', $legacy('Admin/PembayaranController@markAsPaid'));
Route::post('admin/pembayaran/kembali', $legacy('Admin/PembayaranController@returnToApplicant'));
Route::get('admin/verifikasi', $legacy('Admin/VerifikasiController@index'));
Route::post('admin/verifikasi/setuju', $legacy('Admin/VerifikasiController@approve'));
Route::post('admin/verifikasi/kembali', $legacy('Admin/VerifikasiController@returnToApplicant'));
Route::post('admin/session/keepalive', $legacy('Admin/AuthController@keepAlive'));
