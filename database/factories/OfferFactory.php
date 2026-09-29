<?php

namespace Database\Factories;

use App\Enums\DiscountType;
use App\Models\Offer;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

class OfferFactory extends Factory
{
    protected $model = Offer::class;

    public function definition(): array
    {
        $type = fake()->randomElement(DiscountType::cases());

        return [
            'title_ar' => fake()->randomElement([
                'تخفيضات الموسم الكبرى',
                'عروض الشتاء',
                'خصم خاص للمعرض',
                'صيف على الذوق السوري',
                'عروض نهاية العام',
                'وصل الجديد',
            ]),
            'slug'           => str()->slug(fake()->unique()->words(3, true)),
            'description_ar' => fake()->paragraph(2),
            'discount_type'  => $type,
            'discount_value' => $type === DiscountType::CustomPrice ? null : fake()->numberBetween(5, 50),
            'price_override' => $type === DiscountType::CustomPrice ? fake()->numberBetween(3, 50) * 100_000 : null,
            'starts_at'      => Carbon::now()->subDays(fake()->numberBetween(0, 15)),
            'ends_at'        => Carbon::now()->addDays(fake()->numberBetween(10, 60)),
            'sort_order'     => fake()->numberBetween(0, 10),
            'is_active'      => true,
        ];
    }

    public function upcoming(): static
    {
        return $this->state(fn () => [
            'starts_at' => Carbon::now()->addDays(5),
        ]);
    }
}
