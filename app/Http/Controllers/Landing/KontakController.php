<?php

class KontakController extends Controller
{
    public function index()
    {
        $kontakModel = $this->model('Kontak');

        $contactGroup = $kontakModel->getGroupedContacts();
        $allContacts = $kontakModel->getAllContactsFlat();

        $this->view('landing.kontak', [
            'title' => 'SIGAP - Kontak',
            'contactGroup' => $contactGroup,
            'allContacts' => $allContacts,
            'mapDefault' => [
                'lat' => -7.2756,
                'lng' => 112.7508,
                'zoom' => 12,
            ],
        ]);
    }
}
