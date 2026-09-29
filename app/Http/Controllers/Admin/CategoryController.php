<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCategoryRequest;
use App\Http\Requests\Admin\UpdateCategoryRequest;
use App\Models\Category;
use App\Services\MediaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Inertia\Inertia;

class CategoryController extends Controller
{
    public function __construct(
        private readonly MediaService $mediaService
    ) {}

    public function index(Request $request)
    {
        Gate::authorize('viewAny', Category::class);

        $query = Category::query()
            ->with(['parent:id,name_ar', 'children:id,name_ar', 'image'])
            ->withCount(['products', 'children'])
            ->withTrashed()
            ->ordered();

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name_ar', 'like', "%{$search}%")
                  ->orWhere('name_en', 'like', "%{$search}%")
                  ->orWhere('slug', 'like', "%{$search}%")
                  ->orWhereHas('parent', fn ($p) => $p->where('name_ar', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('parent_id')) {
            if ($request->input('parent_id') === '0') {
                $query->whereNull('parent_id');
            } else {
                $query->where('parent_id', $request->input('parent_id'));
            }
        }

        $trash = $request->boolean('trash');
        if ($trash) {
            $query->onlyTrashed();
        }

        $categories = $query->paginate($request->integer('per_page', 15))
            ->withQueryString()
            ->through(fn (Category $category) => $this->presentRow($category, $trash));

        $allCategories = Category::withTrashed()
            ->ordered()
            ->get(['id', 'name_ar', 'name_en', 'slug', 'parent_id', 'sort_order', 'is_active', 'deleted_at']);

        return Inertia::render('Admin/Categories/Index', [
            'categories'    => $categories,
            'allCategories' => $allCategories,
            'filters'       => $request->only(['search', 'parent_id', 'trash']),
            'stats'         => $this->stats(),
        ]);
    }

    // --------------------------------------------------------------- helpers

    private function presentRow(Category $category, bool $trashedMode): array
    {
        return [
            'id'             => $category->id,
            'name_ar'        => $category->name_ar,
            'name_en'        => $category->name_en,
            'slug'           => $category->slug,
            'description_ar' => $category->description_ar,
            'description_en' => $category->description_en,
            'parent'         => $category->parent
                ? ['id' => $category->parent->id, 'name_ar' => $category->parent->name_ar]
                : null,
            'children_count' => (int) $category->children_count,
            'products_count' => (int) $category->products_count,
            'sort_order'     => (int) $category->sort_order,
            'is_active'      => (bool) $category->is_active,
            'image_url'      => $category->image?->url,
            'image_thumb_url'=> $category->image?->thumb_url,
            'trashed'        => $trashedMode || $category->trashed(),
            'created_at'     => $category->created_at?->toDateTimeString(),
            'deleted_at'     => $category->deleted_at?->toDateTimeString(),
        ];
    }

    private function stats(): array
    {
        return [
            'total'    => Category::withTrashed()->count(),
            'active'   => Category::active()->count(),
            'inactive' => Category::withTrashed()->where('is_active', false)->count(),
            'trashed'  => Category::onlyTrashed()->count(),
        ];
    }

    public function store(StoreCategoryRequest $request)
    {
        Gate::authorize('create', Category::class);

        $data = $request->validated();

        $category = Category::create([
            'parent_id'      => $data['parent_id'] ?? null,
            'name_ar'        => $data['name_ar'],
            'name_en'        => $data['name_en'] ?? null,
            'slug'           => $data['slug'] ?? Str::slug($data['name_ar']),
            'description_ar' => $data['description_ar'] ?? null,
            'description_en' => $data['description_en'] ?? null,
            'sort_order'     => $data['sort_order'] ?? 0,
            'is_active'      => $data['is_active'] ?? true,
        ]);

        if ($request->hasFile('image')) {
            $media = $this->mediaService->storeOne($request->file('image'), 'category');
            $category->update(['image_media_id' => $media->id]);
        }

        return back()->with('success', 'تم إضافة التصنيف بنجاح.');
    }

    public function update(UpdateCategoryRequest $request, Category $category)
    {
        Gate::authorize('update', $category);

        $data = $request->validated();

        $category->update([
            'parent_id'      => $data['parent_id'] ?? $category->parent_id,
            'name_ar'        => $data['name_ar'],
            'name_en'        => $data['name_en'] ?? null,
            'slug'           => $data['slug'] ?? $category->slug,
            'description_ar' => $data['description_ar'] ?? null,
            'description_en' => $data['description_en'] ?? null,
            'sort_order'     => $data['sort_order'] ?? $category->sort_order,
            'is_active'      => $data['is_active'] ?? true,
        ]);

        if ($request->hasFile('image')) {
            $media = $this->mediaService->storeOne($request->file('image'), 'category');
            $category->update(['image_media_id' => $media->id]);
        }

        return back()->with('success', 'تم تحديث التصنيف بنجاح.');
    }

    public function destroy(Category $category)
    {
        Gate::authorize('delete', $category);

        if ($category->children()->exists()) {
            return back()->with('error', 'لا يمكن حذف تصنيف يحتوي على تصنيفات فرعية. انقلها أو احذفها أولاً.');
        }

        $category->delete();

        return back()->with('success', 'تم حذف التصنيف بنجاح.');
    }

    public function restore(Category $category)
    {
        Gate::authorize('restore', $category);

        $category->restore();

        return back()->with('success', 'تم استعادة التصنيف بنجاح.');
    }
}