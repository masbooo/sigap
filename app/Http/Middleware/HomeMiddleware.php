<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class HomeMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (function_exists('is_admin_logged_in') && is_admin_logged_in()) {
            return redirect()->route('admin.dashboard');
        }

        return $next($request);
    }
}
