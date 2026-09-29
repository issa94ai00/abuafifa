<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\Public\StoreContactMessageRequest;
use App\Models\ContactMessage;
use App\Services\SeoService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ContactController extends Controller
{
    public function __construct(
        private readonly SeoService $seo
    ) {}

    public function index()
    {
        $contact = [
            'phone'         => setting('contact.phone'),
            'whatsapp'      => setting('contact.whatsapp'),
            'email'         => setting('contact.email', setting('site.email')),
            'address'       => setting('contact.address_ar'),
            'working_hours' => setting('contact.working_hours'),
            'map_lat'       => setting('contact.map_lat', 29.5267),
            'map_lng'       => setting('contact.map_lng', 35.0078),
        ];

        return Inertia::render('Public/Contact', [
            'contact' => $contact,
            'seo'     => $this->seo->forPage('contact'),
        ]);
    }

    public function store(StoreContactMessageRequest $request)
    {
        ContactMessage::create($request->validated() + [
            'ip_address' => $request->ip(),
        ]);

        return back()->with('success', 'تم إرسال رسالتك بنجاح، سنتواصل معك قريباً.');
    }
}