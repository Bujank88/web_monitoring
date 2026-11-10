<?php

namespace App\Http\Middleware;

use App\Support\ProductAccess;
use Closure;
use Illuminate\Http\Request;

class RestrictProductAccess
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->user()?->hasRole('product')) {
            abort_unless(ProductAccess::allows($request), 403);
        }

        return $next($request);
    }
}
