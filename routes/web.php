<?php

use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Admin\ContactMessageController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\GalleryController as AdminGalleryController;
use App\Http\Controllers\Admin\MediaController;
use App\Http\Controllers\Admin\OfferController as AdminOfferController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Public\AboutController;
use App\Http\Controllers\Public\CategoryController;
use App\Http\Controllers\Public\ContactController;
use App\Http\Controllers\Public\FavoriteController;
use App\Http\Controllers\Public\GalleryController;
use App\Http\Controllers\Public\HomeController;
use App\Http\Controllers\Public\LocaleController;
use App\Http\Controllers\Public\OfferController;
use App\Http\Controllers\Public\ProductController;
use App\Http\Controllers\Public\SearchController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Authentication
|--------------------------------------------------------------------------
| Loaded at the top level: the file declares its own guest/auth groups,
| and nesting it inside a `guest` group would make logout — which requires
| an authenticated user — permanently unreachable.
*/
require __DIR__.'/auth.php';

// ----------------------------------------------------------- Guest / Public

Route::get('/locale/{locale}', [LocaleController::class, 'switch'])
    ->whereIn('locale', ['ar', 'en'])
    ->name('locale.switch');

/*
| A single "where do I land after signing in" target.
|
| The framework's own auth flows (password confirmation, e-mail
| verification) redirect to route('dashboard') by name. Without it those
| routes threw RouteNotFoundException — a 500 on a path a real user hits.
| Resolving the destination here keeps that decision in one place instead
| of repeating the staff/customer branch in four controllers.
*/
Route::get('/dashboard', function (Illuminate\Http\Request $request) {
    return redirect()->route(
        $request->user()?->canAccessAdmin() ? 'admin.dashboard' : 'home'
    );
})->middleware(['auth', 'active'])->name('dashboard');

Route::middleware('active')->group(function () {

    // Public storefront
    Route::get('/', HomeController::class)->name('home');
    Route::get('/about', AboutController::class)->name('about');
    Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
    Route::get('/categories/{slug}', [CategoryController::class, 'show'])->name('categories.show');
    Route::get('/products', [ProductController::class, 'index'])->name('products.index');
    Route::get('/products/{slug}', [ProductController::class, 'show'])->name('products.show');
    Route::get('/offers', [OfferController::class, 'index'])->name('offers.index');
    Route::get('/offers/{slug}', [OfferController::class, 'show'])->name('offers.show');
    Route::get('/gallery', [GalleryController::class, 'index'])->name('gallery');
    Route::get('/contact', [ContactController::class, 'index'])->name('contact');

    Route::post('/contact', [ContactController::class, 'store'])
        ->middleware('throttle:contact')
        ->name('contact.store');

    Route::get('/search', SearchController::class)
        ->middleware('throttle:search')
        ->name('search');

    // Authenticated customer features
    Route::middleware(['auth', 'auth.session'])->group(function () {
        Route::post('/favorites', [FavoriteController::class, 'toggle'])
            ->middleware('throttle:60,1')
            ->name('favorites.toggle');

        Route::get('/favorites', [FavoriteController::class, 'index'])->name('favorites.index');

        Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    });
});

/*
|--------------------------------------------------------------------------
| Admin panel
|--------------------------------------------------------------------------
| Layered on purpose:
|   auth           — signed in at all
|   auth.session   — session bound to the user (password change kills it)
|   active         — account not deactivated
|   admin          — staff flag + a recognised back-office role
|   admin.session  — idle / absolute session timeouts
|   password.fresh — an admin-set initial password must be replaced
|   permission:*   — coarse per-route gate (defence in depth)
|
| The route-level `permission` middleware is a first filter only. The
| authoritative check is the policy call inside each controller action,
| which also covers ownership and privilege-level rules the middleware
| cannot express.
*/

