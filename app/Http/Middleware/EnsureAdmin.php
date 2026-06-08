<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->isStaff()) {
            abort(403, 'Halaman ini khusus bagian admin atau pustakawan.');
        }

        return $next($request);
    }
}
