<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ActivityAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateSettingsRequest;
use App\Models\Setting;
use App\Models\SocialLink;
use App\Services\ActivityLogger;
use App\Services\MediaService;
use App\Services\SettingsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class SettingController extends Controller
{
    /**
     * The only groups that may be written. A group name arrives from the
     * URL, so it is matched against this list rather than trusted.
     */
    private const GROUPS = ['site', 'contact', 'seo', 'homepage'];

    /**
     * Setting keys that hold an uploaded image.
     */
    private const IMAGE_KEYS = ['logo', 'favicon', 'og_image', 'hero_image'];

    public function __construct(
        private readonly SettingsService $settings,
        private readonly MediaService $mediaService,
        private readonly ActivityLogger $logger,
    ) {}

    public function index(Request $request)
    {
        Gate::authorize('viewAny', Setting::class);

        $group = $request->string('group')->toString() ?: 'site';

        abort_unless(in_array($group, self::GROUPS, true), 404);

        return Inertia::render('Admin/Settings/Index', [
            'group' => $group,
            'settings' => $this->settings->allForGroup($group),
            'social_links' => SocialLink::query()->orderBy('sort_order')->get(),
            'groups' => self::GROUPS,
        ]);
    }

    public function update(UpdateSettingsRequest $request)
    {
        Gate::authorize('update', Setting::class);

        $group = (string) $request->route('group');

        abort_unless(in_array($group, self::GROUPS, true), 404);

        $data = $request->validated();
        $before = $this->settings->allForGroup($group);

        foreach (self::IMAGE_KEYS as $imageKey) {
            if ($request->hasFile($imageKey)) {
                $media = $this->mediaService->storeOne($request->file($imageKey), 'setting');
                $data[$imageKey] = $media->url;
            } else {
                // Never persist the raw upload placeholder when no file came.
                unset($data[$imageKey]);
            }
        }

        DB::transaction(function () use ($group, $data) {
            foreach ($data as $key => $value) {
                $this->settings->set($group, $key, $value);
            }
        });

        $this->settings->flush($group);

        $this->logger->log(
            ActivityAction::SettingsUpdated,
            null,
            [
                'group' => $group,
                ...$this->logger->diff($before, $this->settings->allForGroup($group)),
            ],
            description: "تعديل إعدادات المجموعة: {$group}",
        );

        return back()->with('success', 'تم تحديث الإعدادات بنجاح.');
    }

    public function updateSocialLinks(Request $request)
    {
        Gate::authorize('update', Setting::class);

        $validated = $request->validate([
            'links' => ['present', 'array', 'max:20'],
            'links.*.id' => ['nullable', 'integer', 'exists:social_links,id'],
            'links.*.platform' => ['required', 'string', 'max:60', 'alpha_dash'],
            // Only http(s): a `javascript:` or `data:` URL here would be
            // rendered as a link in the storefront footer.
            'links.*.url' => ['required', 'url:http,https', 'max:500'],
            'links.*.icon' => ['nullable', 'string', 'max:100', 'regex:/^[A-Za-z0-9_\-]+$/'],
            'links.*.label_ar' => ['nullable', 'string', 'max:100'],
            'links.*.label_en' => ['nullable', 'string', 'max:100'],
            'links.*.sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'links.*.is_active' => ['nullable', 'boolean'],
        ]);

        $links = collect($validated['links']);
        $before = SocialLink::query()->orderBy('sort_order')->get()->toArray();

        DB::transaction(function () use ($links) {
            $keptIds = $links->pluck('id')->filter()->all();

            SocialLink::query()->whereNotIn('id', $keptIds ?: [0])->delete();

            foreach ($links as $index => $link) {
                $attributes = [
                    'platform' => $link['platform'],
                    'url' => $link['url'],
                    'icon' => $link['icon'] ?? null,
                    'label_ar' => $link['label_ar'] ?? null,
                    'label_en' => $link['label_en'] ?? null,
                    'sort_order' => $link['sort_order'] ?? $index,
                    'is_active' => $link['is_active'] ?? true,
                ];

                if (! empty($link['id'])) {
                    // updateOrCreate on the id, so a stale id cannot
                    // silently create a row with an attacker-chosen key.
                    SocialLink::query()->whereKey($link['id'])->update($attributes);
                } else {
                    SocialLink::create($attributes);
                }
            }
        });

        $this->settings->flush('social');

        $this->logger->log(
            ActivityAction::SocialLinksUpdated,
            null,
            [
                'before_count' => count($before),
                'after_count' => $links->count(),
                'platforms' => $links->pluck('platform')->all(),
            ],
        );

        return back()->with('success', 'تم تحديث الروابط الاجتماعية بنجاح.');
    }
}
