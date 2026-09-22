<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string ...$roles): mixed
    {
        $user = Auth::user();

        if (! $user || ! in_array($user->role, $roles, true)) {
            abort(403, 'Akses ditolak.');
        }

        return $next($request);
    }
}
