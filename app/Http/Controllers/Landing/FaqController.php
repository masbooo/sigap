<?php

namespace App\Http\Controllers\Landing;

use App\Http\Controllers\Controller;

class FaqController extends Controller
{
    public function index()
    {
        $this->view('landing.faq', [
            'title' => 'SIGAP - Tanya',
        ]);
    }
}
