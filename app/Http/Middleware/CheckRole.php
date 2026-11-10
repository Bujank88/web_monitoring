<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle($request, Closure $next, ...$roles)
    {
        if (!Auth::check()) {
            return redirect('login'); // kalau belum login
        }

        $user = Auth::user();

        // Product uses a report allowlist, including data and export endpoints.
        if ($user->hasRole('product')) {
            abort_unless(\App\Support\ProductAccess::allows($request), 403);

            return $next($request);
        }

        // cek role
        if (in_array($user->role, $roles)) {
            return $next($request);
        }

        // kalau role tidak sesuai, arahkan ke halaman default
        return redirect('/')->with('error', 'Anda tidak punya akses ke halaman ini.');
    }
}
