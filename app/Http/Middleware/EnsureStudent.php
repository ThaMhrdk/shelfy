<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureStudent
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->isAdmin()) {
            abort(403, 'Halaman ini khusus bagian mahasiswa.');
        }

        return $next($request);
    }
}
