<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** `has_role:parent` — Sanctum foydalanuvchisida rol borligini tekshiradi. */
class EnsureRole
{
    public function handle(Request $request, Closure $next, string $role): Response
    {
        abort_unless($request->user()?->hasRole($role), 403, "Bu amal uchun '{$role}' roli kerak.");

        return $next($request);
    }
}
