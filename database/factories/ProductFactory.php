<?php

namespace Database\Factories;

use App\Enums\AvailabilityStatus;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        $isNew = fake()->boolean(25);
        $isFeatured = fake()->boolean(30);
        $price = fake()->numberBetween(80, 900) * 10;
        $hasSale = fake()->boolean(40);

        return [
            'category_id'          => Category::inRandomOrder()->first()?->id,
            'name_ar'              => $this->randomName(),
            'name_en'              => $this->randomEnglishName(),
            'slug'                 => Str::slug(fake()->unique()->words(2, true)),
            'sku'                  => strtoupper('AF-'.fake()->unique()->numerify('####-####')),
            'short_description_ar' => fake()->sentence(10),
            'description_ar'       => fake()->paragraph(4),
            'price'                => $price,
            'sale_price'           => $hasSale ? (int) round($price * fake()->randomElement([0.75, 0.8, 0.85, 0.9])) : null,
            'currency'             => 'JOD',
            'dimensions_ar'        => fake()->randomElement([' العرض 240 × العمق 90 × الإرتفاع 85 سم', '180×80×75 سم', '200×100×90 سم', '250×90×80 سم']),
            'material_ar'          => fake()->randomElement(['خشب زان طبيعي', 'خشب بلوط مع قماش كتان', 'صندل مع جلد طبيعي', 'خشب سنديان فاخر', 'قماش مخمل برتغالي']),
            'color_ar'             => fake()->randomElement(['بيج', 'كريمي', 'بني فاتح', 'أبيض عاجي', 'رمادي فحمي', 'خشبي طبيعي']),
            'availability_status'  => fake()->randomElement(AvailabilityStatus::cases()),
            'is_featured'          => $isFeatured,
            'is_new'               => $isNew && fake()->boolean(70),
            'sort_order'           => 0,
            'is_active'            => true,
            'views_count'          => fake()->numberBetween(0, 9000),
        ];
    }

    private function randomName(): string
    {
        return match (fake()->numberBetween(1, 6)) {
            1 => 'صالة '.fake()->randomElement(['رويال', 'مودرن', 'كلاسيك', 'إمبراطوري', 'نسيم', 'رئيسية']),
            2 => 'غرفة نوم '.fake()->randomElement(['فينيسيا', 'لورد', 'ملكية', 'عصرية', 'كاملة']),
            3 => 'طقم كنب '.fake()->randomElement(['فاخر', 'انجليزي', 'إيطالي', 'قطن', 'جلد']),
            4 => 'طاولة طعام '.fake()->randomElement(['عائلية', 'ممتدة', 'دائرية', 'مستطيلة']),
            5 => 'دولاب خشبي '.fake()->randomElement(['مدرج', 'فاخر', 'مودرن']),
            default => 'أطقم '.fake()->randomElement(['ضيافة', 'غرف طعام', 'مكاتب']),
        };
    }

    private function randomEnglishName(): string
    {
        return 'Furniture '.fake()->bothify('##??');
    }
}
