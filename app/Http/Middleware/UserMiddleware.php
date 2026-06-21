<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class UserMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!function_exists('is_user_logged_in') || !is_user_logged_in()) {
            return redirect()->route('login');
        }

        return $next($request);
    }
}
