<?php

namespace Database\Seeders;

use App\Enums\AvailabilityStatus;
use App\Enums\DiscountType;
use App\Models\Category;
use App\Models\Gallery;
use App\Models\GalleryImage;
use App\Models\Media;
use App\Models\Offer;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class CatalogSeeder extends Seeder
{
    private const CATEGORIES = [
        ['name_ar' => 'غرف نوم',      'name_en' => 'Bedrooms'],
        ['name_ar' => 'غرف جلوس',     'name_en' => 'Living Rooms'],
        ['name_ar' => 'غرف سفرة',     'name_en' => 'Dining Rooms'],
        ['name_ar' => 'كنب',           'name_en' => 'Sofas'],
        ['name_ar' => 'طاولات',       'name_en' => 'Tables'],
        ['name_ar' => 'خزائن',        'name_en' => 'Cabinets'],
        ['name_ar' => 'مفروشات',      'name_en' => 'Upholstery'],
        ['name_ar' => 'إكسسوارات منزلية', 'name_en' => 'Home Accessories'],
    ];

    /**
     * [name_ar, name_en, category slug, photo number within that category's
     * demo group] — the photo is pinned per product so a bookshelf shows a
     * bookshelf rather than whatever the pool happened to land on.
     *
     * An optional fifth element names a key in DemoImagesSeeder's
     * PRODUCT_PHOTOS, for the products whose name the category pool cannot
     * honour: a prayer rug or a marble table needs its own photograph, not
     * the nearest rug or table. The photo number stays as the fallback for
     * when that pool has not been seeded.
     */
    private const PRODUCT_NAMES = [
        ['صالة رويال مودرن', 'Royal Modern Lounge', 'living-rooms', 2],
        ['غرفة نوم فينيسيا كاملة', 'Venice Full Bedroom', 'bedrooms', 1],
        ['طقم كنب إنجليزية فاخر', 'Luxury English Sofa Set', 'sofas', 1],
        ['طاولة طعام عائلية ممتدة', 'Family Extendable Dining Table', 'dining-rooms', 4],
        ['دولاب خزانة خشب بلوط', 'Oak Wood Wardrobe', 'cabinets', 1],
        ['كنبة قطناً فاخرة', 'Premium Cotton Sofa', 'sofas', 4],
        ['طاولة قهوة رخامية', 'Marble Coffee Table', 'tables', 2, 'marble-coffee-table'],
        ['سرير مزدوج ملكي', 'Royal Queen Bed', 'bedrooms', 4],
        ['دولاب تسريحة مودرن', 'Modern Dresser', 'cabinets', 5, 'modern-dresser'],
        ['غرفة نوم أطفال مبهجة', 'Joyful Kids Bedroom', 'bedrooms', 6],
        ['طقم جلوس كلاسيك', 'Classic Lounge Set', 'living-rooms', 4],
        ['ترابيزة جانبية ذهبية', 'Golden Side Table', 'tables', 3, 'golden-side-table'],
        ['صالون إمبراطوري فاخر', 'Imperial Majlis', 'living-rooms', 5],
        ['خزانة كتب خشبية', 'Wooden Bookshelf', 'cabinets', 3],
        ['طقم مفروشات مضاف تدخين', 'Lounge Accent Set', 'upholstery', 1],
        ['مرآة إيطالية مزخرفة', 'Italian Ornate Mirror', 'tables', 4, 'ornate-mirror'],
        ['طاولة سفرة دائرية', 'Round Dining Table', 'dining-rooms', 3],
        ['كنب جلد طبيعي إيطالي', 'Italian Leather Sofa', 'sofas', 2],
        ['غرفة جلوس عصرية', 'Contemporary Living Room', 'living-rooms', 3],
        ['دولاب ملابس عالي', 'Tall Wardrobe', 'cabinets', 2],
        ['طاولة مكتب مودرن', 'Modern Desk', 'tables', 1],
        ['كنسول خشبي فاخر', 'Luxury Console Table', 'tables', 6, 'console-table'],
        ['صالة ضيافة نسيم', 'Naseem Guest Lounge', 'living-rooms', 6],
        ['سرير قماش مميز', 'Designer Fabric Bed', 'bedrooms', 3],
        ['طقم طاولات جانبية', 'Side Table Set', 'tables', 3],
        ['دولاب سفرة كبير', 'Large Sideboard', 'cabinets', 6],
        ['كنبة عثمانية مخصصة', 'Custom Ottoman', 'upholstery', 5, 'ottoman'],
        ['غرفة نوم عصرية فاخرة', 'Luxury Modern Bedroom', 'bedrooms', 5],
        ['طاولة طعام 8 كراسي', '8-Seat Dining Table', 'dining-rooms', 1],
        ['خزانة مطبخ خشب', 'Wooden Kitchen Cabinet', 'cabinets', 4],
        ['أرضية صوف دانتيل', 'Wool Area Rug', 'upholstery', 3, 'area-rug'],
        ['وسائد ديكور مطرزة', 'Embroidered Cushions', 'upholstery', 4, 'embroidered-cushions'],
        ['ستائر قماش عالية', 'Floor-Length Curtains', 'upholstery', 6, 'curtains'],
        ['مفارش سرير فاخرة', 'Luxury Bed Linen', 'upholstery', 2, 'bed-linen'],
        ['شمعدانات نحاسية', 'Brass Candle Holder', 'home-accessories', 6],
        ['أبجورة طاولة مودرن', 'Modern Table Lamp', 'home-accessories', 5],
        ['مزهرية زجاجية فنية', 'Art Glass Vase', 'home-accessories', 2, 'glass-vase'],
        ['قفص إضاءة عربي', 'Arabic Lantern', 'home-accessories', 1],
        ['طبق سيراميك مزخرف', 'Decorative Ceramic Plate', 'home-accessories', 3, 'ceramic-plate'],
        ['سجاد صلاة فاخر', 'Premium Prayer Rug', 'upholstery', 3, 'prayer-rug'],
    ];

    public function run(): void
    {
        if (! Media::exists()) {
            $this->command?->warn('لا توجد صور وسائط. شغّل DemoImagesSeeder أولاً.');

            return;
        }

        $this->seedCategories($pool = $this->mediaPool());
        $this->seedProducts($pool);
        $this->seedOffers($pool);
        $this->seedGalleries($pool);
    }

    /**
     * Demo media is stored as "{category-slug}-{n}.webp", so the pool can be
     * grouped by category and every image stays on topic. The first image of
     * each group is the portrait one, reserved for the category cover.
     *
     * Falls back to a single ungrouped bucket if the pool was seeded under
     * the older flat naming.
     *
     * @return \Illuminate\Support\Collection<string, \Illuminate\Support\Collection<int, int>>
     */
    private function mediaPool(): Collection
    {
        $pool = Media::query()
            ->where('collection', 'demo')
            ->orderBy('filename')
            ->get()
            ->groupBy(fn (Media $media) => Str::beforeLast(pathinfo($media->filename, PATHINFO_FILENAME), '-'))
            ->map(fn (Collection $group) => $group->pluck('id')->values());

        $known = $pool->only(array_map(
            fn (array $category) => Str::slug($category['name_en']),
            self::CATEGORIES
        ));

        return $known->isEmpty()
            ? collect(['*' => Media::query()->orderBy('id')->pluck('id')])
            : $known;
    }

    /**
     * Every image of one category, cover first.
     *
     * @return \Illuminate\Support\Collection<int, int>
     */
    private function group(Collection $pool, string $slug): Collection
    {
        return $pool->get($slug) ?? $pool->first();
    }

    /**
     * The landscape images of one category — everything but the cover, which
     * is portrait and would letterbox inside the 4/3 product frame.
     *
     * @return \Illuminate\Support\Collection<int, int>
     */
    private function shots(Collection $pool, string $slug): Collection
    {
        $group = $this->group($pool, $slug);

        return $group->count() > 1 ? $group->slice(1)->values() : $group;
    }

    /**
     * The dedicated portrait hero for a category, seeded into its own
     * collection by DemoImagesSeeder. Null if that pool was never seeded.
     */
    private function categoryImage(string $slug): ?int
    {
        return Media::query()
            ->where('collection', 'categories')
            ->where('filename', $slug.'.webp')
            ->value('id');
    }

    /**
     * The dedicated photo for a named product, seeded into its own
     * collection by DemoImagesSeeder. Null if that pool was never seeded.
     */
    private function productImage(?string $key): ?int
    {
        if ($key === null) {
            return null;
        }

        return Media::query()
            ->where('collection', 'products')
            ->where('filename', $key.'.webp')
            ->value('id');
    }

    private function seedCategories(Collection $pool): void
    {
        foreach (self::CATEGORIES as $index => $category) {
            $slug = Str::slug($category['name_en']);

            Category::updateOrCreate(
                ['slug' => $slug],
                [
                    'name_ar'       => $category['name_ar'],
                    'name_en'       => $category['name_en'],
                    'sort_order'    => $index,
                    'image_media_id'=> $this->categoryImage($slug) ?? $this->group($pool, $slug)->first(),
                    'is_active'     => true,
                ]
            );
        }

        $this->command?->info('✓ تم إنشاء التصنيفات.');
    }

    private function seedProducts(Collection $pool): void
    {
        $categories = Category::query()->orderBy('sort_order')->get();

        foreach (self::PRODUCT_NAMES as $index => $row) {
            [$nameAr, $nameEn, $categorySlug, $photo] = $row;
            $category = $categories->firstWhere('slug', $categorySlug) ?? $categories->first();
            $group = $this->group($pool, $categorySlug);
            $coverMediaId = $this->productImage($row[4] ?? null)
                ?? $group[($photo - 1) % $group->count()];

            $price = fake()->numberBetween(18, 55) * 10;
            $hasSale = $index % 4 === 0;
            $salePrice = $hasSale ? (int) round($price * fake()->randomElement([0.75, 0.85, 0.9])) : null;

            $product = Product::updateOrCreate(
                ['slug' => Str::slug($nameEn.'-'.$index)],
                [
                    'category_id'       => $category->id,
                    'name_ar'           => $nameAr,
                    'name_en'           => $nameEn,
                    'sku'               => 'AF-'.strtoupper('0'.($index + 1000)),
                    'short_description_ar' => fake()->sentence(8),
                    'description_ar'    => fake()->paragraph(3),
                    'cover_media_id'    => $coverMediaId,
                    'price'             => $price,
                    'sale_price'        => $salePrice,
                    'currency'          => 'JOD',
                    'dimensions_ar'     => fake()->randomElement(['العرض 240 × العمق 90 × الارتفاع 85 سم', '180×80×75 سم', '200×100×90 سم', '250×90×80 سم', '120×40×60 سم']),
                    'material_ar'       => fake()->randomElement(['خشب زان طبيعي', 'خشب بلوط مع قماش كتان', 'صندل مع جلد طبيعي', 'خشب سنديان فاخر', 'قماش مخمل برتغالي']),
                    'color_ar'          => fake()->randomElement(['بيج', 'كريمي', 'بني فاتح', 'أبيض عاجي', 'رمادي فحمي', 'خشبي طبيعي']),
                    'availability_status' => fake()->randomElement([
                        AvailabilityStatus::Available,
                        AvailabilityStatus::Available,
                        AvailabilityStatus::Available,
                        AvailabilityStatus::SoldOut,
                    ]),
                    'is_featured'       => $index % 3 === 0,
                    'is_new'            => $index >= count(self::PRODUCT_NAMES) - 8,
                    'sort_order'        => $index,
                    'is_active'         => true,
                    'views_count'       => fake()->numberBetween(200, 8500),
                ]
            );

            $this->attachProductImages($product, $group, $photo, $coverMediaId);
        }

        $this->command?->info('✓ تم إنشاء المنتجات.');
    }

    /**
     * @param  \Illuminate\Support\Collection<int, int>  $group
     */
    private function attachProductImages(Product $product, Collection $group, int $photo, int $coverMediaId): void
    {
        ProductImage::updateOrCreate(
            ['product_id' => $product->id, 'media_id' => $coverMediaId],
            ['is_cover' => true, 'sort_order' => 0]
        );

        $attached = [$coverMediaId];

        foreach ([1, 2, 3] as $offset) {
            $mediaId = $group[($photo - 1 + $offset) % $group->count()];

            if ($mediaId === $coverMediaId) {
                continue;
            }

            ProductImage::updateOrCreate(
                ['product_id' => $product->id, 'media_id' => $mediaId],
                ['is_cover' => false, 'sort_order' => $offset]
            );

            $attached[] = $mediaId;
        }

        // Re-seeding must converge: without this, a product that was given a
        // different cover keeps the one it had, and the gallery grows every
        // time the seeder runs.
        ProductImage::query()
            ->where('product_id', $product->id)
            ->whereNotIn('media_id', $attached)
            ->delete();
    }

    private function seedOffers(Collection $pool): void
    {
        $products = Product::query()->orderBy('id')->pluck('id');

        $definitions = [
            [
                'title_ar'     => 'تخفيضات الموسم الكبرى',
                'description_ar' => 'خصومات تصل إلى 25% على تشكيلة مختارة من غرف الجلوس والكنب خلال هذا الموسم.',
                'discount_type' => DiscountType::Percentage,
                'discount_value' => 25,
                'badge'         => '-25٪',
                'image_slug'    => 'sofas',
            ],
            [
                'title_ar'     => 'عروض الشتاء الدافئة',
                'description_ar' => 'أجواء دافئة لمنزلك مع خصم ثابت على أثاث غرف النوم والمفروشات طوال فصل الشتاء.',
                'discount_type' => DiscountType::Percentage,
                'discount_value' => 15,
                'badge'         => '-15٪',
                'image_slug'    => 'bedrooms',
            ],
            [
                'title_ar'     => 'تشكيلة الضيافة الحصرية',
                'description_ar' => 'أسعار خاصة لعملائنا الكرام على أطقم الضيافة والصالونات الإمبراطورية.',
                'discount_type' => DiscountType::Fixed,
                'discount_value' => 25,
                'badge'         => 'خصم خاص',
                'image_slug'    => 'living-rooms',
            ],
        ];

        foreach ($definitions as $definition) {
            $offer = Offer::updateOrCreate(
                ['slug' => Str::slug($definition['title_ar'])],
                [
                    'title_ar'       => $definition['title_ar'],
                    'description_ar' => $definition['description_ar'],
                    // offer banners are wide, so they take a landscape shot
                    'image_media_id' => $this->shots($pool, $definition['image_slug'])->first(),
                    'discount_type'  => $definition['discount_type'],
                    'discount_value' => $definition['discount_value'],
                    'price_override' => null,
                    'starts_at'      => now()->subDays(7),
                    'ends_at'        => now()->addDays(30),
                    'sort_order'     => array_search($definition, $definitions),
                    'is_active'      => true,
                ]
            );

            $offerProducts = $products->shuffle()->take(6);
            $offer->products()->sync($offerProducts->mapWithKeys(fn ($id) => [$id => ['price_override' => null]]));
        }

        $this->command?->info('✓ تم إنشاء العروض.');
    }

    private function seedGalleries(Collection $pool): void
    {
        $groups = [
            'قاعة العرض الرئيسية' => [
                'description' => 'لمحة عامة عن أجنحة المعرض الرئيسية وتشكيلاتها المتنوعة.',
                'slugs'       => ['living-rooms', 'bedrooms', 'dining-rooms', 'cabinets'],
            ],
            'أجنحة غرف النوم' => [
                'description' => 'تصاميم غرف النوم الفاخرة مرتبة بأسلوب عصري.',
                'slugs'       => ['bedrooms'],
            ],
            'ركن المودرن' => [
                'description' => 'قطع عصرية بألوان هادئة وتناغم مريح للعين.',
                'slugs'       => ['tables', 'home-accessories'],
            ],
            'جاليري الصالونات' => [
                'description' => 'صالونات استقبال بارزة بتصاميم شرقية وأوروبية.',
                'slugs'       => ['sofas', 'living-rooms'],
            ],
        ];

        foreach ($groups as $title => $definition) {
            // Round-robin across the themes rather than taking a block from
            // each: a flat take() filled the six slots from the first two
            // themes and silently dropped the rest, so a four-theme showroom
            // gallery ended up showing the same rooms as the two-theme one.
            $themes = collect($definition['slugs'])
                ->map(fn (string $slug) => $this->group($pool, $slug)->values());

            $images = collect();

            for ($round = 0; $images->count() < 6; $round++) {
                $added = false;

                foreach ($themes as $theme) {
                    if ($round >= $theme->count() || $images->count() >= 6) {
                        continue;
                    }

                    $images->push($theme[$round]);
                    $added = true;
                }

                if (! $added) {
                    break; // every theme exhausted
                }
            }

            $images = $images->unique()->values();

            $gallery = Gallery::updateOrCreate(
                ['slug' => Str::slug($title)],
                [
                    'title_ar'       => $title,
                    'description_ar' => $definition['description'],
                    'cover_media_id' => $images->first(),
                    'sort_order'     => array_search($title, array_keys($groups)),
                    'is_active'      => true,
                ]
            );

            foreach ($images as $order => $mediaId) {
                GalleryImage::updateOrCreate(
                    ['gallery_id' => $gallery->id, 'media_id' => $mediaId],
                    [
                        'caption_ar'   => fake()->optional(0.6)->randomElement(['زاوية مريحة', 'تصميم عصري', 'حرفية أصيلة', 'قطع مختارة بعناية']),
                        'sort_order'   => $order,
                        'is_featured'  => $order === 0,
                    ]
                );
            }

            // Drop anything a previous run left behind, so re-seeding after a
            // theme change replaces the set rather than adding to it.
            GalleryImage::query()
                ->where('gallery_id', $gallery->id)
                ->whereNotIn('media_id', $images->all())
                ->delete();
        }

        $this->command?->info('✓ تم إنشاء المعارض.');
    }
}
