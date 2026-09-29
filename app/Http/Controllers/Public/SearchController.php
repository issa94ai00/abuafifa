<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Services\CatalogService;
use App\Services\SeoService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SearchController extends Controller
{
    public function __construct(
        private readonly CatalogService $catalog,
        private readonly SeoService $seo
    ) {}

    public function __invoke(Request $request)
    {
        $term = $request->string('q')->trim()->toString();

        // abort(302) produces a redirect response with no Location header,
        // which browsers treat as a dead end. Send the visitor somewhere.
        if ($term === '') {
            return redirect()->route('products.index');
        }

        $results = $this->catalog->products(['term' => $term, 'per_page' => 24]);

        return Inertia::render('Public/Search', [
            'term'    => $term,
            'results' => $results,
            'seo'     => $this->seo->forPage('search'),
        ]);
    }
}