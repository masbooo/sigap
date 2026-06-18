<?php

class HomeMiddleware
{
    public function handle(): void
    {
        if (is_admin_logged_in()) {
            header('Location: ' . base_url('admin/dasbor'));
            exit;
        }
    }
}
