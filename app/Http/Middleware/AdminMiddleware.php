<?php

// app/Http/Middleware/AdminMiddleware.php :contentReference[oaicite:2]{index=2}:contentReference[oaicite:3]{index=3}

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        // Si no está autenticado o no es admin, lo enviamos al home
        if (! Auth::check() || ! Auth::user()->isAdmin()) {
            return redirect()->route('home');
        }

        return $next($request);
    }
}
