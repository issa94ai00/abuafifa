<?php

namespace Database\Seeders;

use App\Models\Media;
use Illuminate\Database\Seeder;

class DemoImagesSeeder extends Seeder
{
    /**
     * Base URL for the demo photography.
     *
     * Every photo below is from Unsplash and covered by the Unsplash License
     * (free for commercial use, no attribution required). The ids are kept
     * here — rather than the files being committed — so the pool can be
     * re-downloaded at any time; see public/images/home/CREDITS.md for the
     * same arrangement on the home page.
     */
    private const SOURCE = 'https://images.unsplash.com/';

    /**
     * Demo imagery is stored as WebP. 78 sits where the JPEGs it replaced
     * already were visually, for roughly a quarter fewer bytes.
     */
    private const WEBP_QUALITY = 78;

    /**
     * Demo photo pool, grouped by the category slug it belongs to.
     *
     * The first photo of each group is the portrait one: CatalogSeeder uses
     * it as the category cover, where the UI renders a 4/5 frame. The rest
     * are landscape and feed product covers, product galleries and the
     * gallery pages.
     *
     * @var array<string, array{name_ar: string, photos: array<int, array{id: string, alt_ar: string, alt_en: string}>}>
     */
    private const POOL = [
        'bedrooms' => [
            'name_ar' => 'غرف نوم',
            'photos'  => [
                ['id' => 'photo-1505693416388-ac5ce068fe85', 'alt_ar' => 'غرفة نوم فاخرة بظهر سرير منجّد', 'alt_en' => 'Luxury bedroom with tufted headboard'],
                ['id' => 'photo-1540518614846-7eded433c457', 'alt_ar' => 'غرفة نوم عصرية بمقاعد جانبية', 'alt_en' => 'Modern bedroom with accent ottomans'],
                ['id' => 'photo-1631049307264-da0ec9d70304', 'alt_ar' => 'غرفة نوم أنيقة بإضاءة هادئة', 'alt_en' => 'Elegant bedroom with soft lighting'],
                ['id' => 'photo-1617325247661-675ab4b64ae2', 'alt_ar' => 'سرير مزدوج بمفارش قطنية ووسائد', 'alt_en' => 'Double bed with cotton linen and cushions'],
                ['id' => 'photo-1617104678098-de229db51175', 'alt_ar' => 'غرفة نوم رئيسية بألوان داكنة فاخرة', 'alt_en' => 'Master bedroom in rich dark tones'],
                ['id' => 'photo-1615874959474-d609969a20ed', 'alt_ar' => 'غرفة نوم دافئة بديكور منزلي', 'alt_en' => 'Warm bedroom with homely styling'],
            ],
        ],
        'living-rooms' => [
            'name_ar' => 'غرف جلوس',
            'photos'  => [
                ['id' => 'photo-1583847268964-b28dc8f51f92', 'alt_ar' => 'غرفة جلوس بكنب فاتح وطاولات خشبية', 'alt_en' => 'Living room with light sofa and wooden tables'],
                ['id' => 'photo-1567767292278-a4f21aa2d36e', 'alt_ar' => 'صالة جلوس عصرية بكنب رمادي', 'alt_en' => 'Contemporary lounge with grey sofa'],
                ['id' => 'photo-1616486338812-3dadae4b4ace', 'alt_ar' => 'غرفة جلوس واسعة بكنب زاوية', 'alt_en' => 'Spacious living room with sectional sofa'],
                ['id' => 'photo-1617103996702-96ff29b1c467', 'alt_ar' => 'غرفة جلوس بسجادة شرقية وأثاث خشبي', 'alt_en' => 'Living room with oriental rug and wooden furniture'],
                ['id' => 'photo-1600121848594-d8644e57abab', 'alt_ar' => 'صالة عائلية بتنسيق هادئ', 'alt_en' => 'Family lounge with calm styling'],
                ['id' => 'photo-1538688525198-9b88f6f53126', 'alt_ar' => 'ركن جلوس واسع بكنب وطاولات جانبية', 'alt_en' => 'Open lounge area with sofas and side tables'],
            ],
        ],
        'dining-rooms' => [
            'name_ar' => 'غرف سفرة',
            'photos'  => [
                ['id' => 'photo-1604578762246-41134e37f9cc', 'alt_ar' => 'غرفة سفرة بطاولة خشبية وكراسي', 'alt_en' => 'Dining room with wooden table and chairs'],
                ['id' => 'photo-1519710164239-da123dc03ef4', 'alt_ar' => 'ركن طعام صغير بإطلالة مضيئة', 'alt_en' => 'Bright compact dining nook'],
                ['id' => 'photo-1554295405-abb8fd54f153', 'alt_ar' => 'طاولة طعام صغيرة بكرسيين', 'alt_en' => 'Small dining table with two chairs'],
                ['id' => 'photo-1600607687920-4e2a09cf159d', 'alt_ar' => 'طاولة سفرة مودرن ممتدة', 'alt_en' => 'Modern extended dining table'],
                ['id' => 'photo-1560185007-cde436f6a4d0', 'alt_ar' => 'غرفة سفرة بإضاءة معلّقة', 'alt_en' => 'Dining room with pendant lighting'],
                ['id' => 'photo-1592078615290-033ee584e267', 'alt_ar' => 'كرسي طعام مودرن', 'alt_en' => 'Modern dining chair'],
            ],
        ],
        'sofas' => [
            'name_ar' => 'كنب',
            'photos'  => [
                ['id' => 'photo-1493663284031-b7e3aefcae8e', 'alt_ar' => 'كنبة منجّدة بلون رمادي ووسائد', 'alt_en' => 'Tufted grey sofa with cushions'],
                ['id' => 'photo-1615873968403-89e068629265', 'alt_ar' => 'كنب جلد طبيعي بلون العسل', 'alt_en' => 'Tan natural leather sofa'],
                ['id' => 'photo-1555041469-a586c61ea9bc', 'alt_ar' => 'كنبة مخمل خضراء', 'alt_en' => 'Green velvet sofa'],
                ['id' => 'photo-1558211583-d26f610c1eb1', 'alt_ar' => 'كنبة قماشية بلون أصفر', 'alt_en' => 'Yellow fabric sofa'],
                ['id' => 'photo-1567016432779-094069958ea5', 'alt_ar' => 'تفصيل قماش كنبة بلون برتقالي', 'alt_en' => 'Orange sofa upholstery detail'],
                ['id' => 'photo-1567016376408-0226e4d0c1ea', 'alt_ar' => 'كنبة مودرن بخطوط بسيطة', 'alt_en' => 'Minimal modern sofa'],
            ],
        ],
        'tables' => [
            'name_ar' => 'طاولات',
            'photos'  => [
                ['id' => 'photo-1530018607912-eff2daa1bac4', 'alt_ar' => 'طاولة مكتب خشبية بأدراج', 'alt_en' => 'Wooden desk table with drawers'],
                ['id' => 'photo-1499933374294-4584851497cc', 'alt_ar' => 'طاولة جانبية دائرية بيضاء', 'alt_en' => 'White round side table'],
                ['id' => 'photo-1611486212355-d276af4581c0', 'alt_ar' => 'طقم طاولات جانبية خشبية متداخلة', 'alt_en' => 'Nesting wooden side table set'],
                ['id' => 'photo-1618220179428-22790b461013', 'alt_ar' => 'كنسول خشبي مع مرآة دائرية', 'alt_en' => 'Wooden console table with round mirror'],
                ['id' => 'photo-1551298370-9d3d53740c72', 'alt_ar' => 'كراسي مودرن بألوان متباينة', 'alt_en' => 'Modern chairs in contrasting colours'],
                ['id' => 'photo-1533090368676-1fd25485db88', 'alt_ar' => 'طاولة منخفضة مع رفوف كتب', 'alt_en' => 'Low table with book shelving'],
            ],
        ],
        'cabinets' => [
            'name_ar' => 'خزائن',
            'photos'  => [
                ['id' => 'photo-1558997519-83ea9252edf8', 'alt_ar' => 'دولاب خشبي بأبواب من خشب البلوط', 'alt_en' => 'Wooden wardrobe with oak doors'],
                ['id' => 'photo-1595428774223-ef52624120d2', 'alt_ar' => 'خزانة عمودية بأرفف مفتوحة', 'alt_en' => 'Tall cabinet with open shelving'],
                ['id' => 'photo-1521587760476-6c12a4b040da', 'alt_ar' => 'مكتبة كتب خشبية', 'alt_en' => 'Wooden bookshelf'],
                ['id' => 'photo-1600585152220-90363fe7e115', 'alt_ar' => 'خزائن مطبخ خشبية عصرية', 'alt_en' => 'Modern wooden kitchen cabinets'],
                ['id' => 'photo-1567225557594-88d73e55f2cb', 'alt_ar' => 'خزانة بيضاء مع نباتات زينة', 'alt_en' => 'White cabinet with decorative plants'],
                ['id' => 'photo-1493809842364-78817add7ffb', 'alt_ar' => 'وحدة تخزين جانبية بلون فاتح', 'alt_en' => 'Light sideboard storage unit'],
            ],
        ],
        'upholstery' => [
            'name_ar' => 'مفروشات',
            'photos'  => [
                ['id' => 'photo-1611967164521-abae8fba4668', 'alt_ar' => 'كرسي منجّد مع وسائد وبطانية', 'alt_en' => 'Upholstered armchair with cushions and throw'],
                ['id' => 'photo-1584100936595-c0654b55a2e2', 'alt_ar' => 'وسادة قطنية بيضاء', 'alt_en' => 'White cotton pillow'],
                ['id' => 'photo-1600166898405-da9535204843', 'alt_ar' => 'سجاد مزخرف ملفوف', 'alt_en' => 'Rolled ornate rug'],
                ['id' => 'photo-1522771739844-6a9f6d5f14af', 'alt_ar' => 'مفارش سرير ووسائد فاخرة', 'alt_en' => 'Luxury bed linen and cushions'],
                ['id' => 'photo-1567538096630-e0c55bd6374c', 'alt_ar' => 'كرسي منجّد بلون كريمي', 'alt_en' => 'Cream upholstered accent chair'],
                ['id' => 'photo-1519961655809-34fa156820ff', 'alt_ar' => 'كنبة بيضاء بوسائد ملونة', 'alt_en' => 'White sofa with coloured cushions'],
            ],
        ],
        'home-accessories' => [
            'name_ar' => 'إكسسوارات منزلية',
            'photos'  => [
                ['id' => 'photo-1526057565006-20beab8dd2ed', 'alt_ar' => 'فوانيس وإكسسوارات منزلية', 'alt_en' => 'Lanterns and home accessories'],
                ['id' => 'photo-1584589167171-541ce45f1eea', 'alt_ar' => 'مزهريات خشبية مع نباتات', 'alt_en' => 'Wooden vases with plants'],
                ['id' => 'photo-1513519245088-0e12902e5a38', 'alt_ar' => 'حائط لوحات وإطارات مزخرفة', 'alt_en' => 'Gallery wall of decorative frames'],
                ['id' => 'photo-1507473885765-e6ed057f782c', 'alt_ar' => 'أبجورة جدارية مودرن', 'alt_en' => 'Modern wall lamp'],
                ['id' => 'photo-1517991104123-1d56a6e81ed9', 'alt_ar' => 'أبجورة طاولة خشبية', 'alt_en' => 'Wooden table lamp'],
                ['id' => 'photo-1603006905003-be475563bc59', 'alt_ar' => 'شمعة مضاءة في حامل زجاجي', 'alt_en' => 'Lit candle in a glass holder'],
            ],
        ],
    ];


