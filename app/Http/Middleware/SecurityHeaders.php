<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Usage: 'SecurityHeaders' for the site, SecurityHeaders::class.':panel' for Filament.
     * Filament (Livewire + Alpine) needs inline scripts and eval, so panels get a looser
     * script-src; everything else stays the same.
     */
    public function handle(Request $request, Closure $next, ?string $variant = null): Response
    {
        $response = $next($request);

        $scripts = $variant === 'panel' ? "script-src 'self' 'unsafe-inline' 'unsafe-eval'" : "script-src 'self'";

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
        $response->headers->set('Content-Security-Policy', implode('; ', [
            "default-src 'self'",
            "img-src 'self' data: blob:",
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com",
            "font-src 'self' https://fonts.gstatic.com",
            $scripts,
            "connect-src 'self'",
            "frame-ancestors 'none'",
            "form-action 'self'",
            "base-uri 'self'",
        ]));

        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        $privateDomains = [
            config('govoyza.domains.partner'),
            config('govoyza.domains.admin'),
        ];

        if (in_array($request->getHost(), $privateDomains, true)) {
            $response->headers->set('X-Robots-Tag', 'noindex');
        }

        return $response;
    }
}
