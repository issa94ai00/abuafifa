<?php

use App\Services\SettingsService;

/**
 * Retrieve a global setting value.
 *
 * Keys are "group.key"; a bare key is treated as group "site".
 *
 * Usage:  setting('site.currency', 'JOD')
 *         setting('contact.whatsapp')
 *         setting('seo.default_title', $fallback, 'en')
 *
 * The third argument is the LOCALE, not a second fallback key.
 */
function setting(string $key, mixed $default = null, ?string $locale = null): mixed
{
    return app(SettingsService::class)->get($key, $default, $locale);
}

/**
 * Format an amount with the configured currency.
 */
function format_price(float $amount, string $locale = 'ar'): string
{
    // setting()'s third argument is the *locale*, not a fallback key.
    // Passing 'site.currency' there made the lookup miss and return the
    // key itself as the "currency", which then became the symbol.
    $currency = setting('site.currency', 'JOD');

    $symbol = match ($currency) {
        'JOD' => 'د.أ',
        'SYP' => 'ل.س',
        'USD' => '$',
        'EUR' => '€',
        default => $currency,
    };

    $formatted = number_format($amount, 0, '.', ',');

    return $locale === 'ar' ? "{$formatted} {$symbol}" : "{$symbol} {$formatted}";
}
/**
 * The per-request Content-Security-Policy nonce set by the
 * SecurityHeaders middleware. Stamp it on any inline <script>.
 */
function csp_nonce(): string
{
    return (string) request()->attributes->get('csp_nonce', '');
}

/**
 * Convenience accessor for the audit trail.
 *
 *   activity()->log(ActivityAction::SettingsUpdated, $setting);
 */
function activity(): \App\Services\ActivityLogger
{
    return app(\App\Services\ActivityLogger::class);
}
