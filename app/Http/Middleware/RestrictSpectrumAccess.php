<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RestrictSpectrumAccess
{
    public function handle(Request $request, Closure $next)
    {
        if (! $request->user()?->hasRole('spectrum')) {
            return $next($request);
        }

        if (in_array($request->method(), ['GET', 'HEAD'], true)
            && ($request->routeIs('home') || $request->is('login'))) {
            return redirect()->route('spectrum.leads.index');
        }

        $allowedRoutes = match ($request->method()) {
            'GET', 'HEAD' => [
                'spectrum.leads.index', 'spectrum.leads.create', 'spectrum.leads.edit',
                'change-password', 'logout',
            ],
            'POST' => ['spectrum.leads.store', 'password.update'],
            'PATCH' => ['spectrum.leads.update'],
            'DELETE' => ['spectrum.leads.destroy'],
            default => [],
        };

        abort_unless(in_array($request->route()?->getName(), $allowedRoutes, true), 403);

        return $next($request);
    }
}
