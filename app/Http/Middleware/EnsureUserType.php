<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Picks the portal. Usage: ->middleware('type:customer') or 'type:admin,agent_user' */
class EnsureUserType
{
    public function handle(Request $request, Closure $next, string ...$types): Response
    {
        $type = $request->user()?->type?->value;

        abort_unless($type && in_array($type, $types, true), 403, 'You do not have access to this area.');

        return $next($request);
    }
}
