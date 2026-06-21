<?php

namespace App\Http\Controllers\Landing;

use App\Http\Controllers\Controller;

class GedungController extends Controller
{
    public function index()
    {
        $gedungModel = $this->model('Gedung');
        $gedungGroup = $gedungModel->getGroupedByRegion();

        $this->view('landing.gedung', [
            'title' => 'SIGAP - Gedung',
            'gedungGroup' => $gedungGroup,
        ]);
    }
}
