<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Usage: ->middleware('role:finance') or 'role:owner,finance'. Portals use 'type:' instead. */
class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        abort_unless($user && $user->hasRole(...$roles), 403, 'You do not have access to this area.');

        return $next($request);
    }
}
