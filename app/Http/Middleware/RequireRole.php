<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->guest(route('login'));
        }

        if (! in_array($user->role, $roles, true)) {
            abort(403, 'You do not have access to this farm workspace.');
        }

        if ($user->role === 'farmer' && in_array('farmer', $roles, true) && ! $user->farmer_verified_at && ! $request->routeIs('farmer.pending')) {
            return redirect()->route('farmer.pending');
        }

        return $next($request);
    }
}
