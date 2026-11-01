<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\IpUtils;
use Symfony\Component\HttpFoundation\Response;

/**
 * Optional IP allowlist for the admin panel (GOVOYZA_ADMIN_IPS, comma-separated
 * IPs or CIDR ranges). Empty = open to all. Others get a plain 404, so the
 * panel's existence isn't confirmed.
 *
 * Behind a proxy/CDN, $request->ip() is only the visitor's IP when that proxy is
 * listed in TRUSTED_PROXIES.
 */
class AdminIpAllowlist
{
    public function handle(Request $request, Closure $next): Response
    {
        $allowed = config('govoyza.admin.allowed_ips');

        if ($allowed && ! IpUtils::checkIp((string) $request->ip(), $allowed)) {
            abort(404);
        }

        return $next($request);
    }
}
