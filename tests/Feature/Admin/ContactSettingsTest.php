<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Models\Setting;
use App\Models\User;
use App\Rules\PhoneNumber;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ContactSettingsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);

        $this->admin = User::factory()->admin()->create();
        $this->admin->assignRole(UserRole::SuperAdmin->value);

        // Settings writes sit behind a password confirmation.
        $this->session(['auth.password_confirmed_at' => time()]);
    }

    private function saveContact(array $payload)
    {
        return $this->actingAs($this->admin)
            ->put(route('admin.settings.update', ['group' => 'contact']), $payload);
    }

    private function valid(array $overrides = []): array
    {
        return array_merge([
            'phone'         => '+962 3 201 5555',
            'whatsapp'      => '+962 79 012 3456',
            'email'         => 'info@abuafifa.com',
            'address_ar'    => 'العقبة، الأردن',
            'map_lat'       => '29.5267',
            'map_lng'       => '35.0078',
            'working_hours' => 'السبت - الخميس',
        ], $overrides);
    }

    public function test_it_saves_contact_settings(): void
    {
        $this->saveContact($this->valid())
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('settings', ['group' => 'contact', 'key' => 'phone']);
        $this->assertSame('+962 79 012 3456', setting('contact.whatsapp'));
        $this->assertSame('29.5267', setting('contact.map_lat'));
    }

    public function test_it_rejects_a_whatsapp_number_without_a_country_code(): void
    {
        // 079… becomes wa.me/0790123456, which resolves to nothing.
        $this->saveContact($this->valid(['whatsapp' => '079 012 3456']))
            ->assertSessionHasErrors('whatsapp');
    }

    public function test_it_accepts_a_whatsapp_number_written_with_a_double_zero_prefix(): void
    {
        $this->saveContact($this->valid(['whatsapp' => '00962790123456']))
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertSame('00962790123456', setting('contact.whatsapp'));
    }

    public function test_it_rejects_a_phone_number_containing_letters(): void
    {
        $this->saveContact($this->valid(['phone' => 'call us now']))
            ->assertSessionHasErrors('phone');
    }

    public function test_it_rejects_a_phone_number_that_is_too_short(): void
    {
        $this->saveContact($this->valid(['phone' => '12345']))
            ->assertSessionHasErrors('phone');
    }

    public function test_it_rejects_half_a_coordinate_pair(): void
    {
        // The storefront falls back to a default for the missing half, which
        // silently drops the pin somewhere else entirely.
        $this->saveContact($this->valid(['map_lng' => '']))
            ->assertSessionHasErrors('map_lng');

        $this->saveContact($this->valid(['map_lat' => '']))
            ->assertSessionHasErrors('map_lat');
    }

    public function test_it_accepts_clearing_both_coordinates(): void
    {
        $this->saveContact($this->valid(['map_lat' => '', 'map_lng' => '']))
            ->assertSessionHasNoErrors()
            ->assertRedirect();
    }

    public function test_it_rejects_out_of_range_coordinates(): void
    {
        $this->saveContact($this->valid(['map_lat' => '95']))->assertSessionHasErrors('map_lat');
        $this->saveContact($this->valid(['map_lng' => '-200']))->assertSessionHasErrors('map_lng');
    }

    public function test_it_collapses_pasted_whitespace_in_numbers(): void
    {
        $this->saveContact($this->valid(['phone' => "  +962   3   201  5555  "]))
            ->assertRedirect();

        $this->assertSame('+962 3 201 5555', setting('contact.phone'));
    }

    public function test_saving_contact_does_not_touch_another_group(): void
    {
        app(\App\Services\SettingsService::class)->set('site', 'name', 'أبو عفيفة');

        $this->saveContact($this->valid())->assertRedirect();

        $this->assertSame('أبو عفيفة', setting('site.name'));
        $this->assertSame(0, Setting::query()->where('group', 'site')->where('key', 'phone')->count());
    }

    public function test_an_unknown_group_is_rejected(): void
    {
        $this->actingAs($this->admin)
            ->put(route('admin.settings.update', ['group' => 'contact']), $this->valid())
            ->assertRedirect();

        // The route only binds the four known groups.
        $this->actingAs($this->admin)
            ->put('/admin/settings/secrets', $this->valid())
            ->assertNotFound();
    }

    #[DataProvider('dialNumbers')]
    public function test_it_derives_the_digits_a_wa_me_link_needs(string $input, string $expected, bool $international): void
    {
        $this->assertSame($expected, PhoneNumber::digits($input));
        $this->assertSame($international, PhoneNumber::hasCountryCode($input));
    }

    public static function dialNumbers(): array
    {
        return [
            'plus prefix'   => ['+962 79 012 3456', '962790123456', true],
            'double zero'   => ['00962790123456', '962790123456', true],
            'local trunk'   => ['079 012 3456', '0790123456', false],
            'bare national' => ['962790123456', '962790123456', true],
        ];
    }
}
