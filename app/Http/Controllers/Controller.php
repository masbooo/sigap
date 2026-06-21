<?php

namespace App\Http\Controllers;

use Illuminate\Routing\Controller as BaseController;
use Illuminate\Contracts\Support\Responsable;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

abstract class Controller extends BaseController
{
    protected function model(string $model): object
    {
        $modelClass = 'App\\Models\\' . ltrim($model, '\\');

        if (!class_exists($modelClass)) {
            throw new InvalidArgumentException("Model [{$model}] tidak ditemukan.");
        }

        return app($modelClass);
    }

    /**
     * Menjaga controller lama yang masih menulis output secara langsung tetap
     * kompatibel saat dipanggil oleh dispatcher controller Laravel.
     */
    public function callAction($method, $parameters): mixed
    {
        ob_start();

        try {
            $result = parent::callAction($method, $parameters);
            $output = ob_get_clean();
        } catch (Throwable $exception) {
            if (ob_get_level() > 0) {
                ob_end_clean();
            }

            throw $exception;
        }

        if ($result instanceof Response) {
            return $result;
        }

        if ($result instanceof Responsable) {
            return $result->toResponse(request());
        }

        return $result ?? response($output);
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