    /**
     * One dedicated hero per category, kept apart from the product pool so a
     * category tile never shows the same photo as a product card sitting
     * underneath it. All portrait — the UI renders categories in a 4/5 frame.
     *
     * @var array<string, array{id: string, alt_ar: string, alt_en: string}>
     */
    private const CATEGORY_PHOTOS = [
        'bedrooms'         => ['id' => 'photo-1512918728675-ed5a9ecdebfd', 'alt_ar' => 'تشكيلة غرف النوم', 'alt_en' => 'Bedrooms collection'],
        'living-rooms'     => ['id' => 'photo-1687180498602-5a1046defaa4', 'alt_ar' => 'تشكيلة غرف الجلوس', 'alt_en' => 'Living rooms collection'],
        'dining-rooms'     => ['id' => 'photo-1615968679312-9b7ed9f04e79', 'alt_ar' => 'تشكيلة غرف السفرة', 'alt_en' => 'Dining rooms collection'],
        'sofas'            => ['id' => 'photo-1606744888344-493238951221', 'alt_ar' => 'تشكيلة الكنب', 'alt_en' => 'Sofas collection'],
        'tables'           => ['id' => 'photo-1532372320572-cda25653a26d', 'alt_ar' => 'تشكيلة الطاولات', 'alt_en' => 'Tables collection'],
        'cabinets'         => ['id' => 'photo-1722349674028-a148f4364e43', 'alt_ar' => 'تشكيلة الخزائن', 'alt_en' => 'Cabinets collection'],
        'upholstery'       => ['id' => 'photo-1595622543083-5081cbe96e24', 'alt_ar' => 'تشكيلة المفروشات', 'alt_en' => 'Upholstery collection'],
        'home-accessories' => ['id' => 'photo-1572048572872-2394404cf1f3', 'alt_ar' => 'تشكيلة الإكسسوارات المنزلية', 'alt_en' => 'Home accessories collection'],
    ];

