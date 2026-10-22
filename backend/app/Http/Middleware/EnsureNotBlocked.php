<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Bloklangan foydalanuvchi tokeni qolib ketgan bo'lsa ham API'dan foydalana olmaydi. */
class EnsureNotBlocked
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_if($request->user()?->isBlocked(), 403, 'Akkaunt bloklangan.');

        return $next($request);
    }
}
