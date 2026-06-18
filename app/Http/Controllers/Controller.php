<?php

namespace App\Http\Controllers;

use Illuminate\Routing\Controller as BaseController;
use InvalidArgumentException;

abstract class Controller extends BaseController
{
    protected function model(string $model): object
    {
        $modelPath = base_path('app/Models/' . $model . '.php');

        if (is_file($modelPath)) {
            require_once $modelPath;
        }

        if (!class_exists($model)) {
            throw new InvalidArgumentException("Model [{$model}] tidak ditemukan.");
        }

        return new $model();
    }

    protected function view(string $view, array $data = []): void
    {
        if (!headers_sent()) {
            header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
            header('Pragma: no-cache');
            header('Expires: 0');
            header('Vary: X-PJAX, X-Requested-With, X-UMKM-Section');
        }

        echo view($view, $data)->render();
    }

    protected function redirect(string $path): void
    {
        header('Location: ' . base_url($path));
        exit;
    }
}