    /**
     * Dedicated photos for the products whose name is more specific than
     * anything the category pool holds — a "Marble Coffee Table" needs
     * marble, not whichever table the pool happened to land on. Landscape,
     * like the rest of the product covers.
     *
     * CatalogSeeder opts a product in by naming the key here as the fifth
     * element of its PRODUCT_NAMES row; everything else still draws from
     * the category pool.
     *
     * @var array<string, array{id: string, alt_ar: string, alt_en: string}>
     */
    private const PRODUCT_PHOTOS = [
        'marble-coffee-table'  => ['id' => 'photo-1649011327045-624a1bd2c3e7', 'alt_ar' => 'طاولة قهوة بسطح رخامي في غرفة جلوس', 'alt_en' => 'Marble-top coffee table in a living room'],
        'modern-dresser'       => ['id' => 'photo-1579656381254-1a60dc1ee3f4', 'alt_ar' => 'تسريحة خشبية بستة أدراج من الجوز', 'alt_en' => 'Six-drawer walnut dresser'],
        'golden-side-table'    => ['id' => 'photo-1643558544531-bff73bbffc28', 'alt_ar' => 'طاولتان جانبيتان بسطح رخامي وأرجل ذهبية', 'alt_en' => 'Marble-top side tables with gold legs'],
        'ornate-mirror'        => ['id' => 'photo-1612392549429-a874f09c65f1', 'alt_ar' => 'مرآة بإطار ذهبي مزخرف', 'alt_en' => 'Mirror in an ornate gold frame'],
        'console-table'        => ['id' => 'photo-1785535573604-62e31f0fd341', 'alt_ar' => 'كنسول خشبي طويل بجانب الجدار', 'alt_en' => 'Long wooden console table against a wall'],
        'ottoman'              => ['id' => 'photo-1568598617558-79e56547418f', 'alt_ar' => 'عثمانية جلدية منجّدة بلون بني', 'alt_en' => 'Tufted brown leather ottoman'],
        'area-rug'             => ['id' => 'photo-1534889156217-d643df14f14a', 'alt_ar' => 'سجادة شرقية كبيرة في غرفة جلوس', 'alt_en' => 'Large oriental area rug in a living room'],
        'embroidered-cushions' => ['id' => 'photo-1575277340591-849c346c4542', 'alt_ar' => 'وسائد ديكور مطرزة بألوان متعددة', 'alt_en' => 'Stack of embroidered decorative cushions'],
        'curtains'             => ['id' => 'photo-1771039622311-24e73898b641', 'alt_ar' => 'ستائر قماشية طويلة حتى الأرض', 'alt_en' => 'Floor-length fabric curtains'],
        'bed-linen'            => ['id' => 'photo-1775241186452-c3d99b09f223', 'alt_ar' => 'سرير مرتّب بمفارش وطبقات وسائد', 'alt_en' => 'Neatly made bed with layered linen'],
        'glass-vase'           => ['id' => 'photo-1598995651120-2f3150be6cf4', 'alt_ar' => 'مزهرية زجاجية مضلّعة مع ورود', 'alt_en' => 'Ribbed glass vase holding roses'],
        'ceramic-plate'        => ['id' => 'photo-1778215251273-34edeb165fa0', 'alt_ar' => 'طبق سيراميك أزرق بزخارف نباتية', 'alt_en' => 'Blue ceramic plate with floral patterning'],
        'prayer-rug'           => ['id' => 'photo-1600814832809-579119f47045', 'alt_ar' => 'سجادة صلاة أمام محراب خشبي', 'alt_en' => 'Prayer rug before a wooden mihrab'],
    ];

