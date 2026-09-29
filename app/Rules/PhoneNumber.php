<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * A dialable phone number.
 *
 * `string|max:30` let anything through, so a number that no dialer could
 * use — or a WhatsApp number with no country code, which produces a dead
 * wa.me link in the storefront footer — was accepted silently.
 *
 * With $international the number must carry a country code, because the
 * value is turned into a https://wa.me/<digits> URL where a local number
 * such as 079… resolves to nothing.
 */
class PhoneNumber implements ValidationRule
{
    public function __construct(
        private readonly bool $international = false,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        if (! is_string($value)) {
            $fail('رقم الهاتف غير صالح.');

            return;
        }

        // Only the characters people actually write numbers with.
        if (preg_match('/[^0-9+\-()\s\.]/u', $value)) {
            $fail('رقم الهاتف يحتوي على رموز غير مسموحة. استخدم الأرقام و + و - والمسافات فقط.');

            return;
        }

        $digits = self::digits($value);

        if (strlen($digits) < 8 || strlen($digits) > 15) {
            $fail('رقم الهاتف يجب أن يتكون من 8 إلى 15 رقماً.');

            return;
        }

        if ($this->international && ! self::hasCountryCode($value)) {
            $fail('يجب أن يبدأ الرقم برمز الدولة حتى يعمل رابط واتساب، مثال: +962 7 9012 3456');
        }
    }

    /**
     * The digits a `wa.me` link needs: no separators, no leading 00 or 0.
     */
    public static function digits(string $value): string
    {
        $digits = preg_replace('/\D/', '', $value) ?? '';

        // 00962… and +962… are the same number in two notations.
        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }

        return $digits;
    }

    /**
     * A leading + or 00 is an explicit country code. Without either, a
     * leading 0 marks a local trunk prefix, which is not dialable abroad.
     */
    public static function hasCountryCode(string $value): bool
    {
        $trimmed = ltrim($value);

        if (str_starts_with($trimmed, '+') || str_starts_with(preg_replace('/\D/', '', $trimmed) ?? '', '00')) {
            return true;
        }

        return ! str_starts_with(preg_replace('/\D/', '', $trimmed) ?? '', '0');
    }
}
