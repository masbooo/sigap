<?php

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
