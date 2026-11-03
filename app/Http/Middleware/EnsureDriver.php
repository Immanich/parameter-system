<?php

namespace App\Http\Middleware;

use App\Models\Driver;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureDriver
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->user() instanceof Driver, 403, 'Driver token required.');

        return $next($request);
    }
}
