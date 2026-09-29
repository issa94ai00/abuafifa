<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class LocaleController extends Controller
{
    /**
     * Switch the active locale.
     *
     * The locale arrives as a *route parameter*, which Request::validate()
     * does not see — it only inspects query/body input. Validating it that
     * way rejected every switch. The route already constrains the segment
     * with whereIn(); this re-checks the value directly so the guard holds
     * even if the route constraint is ever relaxed.
     */
    public function switch(Request $request, string $locale)
    {
        abort_unless(in_array($locale, ['ar', 'en'], true), 404);

        $request->session()->put('locale', $locale);
        App::setLocale($locale);

        // A signed-in user's stored preference wins in SetLocale, so it has
        // to move too — otherwise the switch silently reverts next request.
        if ($user = $request->user()) {
            $user->forceFill(['locale' => $locale])->saveQuietly();
        }

        return back();
    }
}
