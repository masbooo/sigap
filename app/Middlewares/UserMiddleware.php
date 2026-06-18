<?php

class UserMiddleware
{
    public function handle(): void
    {
        if (!is_user_logged_in()) {
            header('Location: ' . base_url('login'));
            exit;
        }
    }
}