<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class CategoryFactory extends Factory
{
    protected $model = Category::class;

    private const NAMES = [
        ['name_ar' => 'غرف المعيشة', 'name_en' => 'Living Room'],
        ['name_ar' => 'غرف النوم', 'name_en' => 'Bedroom'],
        ['name_ar' => 'طاولات الطعام', 'name_en' => 'Dining Tables'],
        ['name_ar' => 'المكاتب', 'name_en' => 'Office'],
        ['name_ar' => 'الحدائق والشرفات', 'name_en' => 'Outdoor'],
        ['name_ar' => 'غرف الأولاد', 'name_en' => 'Kids Room'],
        ['name_ar' => 'المطابخ', 'name_en' => 'Kitchen'],
        ['name_ar' => 'حمّامات', 'name_en' => 'Bathroom'],
        ['name_ar' => 'ديكورات منزليّة', 'name_en' => 'Home Decor'],
        ['name_ar' => 'إكسسوارات وإضاءة', 'name_en' => 'Accessories'],
    ];

    public function definition(): array
    {
        $name = fake()->randomElement(self::NAMES);

        return [
            'name_ar'        => $name['name_ar'],
            'name_en'        => $name['name_en'],
            'slug'           => Str::slug($name['name_en']),
            'description_ar' => fake()->optional(0.7)->sentence(15),
            'sort_order'     => fake()->numberBetween(0, 20),
            'is_active'      => true,
        ];
    }
}
