<?php

namespace App\Providers;

use App\Listeners\AuthEventSubscriber;
use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\ContactMessage;
use App\Models\Gallery;
use App\Models\Media;
use App\Models\Offer;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use App\Policies\ActivityLogPolicy;
use App\Policies\CategoryPolicy;
use App\Policies\ContactMessagePolicy;
use App\Policies\GalleryPolicy;
use App\Policies\MediaPolicy;
use App\Policies\OfferPolicy;
use App\Policies\ProductPolicy;
use App\Policies\SettingPolicy;
use App\Policies\UserPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Policies are registered explicitly rather than relying on
     * auto-discovery, so a renamed class fails loudly instead of
     * silently falling back to "no policy, allow nothing/everything".
     *
     * @var array<class-string, class-string>
     */
    private const POLICIES = [
        Product::class => ProductPolicy::class,
        Category::class => CategoryPolicy::class,
        Offer::class => OfferPolicy::class,
        Gallery::class => GalleryPolicy::class,
        Media::class => MediaPolicy::class,
        ContactMessage::class => ContactMessagePolicy::class,
        User::class => UserPolicy::class,
        Setting::class => SettingPolicy::class,
        ActivityLog::class => ActivityLogPolicy::class,
    ];

    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        $this->registerPolicies();
        $this->configurePasswordPolicy();
        $this->configureRateLimiting();
        $this->configureModels();
        $this->configureHttps();

        Event::subscribe(AuthEventSubscriber::class);
    }

    private function registerPolicies(): void
    {
        foreach (self::POLICIES as $model => $policy) {
            Gate::policy($model, $policy);
        }
    }

    /**
     * One password policy for the whole application: registration, admin
     * user creation, reset and change all inherit it via
     * `Password::defaults()`.
     */
    private function configurePasswordPolicy(): void
    {
        Password::defaults(function () {
            $rule = Password::min(12)
                ->letters()
                ->mixedCase()
                ->numbers()
                ->symbols();

            // The have-i-been-pwned lookup needs network access, so it is
            // only enforced where that is dependable.
            return $this->app->isProduction()
                ? $rule->uncompromised()
                : $rule;
        });
    }

    /**
     * Named limiters referenced from the route definitions.
     */
    private function configureRateLimiting(): void
    {
        // Login: per identifier+IP (stops brute force on one account) and
        // per IP (stops spraying across many accounts).
        RateLimiter::for('login', function (Request $request) {
            $identifier = (string) $request->input('email', $request->input('phone', ''));

            return [
                Limit::perMinutes(
                    max(1, (int) (config('security.login.decay_seconds') / 60)),
                    (int) config('security.login.max_attempts')
                )->by(mb_strtolower($identifier).'|'.$request->ip()),

                Limit::perMinutes(
                    max(1, (int) (config('security.login.decay_seconds') / 60)),
                    (int) config('security.login.max_attempts_per_ip')
                )->by('ip:'.$request->ip()),
            ];
        });

        RateLimiter::for('password-reset', fn (Request $request) => [
            Limit::perHour(5)->by(mb_strtolower((string) $request->input('email')).'|'.$request->ip()),
            Limit::perHour(15)->by('ip:'.$request->ip()),
        ]);

        RateLimiter::for('register', fn (Request $request) => Limit::perHour(5)->by($request->ip()));

        // Public contact form — abuse target for spam.
        RateLimiter::for('contact', fn (Request $request) => [
            Limit::perMinute(3)->by($request->ip()),
            Limit::perDay(20)->by($request->ip()),
        ]);

        // Back-office writes. Generous enough for real work, low enough
        // to blunt a scripted attack with a stolen session.
        RateLimiter::for('admin-write', fn (Request $request) => Limit::perMinute(60)
            ->by($request->user()?->getAuthIdentifier() ?: $request->ip()));

        RateLimiter::for('uploads', fn (Request $request) => Limit::perMinute(20)
            ->by($request->user()?->getAuthIdentifier() ?: $request->ip()));

        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(60)
            ->by($request->user()?->getAuthIdentifier() ?: $request->ip()));

        RateLimiter::for('search', fn (Request $request) => Limit::perMinute(30)->by($request->ip()));
    }

    private function configureModels(): void
    {
        // Fail loudly when code tries to mass assign an attribute that is
        // not fillable, instead of silently dropping it — that silence is
        // what turns a typo into a missing security flag.
        Model::preventSilentlyDiscardingAttributes(! $this->app->isProduction());
    }

    private function configureHttps(): void
    {
        if ($this->app->isProduction() || config('app.force_https', false)) {
            URL::forceScheme('https');
            request()?->server->set('HTTPS', 'on');
        }
    }
}