Route::middleware(['auth', 'auth.session', 'active', 'admin', 'admin.session', 'password.fresh'])
    ->prefix('admin')
    ->as('admin.')
    ->group(function () {

        Route::get('/', DashboardController::class)
            ->middleware('permission:dashboard.view')
            ->name('dashboard');

        // ------------------------------------------------------- Products
        Route::middleware('permission:product.view')->group(function () {
            Route::get('products', [AdminProductController::class, 'index'])->name('products.index');
            Route::get('products/create', [AdminProductController::class, 'create'])->name('products.create');
            Route::get('products/{product}', [AdminProductController::class, 'show'])->name('products.show');
            Route::get('products/{product}/edit', [AdminProductController::class, 'edit'])->name('products.edit');
        });

        Route::middleware(['throttle:admin-write'])->group(function () {
            Route::post('products', [AdminProductController::class, 'store'])
                ->middleware('permission:product.create')->name('products.store');

            Route::match(['put', 'patch'], 'products/{product}', [AdminProductController::class, 'update'])
                ->middleware('permission:product.edit')->name('products.update');

            Route::delete('products/{product}', [AdminProductController::class, 'destroy'])
                ->middleware('permission:product.delete')->name('products.destroy');

            Route::post('products/{product}/restore', [AdminProductController::class, 'restore'])
                ->withTrashed()
                ->middleware('permission:product.restore')->name('products.restore');

            Route::delete('products/{product}/force-delete', [AdminProductController::class, 'forceDelete'])
                ->withTrashed()
                ->middleware('role:super-admin')->name('products.force-delete');
        });

        // ----------------------------------------------------- Categories
        Route::get('categories', [AdminCategoryController::class, 'index'])
            ->middleware('permission:category.view')->name('categories.index');

        Route::middleware(['throttle:admin-write'])->group(function () {
            Route::post('categories', [AdminCategoryController::class, 'store'])
                ->middleware('permission:category.create')->name('categories.store');

            Route::match(['put', 'patch'], 'categories/{category}', [AdminCategoryController::class, 'update'])
                ->middleware('permission:category.edit')->name('categories.update');

            Route::delete('categories/{category}', [AdminCategoryController::class, 'destroy'])
                ->middleware('permission:category.delete')->name('categories.destroy');

            Route::post('categories/{category}/restore', [AdminCategoryController::class, 'restore'])
                ->withTrashed()
                ->middleware('permission:category.edit')->name('categories.restore');
        });

        // --------------------------------------------------------- Offers
        Route::middleware('permission:offer.view')->group(function () {
            Route::get('offers', [AdminOfferController::class, 'index'])->name('offers.index');
            Route::get('offers/{offer}', [AdminOfferController::class, 'show'])->name('offers.show');
        });

        Route::middleware(['throttle:admin-write'])->group(function () {
            Route::post('offers', [AdminOfferController::class, 'store'])
                ->middleware('permission:offer.create')->name('offers.store');

            Route::match(['put', 'patch'], 'offers/{offer}', [AdminOfferController::class, 'update'])
                ->middleware('permission:offer.edit')->name('offers.update');

            Route::patch('offers/{offer}/toggle-active', [AdminOfferController::class, 'toggleActive'])
                ->middleware('permission:offer.edit')->name('offers.toggle-active');

            Route::delete('offers/{offer}', [AdminOfferController::class, 'destroy'])
                ->middleware('permission:offer.delete')->name('offers.destroy');

            Route::post('offers/{offer}/restore', [AdminOfferController::class, 'restore'])
                ->withTrashed()
                ->middleware('permission:offer.edit')->name('offers.restore');
        });

        // ------------------------------------------------------ Galleries
        Route::middleware('permission:gallery.view')->group(function () {
            Route::get('galleries', [AdminGalleryController::class, 'index'])->name('galleries.index');
            Route::get('galleries/{gallery}', [AdminGalleryController::class, 'show'])->name('galleries.show');
        });

        Route::middleware(['throttle:admin-write', 'permission:gallery.manage'])->group(function () {
            Route::post('galleries', [AdminGalleryController::class, 'store'])->name('galleries.store');

            Route::match(['put', 'patch'], 'galleries/{gallery}', [AdminGalleryController::class, 'update'])
                ->name('galleries.update');

            Route::delete('galleries/{gallery}', [AdminGalleryController::class, 'destroy'])->name('galleries.destroy');

            Route::post('galleries/{gallery}/restore', [AdminGalleryController::class, 'restore'])
                ->withTrashed()->name('galleries.restore');

            Route::delete('galleries/{gallery}/images/{image}', [AdminGalleryController::class, 'removeImage'])
                ->name('galleries.images.remove');

            Route::patch('galleries/{gallery}/images/{image}', [AdminGalleryController::class, 'updateImage'])
                ->name('galleries.images.update');

            Route::post('galleries/{gallery}/images/{image}/move', [AdminGalleryController::class, 'moveImage'])
                ->name('galleries.images.move');

            // Whole-gallery reorder in one request — dragging a cover from
            // the end to the front is otherwise N up/down round trips.
            Route::post('galleries/{gallery}/images/reorder', [AdminGalleryController::class, 'reorderImages'])
                ->name('galleries.images.reorder');
        });

        // ---------------------------------------------------------- Media
        Route::get('media', [MediaController::class, 'index'])
            ->middleware('permission:media.upload|media.delete')->name('media.index');

        Route::post('media', [MediaController::class, 'store'])
            ->middleware(['throttle:uploads', 'permission:media.upload'])->name('media.store');

        Route::match(['put', 'patch'], 'media/{media}', [MediaController::class, 'update'])
            ->middleware(['throttle:admin-write', 'permission:media.upload'])->name('media.update');

        Route::delete('media/{media}', [MediaController::class, 'destroy'])
            ->middleware(['throttle:admin-write', 'permission:media.delete'])->name('media.destroy');

        // Files stored off the document root. The signature stops URL
        // sharing outside the panel; the policy check inside the action
        // is what actually decides access.
        Route::get('media/{media}/file', [MediaController::class, 'file'])
            ->middleware('signed')->name('media.file');

        // ----------------------------------------------- Contact messages
        Route::middleware('permission:contact.view')->group(function () {
            Route::get('messages', [ContactMessageController::class, 'index'])->name('messages.index');
            Route::get('messages/{message}', [ContactMessageController::class, 'show'])->name('messages.show');
        });

        Route::middleware(['throttle:admin-write', 'permission:contact.manage'])->group(function () {
            Route::patch('messages/{message}/toggle-read', [ContactMessageController::class, 'toggleRead'])
                ->name('messages.toggle-read');

            Route::delete('messages/{message}', [ContactMessageController::class, 'destroy'])->name('messages.destroy');
        });

        // ---------------------------------------------------------- Users
        // User administration is super-admin territory. The `role`
        // middleware is the perimeter; UserPolicy enforces the finer
        // rules (rank, self-protection, last-super-admin).
        Route::middleware('role:super-admin')->group(function () {
            Route::get('users', [UserController::class, 'index'])->name('users.index');

            Route::middleware('throttle:admin-write')->group(function () {
                Route::post('users', [UserController::class, 'store'])->name('users.store');

                Route::match(['put', 'patch'], 'users/{user}', [UserController::class, 'update'])
                    ->name('users.update');

                Route::delete('users/{user}', [UserController::class, 'destroy'])->name('users.destroy');

                Route::patch('users/{user}/toggle-active', [UserController::class, 'toggleActive'])
                    ->name('users.toggle-active');
            });
        });

        // ------------------------------------------------------- Settings
        Route::get('settings', [SettingController::class, 'index'])
            ->middleware('permission:setting.view')->name('settings.index');

        // Changing settings is sensitive enough to re-ask for the password.
        Route::middleware(['throttle:admin-write', 'permission:setting.manage', 'password.confirm'])->group(function () {
            Route::put('settings/social-links', [SettingController::class, 'updateSocialLinks'])
                ->name('settings.social');

            Route::put('settings/{group}', [SettingController::class, 'update'])
                ->whereIn('group', ['site', 'contact', 'seo', 'homepage'])
                ->name('settings.update');
        });

        // --------------------------------------------------- Activity log
        Route::middleware('permission:activity.view')->group(function () {
            Route::get('activity', [ActivityLogController::class, 'index'])->name('activity.index');
            Route::get('activity/{activity}', [ActivityLogController::class, 'show'])->name('activity.show');
            Route::get('activity-export', [ActivityLogController::class, 'export'])
                ->middleware('throttle:6,1')->name('activity.export');
        });

        Route::delete('activity/purge', [ActivityLogController::class, 'purge'])
            ->middleware(['role:super-admin', 'password.confirm'])->name('activity.purge');
    });
