<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class GarantirVendedor
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user() || $request->user()->tipo !== 'vendedor') {
            abort(403);
        }

        return $next($request);
    }
}
