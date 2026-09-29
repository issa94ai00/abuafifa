<?php

namespace Database\Factories;

use App\Models\ContactMessage;
use Illuminate\Database\Eloquent\Factories\Factory;

class ContactMessageFactory extends Factory
{
    protected $model = ContactMessage::class;

    public function definition(): array
    {
        return [
            'name'    => fake()->name('ar'),
            'phone'   => fake()->numerify('+962-7#-###-####'),
            'email'   => fake()->optional(0.6)->safeEmail(),
            'subject' => fake()->optional(0.8)->randomElement([
                'استفسار عن سعر',
                'طلب توريد لغرفة معيشة',
                'معاينة في الصالة',
                'تصميم داخلي مخصص',
                'سؤال حول التوصيل',
            ]),
            'message'    => fake()->paragraph(2),
            'ip_address' => fake()->ipv4(),
            'is_read'    => fake()->boolean(30),
        ];
    }
}
