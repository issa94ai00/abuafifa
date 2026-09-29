<?php

namespace Tests\Feature\Admin;

use App\Enums\DiscountType;
use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Offer;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OfferManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);

        $this->admin = User::factory()->admin()->create();
        $this->admin->assignRole(UserRole::SuperAdmin->value);

        // ProductFactory attaches products to an existing category.
        Category::factory()->create();
    }

    public function test_index_presents_the_accessors_the_page_renders(): void
    {
        $offer = Offer::factory()->create([
            'discount_type'  => DiscountType::Percentage,
            'discount_value' => 25,
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.offers.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Offers/Index')
                ->where('offers.data.0.id', $offer->id)
                // These are accessors, not columns: the page showed "—"
                // for every discount before the controller presented them.
                ->where('offers.data.0.badge_label', $offer->badge_label)
                ->where('offers.data.0.state', 'active')
                ->has('offers.data.0.product_ids')
                ->has('stats.active')
                ->has('products')
                ->has('currency'));
    }

    public function test_it_creates_an_offer_with_products_and_a_unique_slug(): void
    {
        $products = Product::factory()->count(2)->create();

        $this->actingAs($this->admin)
            ->post(route('admin.offers.store'), [
                'title_ar'       => 'تخفيضات الموسم',
                'discount_type'  => 'percentage',
                'discount_value' => 30,
                'is_active'      => true,
                'sync_products'  => 1,
                'products'       => $products->pluck('id')->all(),
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $offer = Offer::query()->latest('id')->first();

        $this->assertSame('تخفيضات الموسم', $offer->title_ar);
        $this->assertSame(DiscountType::Percentage, $offer->discount_type);
        $this->assertCount(2, $offer->products);
        $this->assertNotEmpty($offer->slug);
    }

    public function test_auto_generated_slugs_do_not_collide(): void
    {
        $payload = [
            'title_ar'       => 'عرض متكرر',
            'discount_type'  => 'fixed',
            'discount_value' => 10,
        ];

        $this->actingAs($this->admin)->post(route('admin.offers.store'), $payload)->assertRedirect();
        // The unique index on `offers.slug` made this second save a 500.
        $this->actingAs($this->admin)->post(route('admin.offers.store'), $payload)->assertRedirect();

        $slugs = Offer::query()->pluck('slug');

        $this->assertCount(2, $slugs);
        $this->assertCount(2, $slugs->unique());
    }

    public function test_it_updates_an_offer_and_clears_the_unused_discount_field(): void
    {
        $offer = Offer::factory()->create([
            'discount_type'  => DiscountType::Percentage,
            'discount_value' => 20,
            'price_override' => null,
        ]);

        $this->actingAs($this->admin)
            ->put(route('admin.offers.update', $offer), [
                'title_ar'       => 'عرض محدَّث',
                'discount_type'  => 'custom_price',
                'price_override' => 499,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $offer->refresh();

        $this->assertSame('عرض محدَّث', $offer->title_ar);
        $this->assertSame(DiscountType::CustomPrice, $offer->discount_type);
        $this->assertSame('499.00', $offer->price_override);
        $this->assertNull($offer->discount_value);
    }

    public function test_it_detaches_every_product_when_the_form_sends_an_empty_list(): void
    {
        $offer = Offer::factory()->create();
        $offer->products()->sync(Product::factory()->count(2)->create()->pluck('id'));

        $this->actingAs($this->admin)
            ->put(route('admin.offers.update', $offer), [
                'title_ar'       => $offer->title_ar,
                'discount_type'  => 'percentage',
                'discount_value' => 15,
                // Multipart encoding drops an empty array, so the form
                // flags that the payload carries the full product list.
                'sync_products' => 1,
            ])
            ->assertRedirect();

        $this->assertCount(0, $offer->fresh()->products);
    }

    public function test_it_rejects_a_percentage_above_one_hundred(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.offers.store'), [
                'title_ar'       => 'خصم مستحيل',
                'discount_type'  => 'percentage',
                'discount_value' => 150,
            ])
            ->assertSessionHasErrors('discount_value');

        $this->assertSame(0, Offer::query()->count());
    }

    public function test_it_requires_a_custom_price_for_a_custom_price_offer(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.offers.store'), [
                'title_ar'      => 'سعر خاص',
                'discount_type' => 'custom_price',
            ])
            ->assertSessionHasErrors('price_override');
    }

    public function test_it_toggles_visibility(): void
    {
        $offer = Offer::factory()->create(['is_active' => true]);

        $this->actingAs($this->admin)
            ->patch(route('admin.offers.toggle-active', $offer))
            ->assertRedirect();

        $this->assertFalse($offer->fresh()->is_active);

        $this->actingAs($this->admin)
            ->patch(route('admin.offers.toggle-active', $offer))
            ->assertRedirect();

        $this->assertTrue($offer->fresh()->is_active);
    }

    public function test_it_soft_deletes_and_restores(): void
    {
        $offer = Offer::factory()->create();

        $this->actingAs($this->admin)
            ->delete(route('admin.offers.destroy', $offer))
            ->assertRedirect();

        $this->assertSoftDeleted($offer);

        $this->actingAs($this->admin)
            ->post(route('admin.offers.restore', $offer->id))
            ->assertRedirect();

        $this->assertNotSoftDeleted($offer);
    }

    public function test_show_lists_only_the_attached_products(): void
    {
        $attached = Product::factory()->count(2)->create();
        Product::factory()->count(3)->create(); // unrelated

        $offer = Offer::factory()->create();
        $offer->products()->sync($attached->pluck('id'));

        $this->actingAs($this->admin)
            ->get(route('admin.offers.show', $offer))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Offers/Show')
                // The page used to receive every active product here and
                // render them all as "linked products".
                ->has('products', 2)
                ->has('allProducts', 5)
                ->where('offer.id', $offer->id));
    }

    public function test_the_state_filter_matches_the_counters(): void
    {
        Offer::factory()->create(['is_active'             => true]);
        Offer::factory()->upcoming()->create(['is_active' => true]);
        Offer::factory()->create(['is_active'             => false]);

        $this->actingAs($this->admin)
            ->get(route('admin.offers.index', ['state' => 'upcoming']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('offers.data', 1)
                ->where('offers.data.0.state', 'upcoming')
                ->where('stats.upcoming', 1)
                ->where('stats.hidden', 1));
    }

    public function test_a_content_manager_may_create_but_not_delete(): void
    {
        $manager = User::factory()->admin()->create();
        $manager->assignRole(UserRole::ContentManager->value);

        $offer = Offer::factory()->create();

        $this->actingAs($manager)
            ->post(route('admin.offers.store'), [
                'title_ar'       => 'عرض من محرر المحتوى',
                'discount_type'  => 'percentage',
                'discount_value' => 10,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        // The role grants offer.create and offer.edit but not offer.delete.
        $this->actingAs($manager)
            ->delete(route('admin.offers.destroy', $offer))
            ->assertForbidden();

        $this->assertNotSoftDeleted($offer);
    }
}
