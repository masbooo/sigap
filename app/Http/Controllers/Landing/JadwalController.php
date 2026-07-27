<?php

namespace App\Http\Controllers\Landing;

use App\Http\Controllers\Controller;
use DateTime;
use Throwable;

class JadwalController extends Controller
{
    public function index()
    {
        try {
            $jadwalModel = $this->model('Jadwal');

            $filterData = $jadwalModel->getFilterData();
            $events = $jadwalModel->getCalendarEvents();
            $minBookingDate = (new DateTime('today'))->modify('+14 days')->format('Y-m-d');

            $this->view('landing.jadwal', [
                'title' => 'SIGAP - Gedung',
                'filterData' => $filterData,
                'events' => $events,
                'minBookingDate' => $minBookingDate,
            ]);
        } catch (Throwable $e) {
            report($e);
            abort(500, 'Gagal memuat halaman jadwal.');
        }
    }

    public function events()
    {
        try {
            $jadwalModel = $this->model('Jadwal');
            $events = $jadwalModel->getCalendarEvents();

            return response()
                ->json($events, 200, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'error' => 'Gagal memuat data jadwal',
            ], 500, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
    }
}
