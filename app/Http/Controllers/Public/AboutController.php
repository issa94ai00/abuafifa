<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Services\SeoService;
use Inertia\Inertia;

class AboutController extends Controller
{
    public function __construct(
        private readonly SeoService $seo
    ) {}

    public function __invoke()
    {
        return Inertia::render('Public/About', [
            'about' => [
                'intro' => setting('homepage.about_intro_ar'),
            ],
            'seo' => $this->seo->forPage('about'),
        ]);
    }
}