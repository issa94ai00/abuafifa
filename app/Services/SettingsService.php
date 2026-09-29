<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class SettingsService
{
    /**
     * Retrieve a setting value (cached per group).
     *
     * Keys are stored in the form "group.key".
     */
    public function get(string $key, mixed $default = null, ?string $locale = null): mixed
    {
        $locale = $locale ?? app()->getLocale();

        [$group, $settingKey] = $this->parseKey($key);

        $values = $this->allForGroup($group, $locale);

        return $values[$settingKey] ?? $default;
    }

    public function set(string $group, string $key, mixed $value, string $locale = 'ar'): static
    {
        Setting::updateOrCreate(
            ['group' => $group, 'key' => $key, 'locale' => $locale],
            ['value' => $this->serializeValue($value)]
        );

        return $this;
    }

    /**
     * All settings for a group, keyed by `key` => `value` (cast).
     */
    public function allForGroup(string $group, string $locale = 'ar'): array
    {
        $cacheKey = "settings.{$group}.{$locale}";

        return Cache::remember($cacheKey, 3600, fn () => collect(
            Setting::where('group', $group)->where('locale', $locale)->get()
        )->pluck('value', 'key')->all());
    }

    /**
     * Forget cached settings.
     *
     * With no arguments every group is cleared; passing a $group narrows it to
     * that group, and passing a $locale narrows it further to that locale.
     */
    public function flush(string $group = '', string $locale = ''): void
    {
        $groups = $group !== ''
            ? [$group]
            : Setting::query()->distinct()->pluck('group')->all();

        $locales = $locale !== '' ? [$locale] : ['ar', 'en'];

        foreach ($groups as $g) {
            foreach ($locales as $l) {
                Cache::forget("settings.{$g}.{$l}");
            }
        }
    }

    public function destroy(string $group, string $key, string $locale = 'ar'): void
    {
        Setting::where('group', $group)->where('key', $key)->where('locale', $locale)->delete();
        $this->flush($group, $locale);
    }

    // --------------------------------------------------------------- helpers

    /**
     * Shorthand: resolve 'site.currency' or 'contact.whatsapp' quickly.
     */
    public function site(): static
    {
        return $this;
    }

    public function contact(): static
    {
        return $this;
    }

    // ------------------------------------------------------------- internal

    private function parseKey(string $key): array
    {
        $parts = explode('.', $key);

        if (count($parts) >= 2) {
            $group = array_shift($parts);

            return [$group, implode('.', $parts)];
        }

        // fallback: treat entire key as group="site", key=value name
        return ['site', $key];
    }

    private function serializeValue(mixed $value): ?string
    {
        if (is_array($value) || is_object($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE);
        }

        return $value === null ? null : (string) $value;
    }
}