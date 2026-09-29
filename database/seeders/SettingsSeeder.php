<?php

namespace Database\Seeders;

use App\Models\SocialLink;
use App\Services\SettingsService;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = app(SettingsService::class);

        $groups = [
            'site' => [
                'name'        => 'معرض أبو عفيفة للمفروشات',
                'title'       => 'معرض أبو عفيفة للمفروشات — أثاث فاخر في العقبة',
                'description' => 'أرقى صالونات، غرف نوم، وطاولات طعام من معرض أبو عفيفة.',
                'currency'    => 'JOD',
                'email'       => 'info@abuafifa.com',
            ],
            'contact' => [
                'phone'         => '+962 3 201 5555',
                'whatsapp'      => '+962 79 012 3456',
                'email'         => 'info@abuafifa.com',
                'address_ar'    => 'شارع الملك حسين، وسط المدينة، العقبة — الأردن',
                'address_en'    => 'King Hussein Street, City Centre, Aqaba — Jordan',
                'map_lat'       => '29.5267',
                'map_lng'       => '35.0078',
                'working_hours' => 'السبت - الخميس: 10 صباحاً - 9 مساءً · الجمعة: مغلق',
            ],
            'seo' => [
                'default_title'       => 'معرض أبو عفيفة للمفروشات — أثاث فاخر في العقبة',
                'default_description' => 'معرض أبو عفيفة للمفروشات في العقبة: صالونات فاخرة، غرف نوم، طاولات طعام، ومكاتب بأرقى الخامات والتصاميم العصرية، بحرفية أصيلة.',
                'default_keywords'    => 'أثاث, مفروشات, صالونات, غرف نوم, العقبة, الأردن, أثاث العقبة, معرض أبو عفيفة',
            ],
            'homepage' => [
                'hero_title_ar'     => 'اجعل بيتك يليق بك',
                'hero_subtitle_ar'  => 'تشكيلات فاخرة من الصالونات وغرف النوم بأرقى الخامات والتصاميم',
                'show_featured'     => '1',
                'show_new_arrivals' => '1',
                'show_offers'       => '1',
                'about_intro_ar'    => 'منذ سنوات، ونحن نؤثّت البيوت بأرقى المفروشات؛ حرفية أصيلة نقدّمها اليوم من قلب العقبة، جودة تصنع الفرق وذوق يعكس شخصيتك.',
                'about_intro_en'    => 'For years we have been furnishing homes with the finest furniture — authentic Aleppan craftsmanship, now from the heart of Aqaba; quality that makes a difference.',
            ],
        ];

        foreach ($groups as $group => $values) {
            foreach ($values as $key => $value) {
                $settings->set($group, $key, $value);
            }
        }

        $settings->flush();

        SocialLink::query()->delete();

        $links = [
            [
                'platform'   => 'facebook',
                'url'        => 'https://facebook.com/abuafifa',
                'icon'       => 'facebook',
                'label_ar'   => 'فيسبوك',
                'sort_order' => 0,
                'is_active'  => true,
            ],
            [
                'platform'   => 'instagram',
                'url'        => 'https://instagram.com/abuafifa',
                'icon'       => 'instagram',
                'label_ar'   => 'إنستغرام',
                'sort_order' => 1,
                'is_active'  => true,
            ],
            [
                'platform'   => 'whatsapp',
                'url'        => 'https://wa.me/962790123456',
                'icon'       => 'whatsapp',
                'label_ar'   => 'واتساب',
                'sort_order' => 2,
                'is_active'  => true,
            ],
            [
                'platform'   => 'tiktok',
                'url'        => 'https://tiktok.com/@abuafifa',
                'icon'       => 'tiktok',
                'label_ar'   => 'تيك توك',
                'sort_order' => 3,
                'is_active'  => true,
            ],
            [
                'platform'   => 'youtube',
                'url'        => 'https://youtube.com/@abuafifa',
                'icon'       => 'youtube',
                'label_ar'   => 'يوتيوب',
                'sort_order' => 4,
                'is_active'  => true,
            ],
        ];

        foreach ($links as $link) {
            SocialLink::create($link);
        }
    }
}