    /**
     * Fallback palettes, used only when a photo cannot be downloaded.
     */
    private array $palettes = [
        [[184, 155, 107], [222, 201, 164]], // brass / sand
        [[163, 132, 102], [205, 178, 153]], // walnut
        [[139, 108, 78], [178, 149, 120]], // wood
        [[110, 86, 65], [153, 128, 105]], // coffee
        [[92, 84, 74], [138, 129, 117]], // charcoal
        [[226, 217, 202], [245, 240, 230]], // cream
        [[237, 224, 205], [249, 242, 232]], // beige
        [[176, 161, 140], [214, 202, 186]], // sand stone
        [[120, 104, 88], [168, 152, 134]], // taupe
        [[212, 183, 150], [238, 220, 195]], // honey
    ];

    public function run(): void
    {
        $directory = storage_path('app/public/demo');

        if (! is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        $downloaded = 0;
        $drawn = 0;
        $index = 0;

        foreach (self::POOL as $slug => $group) {
            foreach ($group['photos'] as $position => $photo) {
                // first of each group is the portrait category cover
                [$width, $height] = $position === 0 ? [1200, 1500] : [1600, 1200];

                $filename = $slug.'-'.($position + 1).'.webp';
                $path = $directory.'/'.$filename;

                if ($this->download($photo['id'], $path, $width, $height)) {
                    $downloaded++;
                } else {
                    $palette = $this->palettes[$index % count($this->palettes)];
                    $backdrop = $this->palettes[($index * 3 + 5) % count($this->palettes)];
                    $this->drawPlaceholder($path, $width, $height, $palette, $backdrop);
                    $drawn++;
                }

                $this->recordMedia($slug, $filename, $path, $width, $height, $photo);

                $index++;
            }
        }

        [$catDownloaded, $catDrawn] = $this->seedCategoryHeroes($index);

        $downloaded += $catDownloaded;
        $drawn += $catDrawn;
        $index += count(self::CATEGORY_PHOTOS);

        [$prodDownloaded, $prodDrawn] = $this->seedProductHeroes($index);

        $downloaded += $prodDownloaded;
        $drawn += $prodDrawn;

        $this->command?->info("✓ صور المعرض: {$downloaded} صورة منزّلة، {$drawn} صورة بديلة مرسومة.");
    }

    /**
     * Category heroes live in their own directory and collection so the
     * product pool stays untouched.
     *
     * @return array{0: int, 1: int} downloaded and drawn counts
     */
    private function seedCategoryHeroes(int $index): array
    {
        $directory = storage_path('app/public/categories');

        if (! is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        $downloaded = 0;
        $drawn = 0;

        foreach (self::CATEGORY_PHOTOS as $slug => $photo) {
            $filename = $slug.'.webp';
            $path = $directory.'/'.$filename;

            if ($this->download($photo['id'], $path, 1200, 1500)) {
                $downloaded++;
            } else {
                $palette = $this->palettes[$index % count($this->palettes)];
                $backdrop = $this->palettes[($index * 3 + 5) % count($this->palettes)];
                $this->drawPlaceholder($path, 1200, 1500, $palette, $backdrop);
                $drawn++;
            }

            $this->recordMedia($slug, $filename, $path, 1200, 1500, $photo, 'categories');

            $index++;
        }

        return [$downloaded, $drawn];
    }

    /**
     * Named product photos, in their own directory and collection for the
     * same reason the category heroes have one: the category pool stays a
     * pool, and these stay pinned to the product that needs them.
     *
     * @return array{0: int, 1: int} downloaded and drawn counts
     */
    private function seedProductHeroes(int $index): array
    {
        $directory = storage_path('app/public/products');

        if (! is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        $downloaded = 0;
        $drawn = 0;

        foreach (self::PRODUCT_PHOTOS as $key => $photo) {
            $filename = $key.'.webp';
            $path = $directory.'/'.$filename;

            if ($this->download($photo['id'], $path, 1600, 1200)) {
                $downloaded++;
            } else {
                $palette = $this->palettes[$index % count($this->palettes)];
                $backdrop = $this->palettes[($index * 3 + 5) % count($this->palettes)];
                $this->drawPlaceholder($path, 1600, 1200, $palette, $backdrop);
                $drawn++;
            }

            $this->recordMedia($key, $filename, $path, 1600, 1200, $photo, 'products');

            $index++;
        }

        return [$downloaded, $drawn];
    }

    /**
     * Store the file against the media pool, keeping the row for a filename
     * that already exists so foreign keys elsewhere stay valid.
     */
    private function recordMedia(string $slug, string $filename, string $path, int $width, int $height, array $photo, string $collection = 'demo'): void
    {
        $media = Media::query()->firstOrNew([
            'collection' => $collection,
            'filename'   => $filename,
        ]);

        $media->forceFill([
            'disk'          => 'public',
            'collection'    => $collection,
            'directory'     => $collection,
            'filename'      => $filename,
            'original_name' => $slug.'.webp',
            'mime_type'     => 'image/webp',
            'extension'     => 'webp',
            'size'          => filesize($path) ?: 0,
            'width'         => $width,
            'height'        => $height,
            'alt_ar'        => $photo['alt_ar'],
            'alt_en'        => $photo['alt_en'],
            'is_processed'  => true,
        ])->save();
    }

    /**
     * Pull a cropped, re-encoded copy from the CDN. Returns false on any
     * failure so seeding still works without a network connection.
     */
    private function download(string $id, string $path, int $width, int $height): bool
    {
        $url = self::SOURCE.$id.'?'.http_build_query([
            'w'    => $width,
            'h'    => $height,
            'fit'  => 'crop',
            'crop' => 'entropy',
            'fm'   => 'webp',
            'q'    => 72,
        ]);

        $context = stream_context_create([
            'http' => ['timeout' => 30, 'user_agent' => 'abuafifa-seeder'],
        ]);

        $body = @file_get_contents($url, false, $context);

        if ($body === false || strlen($body) < 1024) {
            return false;
        }

        // reject anything that is not a real image before it reaches disk
        $img = @imagecreatefromstring($body);

        if ($img === false) {
            return false;
        }

        // Re-encode locally so the bytes on disk are WebP even if the CDN
        // ignored `fm` and handed us something else.
        $written = imagewebp($img, $path, self::WEBP_QUALITY);
        imagedestroy($img);

        return $written;
    }

    private function drawPlaceholder(string $path, int $width, int $height, array $palette, array $backdrop): void
    {
        $img = imagecreatetruecolor($width, $height);

        // vertical gradient background
        $this->gradient($img, $width, $height, $palette[0], $palette[1]);

        // soft backdrop "stage" circle
        $this->softCircle($img, $width, $height, $backdrop[0], $backdrop[1]);

        // muted dynamic speckle for a fabric texture feel
        $this->speckle($img, $width, $height);

        imagewebp($img, $path, self::WEBP_QUALITY);
        imagedestroy($img);
    }

    private function gradient($img, int $w, int $h, array $top, array $bottom): void
    {
        for ($y = 0; $y < $h; $y++) {
            $t = $y / max(1, $h - 1);
            $r = (int) ($top[0] + ($bottom[0] - $top[0]) * $t);
            $g = (int) ($top[1] + ($bottom[1] - $top[1]) * $t);
            $b = (int) ($top[2] + ($bottom[2] - $top[2]) * $t);
            imageline($img, 0, $y, $w, $y, imagecolorallocate($img, $r, $g, $b));
        }
    }

    private function softCircle($img, int $w, int $h, array $c1, array $c2): void
    {
        $cx = (int) ($w * 0.5);
        $cy = (int) ($h * 0.55);
        $radius = (int) (min($w, $h) * 0.35);

        for ($i = 0; $i < $radius; $i++) {
            $alpha = (int) (127 * $i / max(1, $radius));
            $r = (int) ($c1[0] + ($c2[0] - $c1[0]) * ($i / max(1, $radius)));
            $g = (int) ($c1[1] + ($c2[1] - $c1[1]) * ($i / max(1, $radius)));
            $b = (int) ($c1[2] + ($c2[2] - $c1[2]) * ($i / max(1, $radius)));
            $color = imagecolorallocatealpha($img, $r, $g, $b, $alpha);
            imageellipse($img, $cx, $cy, $i * 3, $i * 3, $color);
        }
    }

    private function speckle($img, int $w, int $h): void
    {
        mt_srand(12345 + $w + $h);
        for ($i = 0; $i < $w * $h / 350; $i++) {
            $x = mt_rand(0, $w - 1);
            $y = mt_rand(0, $h - 1);
            $brightness = mt_rand(0, 32);
            $color = imagecolorallocatealpha(
                $img,
                $brightness, $brightness, $brightness,
                mt_rand(90, 120)
            );
            imagesetpixel($img, $x, $y, $color);
        }
    }
}
