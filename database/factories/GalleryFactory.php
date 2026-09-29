<?php

namespace Database\Factories;

use App\Models\Gallery;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class GalleryFactory extends Factory
{
    protected $model = Gallery::class;

    public function definition(): array
    {
        return [
            'title_ar' => fake()->randomElement([
                'قاعة العرض الرئيسية',
                'أجنحة غرف النوم',
                'ركن المودرن',
                'قسم العائلات',
                'جاليري الصالونات',
                'ملحق الأجواء الخارجية',
            ]),
            'slug'           => Str::slug(fake()->unique()->words(2, true)),
            'description_ar' => fake()->optional(0.6)->sentence(12),
            'sort_order'     => fake()->numberBetween(0, 10),
            'is_active'      => true,
        ];
    }
}
