<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\ContactMessage;
use App\Models\Gallery;
use App\Models\Offer;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = $request->user();

        // The dashboard aggregates data from several resources, so each
        // tile is gated on the ability that owns it rather than shown to
        // anyone who can reach the page.
        $stats = array_filter([
            'products' => $user->can('viewAny', Product::class)
                ? Product::query()->count() : null,
            'featured_products' => $user->can('viewAny', Product::class)
                ? Product::query()->where('is_featured', true)->count() : null,
            'new_arrivals' => $user->can('viewAny', Product::class)
                ? Product::query()->where('is_new', true)->count() : null,
            'categories' => $user->can('viewAny', Category::class)
                ? Category::query()->count() : null,
            'offers' => $user->can('viewAny', Offer::class)
                ? Offer::query()->where('is_active', true)->count() : null,
            'galleries' => $user->can('viewAny', Gallery::class)
                ? Gallery::query()->count() : null,
            'unread_messages' => $user->can('viewAny', ContactMessage::class)
                ? ContactMessage::query()->unread()->count() : null,
            'users' => $user->can('viewAny', User::class)
                ? User::query()->count() : null,
        ], fn ($value) => $value !== null);

        $recentMessages = $user->can('viewAny', ContactMessage::class)
            ? $this->recentMessages()
            : collect();

        $recentProducts = $user->can('viewAny', Product::class)
            ? $this->recentProducts()
            : collect();

        $recentActivity = $user->can('viewAny', ActivityLog::class)
            ? ActivityLog::query()
                ->latest('created_at')
                ->take(8)
                ->get()
                ->map(fn (ActivityLog $log) => [
                    'id' => $log->id,
                    'action' => $log->action_label,
                    'user' => $log->user_name,
                    'subject' => $log->subject_label,
                    'is_security_event' => $log->is_security_event,
                    'created_at' => $log->created_at?->diffForHumans(),
                ])
            : collect();

        return Inertia::render('Admin/Dashboard', [
            'stats' => $stats,
            'recent_messages' => $recentMessages,
            'recent_products' => $recentProducts,
            'recent_activity' => $recentActivity,
        ]);
    }

    private function recentMessages()
    {
        return ContactMessage::query()
            ->latest()
            ->take(5)
            ->get()
            ->map(fn (ContactMessage $m) => [
                'id'        => $m->id,
                'name'      => $m->name,
                'subject'   => $m->subject,
                'is_read'   => $m->is_read,
                'created_at' => $m->created_at?->diffForHumans(),
            ]);
    }

    private function recentProducts()
    {
        return Product::query()
            ->with('category:id,name_ar')
            ->latest()
            ->take(5)
            ->get()
            ->map(fn (Product $p) => [
                'id'         => $p->id,
                'name'       => $p->name_ar,
                'price'      => (float) $p->price,
                'sale_price' => $p->sale_price !== null ? (float) $p->sale_price : null,
                'currency'   => $p->currency ?? 'JOD',
                'category'   => $p->category?->name_ar,
                'is_active'  => (bool) $p->is_active,
                'is_featured' => (bool) $p->is_featured,
                'created_at' => $p->created_at?->diffForHumans(),
            ]);
    }
}