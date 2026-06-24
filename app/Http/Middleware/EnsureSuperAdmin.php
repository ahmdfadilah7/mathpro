<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSuperAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(
            $request->user()?->role?->slug === 'super-admin',
            403,
            'Hanya Super Admin yang dapat mengakses halaman ini.'
        );

        return $next($request);
    }
}
