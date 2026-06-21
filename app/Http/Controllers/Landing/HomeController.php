<?php

namespace App\Http\Controllers\Landing;

use App\Http\Controllers\Controller;

class HomeController extends Controller
{
    public function index()
    {
        $gedungModel = $this->model('Gedung');
        $umkmModel = $this->model('Umkm');
        $allGedung = $gedungModel->getAllActive();
        $featuredGedung = array_slice($allGedung, 0, 6);
        $heroUmkmThumbnails = $umkmModel->getRandomHeroThumbnails(3);

        $this->view('landing.index', [
            'title' => 'SIGAP - Sistem Informasi Gedung dan Prasarana',
            'featuredGedung' => $featuredGedung,
            'heroUmkmThumbnails' => $heroUmkmThumbnails,
        ]);
    }
}
