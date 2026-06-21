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
            echo '<pre>';
            print_r([
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            echo '</pre>';
            die;
        }
    }

    public function events()
    {
        try {
            $jadwalModel = $this->model('Jadwal');
            $events = $jadwalModel->getCalendarEvents();

            header('Content-Type: application/json; charset=utf-8');
            header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

            echo json_encode(
                $events,
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            );
        } catch (Throwable $e) {
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');

            echo json_encode([
                'error' => 'Gagal memuat data jadwal',
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        exit;
    }
}
