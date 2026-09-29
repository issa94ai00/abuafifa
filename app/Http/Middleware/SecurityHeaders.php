<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Applies defence-in-depth response headers to every web response.
 *
 * The CSP is nonce-based for inline scripts; the nonce is generated per
 * request and exposed through `csp_nonce()` so Blade can stamp it onto
 * the Inertia bootstrap tags.
 */
class SecurityHeaders
{
    /**
     * Hosts the storefront legitimately pulls assets from.
     */
    private const FONT_HOSTS = 'https://fonts.bunny.net';

    public function handle(Request $request, Closure $next): Response
    {
        $nonce = Str::random(24);
        $request->attributes->set('csp_nonce', $nonce);

        // Let @vite stamp the same nonce on the asset tags it renders.
        Vite::useCspNonce($nonce);

        $response = $next($request);

        // Never cache authenticated back-office responses.
        if ($request->is('admin', 'admin/*') || $request->user()) {
            $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, private');
            $response->headers->set('Pragma', 'no-cache');
        }

        $headers = [
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'DENY',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            'X-Permitted-Cross-Domain-Policies' => 'none',
            'Cross-Origin-Opener-Policy' => 'same-origin',
            'Cross-Origin-Resource-Policy' => 'same-origin',
            'Permissions-Policy' => implode(', ', [
                'accelerometer=()',
                'camera=()',
                'geolocation=()',
                'gyroscope=()',
                'magnetometer=()',
                'microphone=()',
                'payment=()',
                'usb=()',
                'interest-cohort=()',
            ]),
        ];

        // HSTS only makes sense once the site is actually served over TLS.
        if ($request->secure()) {
            $headers['Strict-Transport-Security'] = 'max-age=31536000; includeSubDomains';
        }

        foreach ($headers as $name => $value) {
            $response->headers->set($name, $value);
        }

        // Leak nothing about the stack.
        $response->headers->remove('X-Powered-By');
        $response->headers->remove('Server');

        if (config('security.csp.enabled', true)) {
            $response->headers->set(
                config('security.csp.report_only', false)
                    ? 'Content-Security-Policy-Report-Only'
                    : 'Content-Security-Policy',
                $this->contentSecurityPolicy($nonce, $request)
            );
        }

        return $response;
    }

    private function contentSecurityPolicy(string $nonce, Request $request): string
    {
        // Vite's dev server needs a websocket + eval for HMR; production
        // gets the strict policy.
        $isDev = ! app()->environment('production');

        $scriptSrc = ["'self'", "'nonce-{$nonce}'", "'strict-dynamic'"];
        $connectSrc = ["'self'"];

        if ($isDev) {
            $scriptSrc[] = "'unsafe-eval'";
            $connectSrc[] = 'ws://localhost:*';
            $connectSrc[] = 'http://localhost:*';
            $connectSrc[] = 'ws://127.0.0.1:*';
            $connectSrc[] = 'http://127.0.0.1:*';
        }

        $directives = [
            "default-src 'self'",
            'script-src '.implode(' ', $scriptSrc),
            // Vue/Tailwind inject style tags at runtime, so styles stay
            // 'unsafe-inline'; scripts — the actual XSS vector — do not.
            "style-src 'self' 'unsafe-inline' ".self::FONT_HOSTS,
            'font-src '."'self' data: ".self::FONT_HOSTS,
            "img-src 'self' data: blob: https:",
            'connect-src '.implode(' ', $connectSrc),
            "media-src 'self'",
            "object-src 'none'",
            // The contact page (and the admin settings preview) embed an
            // OpenStreetMap iframe; without it here the browser blocks the
            // map and the panel renders empty.
            "frame-src 'self' https://www.google.com https://maps.google.com https://www.openstreetmap.org",
            "frame-ancestors 'none'",
            "base-uri 'self'",
            "form-action 'self'",
            "worker-src 'self' blob:",
        ];

        // Only upgrade subresources when the document itself arrived over
        // TLS. On a plain-HTTP origin this rewrites every asset URL to
        // https://, and with no TLS listener answering, the JS and CSS all
        // fail to load and the page renders blank.
        if ($request->secure()) {
            $directives[] = 'upgrade-insecure-requests';
        }

        return implode('; ', $directives);
    }
}
