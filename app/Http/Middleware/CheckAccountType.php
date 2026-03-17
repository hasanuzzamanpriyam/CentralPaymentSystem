<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckAccountType
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $type = null): Response
    {
        if (! $request->user()) {
            return redirect()->route('login');
        }

        // If a specific type is required and the user doesn't match, abort or redirect.
        if ($type && $request->user()->account_type !== $type) {
            return redirect('/dashboard');
        }

        return $next($request);
    }
}
