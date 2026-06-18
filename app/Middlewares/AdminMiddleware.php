<?php

class AdminMiddleware
{
    public function handle(): void
    {
        if (!is_admin_logged_in()) {
            header('Location: ' . base_url('admin/login'));
            exit;
        }
    }
}