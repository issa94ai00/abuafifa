# Home page imagery

All photos sourced from [Unsplash](https://unsplash.com) under the
[Unsplash License](https://unsplash.com/license) (free for commercial use,
no attribution required — credited here for provenance).

Downloaded locally instead of hot-linking so the home page does not depend on
a third-party CDN, and so the files can be re-encoded (progressive JPEG,
q72 / q55 for overlay textures).

| File | Unsplash photo | Used for |
|---|---|---|
| `hero-bedroom-{768,1280,1920}.webp`  | `photo-1616594039964-ae9021a400a0` | Hero slide 1 — luxury bedroom |
| `hero-living-{768,1280,1920}.webp`   | `photo-1533779283484-8ad4940aa3a8` | Hero slide 2 — warm living room |
| `hero-dining-{768,1280,1920}.webp`   | `photo-1617806118233-18e1de247200` | Hero slide 3 — elegant dining room |
| `about-showroom-{800,1400}.webp`     | `photo-1600210492486-724fe5c67fb0` | About section |
| `craft-textile-800.webp`             | `photo-1616627561950-9f746e330187` | Craftsmanship — upholstery detail |
| `craft-chair-800.webp`               | `photo-1598300042247-d088f8ab3a91` | Craftsmanship — frame / chair |
| `craft-leather-800.webp`             | `photo-1540574163026-643ea20ade25` | Craftsmanship — leather |
| `texture-offers-1200.jpg`           | `photo-1615529182904-14819c35db37` | Offers band background texture |
| `texture-cta-1200.jpg`              | `photo-1618221195710-dd6b41faaea6` | CTA banner background texture |

To replace any image, keep the same filename and width suffixes so the
`srcset` in `HeroSection.vue` / `Home.vue` keeps working.

## Catalogue imagery

The 48 demo photos behind the categories, products, offers and galleries are
also Unsplash, under the same licence. They are not kept here: they live on the
public disk at `storage/app/public/demo/` and are downloaded by
`database/seeders/DemoImagesSeeder.php`, which holds the photo ids grouped by
category. Re-download them at any time with:

```
php artisan db:seed --class=DemoImagesSeeder
php artisan db:seed --class=CatalogSeeder
```

Files are named `{category-slug}-{1..6}.webp`; `-1` is the portrait one used as
the category cover. The seeder falls back to drawing GD placeholders if it
cannot reach the CDN, so seeding still works offline.

### Category heroes

Each of the 8 categories has its own portrait photo, seeded into a separate
`categories` collection at `storage/app/public/categories/{slug}.jpg` and held
apart from the product pool — otherwise a category tile shows the same photo as
a product card sitting directly beneath it. Same seeder, same licence; the ids
are in `DemoImagesSeeder::CATEGORY_PHOTOS`.
