<?php

$router->get('/admin', 'admin/DashboardController@index');
$router->get('/admin/gedung', 'admin/GedungController@index');
$router->get('/admin/jadwal', 'admin/JadwalController@index');
$router->get('/admin/reservasi', 'admin/ReservasiController@index');
$router->get('/admin/riwayat/gedung', 'admin/ReservasiController@gedung');
$router->get('/admin/riwayat/umkm', 'admin/ReservasiController@umkm');
$router->get('/admin/pembayaran', 'admin/PembayaranController@index');
$router->get('/admin/verifikasi', 'admin/VerifikasiController@index');

$router->post('/admin/reservasi/setujui', 'admin/ReservasiController@approve');
$router->post('/admin/reservasi/kembali', 'admin/ReservasiController@returnToApplicant');
$router->post('/admin/reservasi/tolak', 'admin/ReservasiController@reject');
$router->post('/admin/riwayat/gedung/hapus', 'admin/ReservasiController@destroy');
$router->post('/admin/riwayat/umkm/hapus', 'admin/ReservasiController@destroyUmkm');
$router->post('/admin/verifikasi/setuju', 'admin/VerifikasiController@approve');
$router->post('/admin/verifikasi/kembali', 'admin/VerifikasiController@returnToApplicant');
$router->post('/admin/pembayaran/lunas', 'admin/PembayaranController@markAsPaid');
$router->post('/admin/pembayaran/kembali', 'admin/PembayaranController@returnToApplicant');
$router->post('/admin/session/keepalive', 'admin/AuthController@keepAlive');
