<?php

namespace Modules\SocialCommerce\Tests\Feature;

use App\Models\Category;
use App\Models\Currency;
use App\Models\GeneralSetting;
use App\Models\Product;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Modules\SocialCommerce\Entities\SocialCommerceSetting;
use Modules\SocialCommerce\Entities\SocialProductSetting;
use Modules\SocialCommerce\Services\ProductLinkResolver;
use Modules\SocialCommerce\Services\SocialCommerceStorefrontCapability;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\TestCase;

class SocialCommerceIndependenceTest extends TestCase
{
    use DatabaseTransactions;

    private Currency $currency;
    private Unit $unit;
    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        View::share('alert_product', 0);
        View::share('dso_alert_product_no', 0);
        View::share('expire_alert_products', 0);

        $this->currency = Currency::create([
            'name' => 'Corrective Test Dollar',
            'code' => 'CTD',
            'symbol' => '$',
            'exchange_rate' => 1,
            'is_active' => true,
        ]);

        GeneralSetting::create([
            'site_title' => 'Social Commerce Corrective Test',
            'currency' => $this->currency->id,
            'currency_position' => 'prefix',
            'staff_access' => 'all',
            'without_stock' => 'no',
            'date_format' => 'd-m-Y',
            'theme' => 'default.css',
            'modules' => '',
            'decimal' => 2,
            'timezone' => 'UTC',
        ]);

        $this->unit = Unit::create([
            'unit_name' => 'Corrective Test Unit',
            'unit_code' => 'CTU-' . uniqid(),
            'operator' => '*',
            'operation_value' => 1,
            'is_active' => true,
        ]);

        $this->category = Category::create([
            'name' => 'Corrective Test Category ' . uniqid(),
            'slug' => 'corrective-test-category-' . uniqid(),
            'is_active' => true,
        ]);

        SocialCommerceSetting::query()->delete();
        SocialCommerceSetting::create([
            'is_active' => true,
            'allow_fallback' => true,
            'whatsapp_number' => '15555550100',
        ]);
    }

    protected function tearDown(): void
    {
        Cache::flush();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        parent::tearDown();
    }

    public function test_catalog_loads_through_real_middleware_with_complete_fixtures(): void
    {
        $product = $this->createProduct();
        $this->publish($product);
        $this->actingAs($this->createAuthorizedUser());

        $canonicalUrl = route('socialcommerce.public.show', $product->id);
        $response = $this->get(route('socialcommerce.catalog', ['filter' => 'published']));

        $response->assertOk();
        $response->assertSee($product->name);
        $response->assertSee('type="button"', false);
        $response->assertSee('class="btn btn-outline-secondary sc-copy-btn"', false);
        $response->assertSee('data-url="' . $canonicalUrl . '"', false);
        $response->assertSee('class="btn btn-sm btn-dark sc-qr-btn"', false);
        $response->assertDontSee('onclick="document.execCommand', false);
        $response->assertDontSee('modules/ecommerce', false);
    }

    public function test_product_link_resolver_uses_named_social_commerce_route_with_subdirectory_url(): void
    {
        config(['app.url' => 'http://localhost/salepro']);
        $product = $this->createProduct();

        $resolved = app(ProductLinkResolver::class)->resolve($product);

        $this->assertSame(route('socialcommerce.public.show', $product->id), $resolved);
        $this->assertStringEndsWith('/social/' . $product->id, $resolved);
    }

    public function test_valid_publication_renders_social_page_and_fallback_without_ecommerce_action(): void
    {
        $product = $this->createProduct(['product_details' => 'Independent product details']);
        $this->publish($product);
        $this->bindUnavailableCapability();

        $response = $this->get(route('socialcommerce.public.show', $product->id));

        $response->assertOk();
        $response->assertSee($product->name);
        $response->assertSee($product->code);
        $response->assertSee('Independent product details');
        $response->assertSee('wa.me', false);
        $response->assertDontSee(__('db.Buy online'));
    }

    public function test_ecommerce_action_uses_verified_route_without_changing_canonical_owner(): void
    {
        $product = $this->createProduct(['is_online' => true, 'slug' => 'corrective-online-product']);
        $this->publish($product);
        if (!Route::has('ecommerce.product.show')) {
            Route::get('/corrective-ecommerce-product/{product_name}/{product_id}', fn () => 'ok')
                ->name('ecommerce.product.show');
            Route::getRoutes()->refreshNameLookups();
        }
        $canonical = route('socialcommerce.public.show', $product->id);
        $ecommerce = route('ecommerce.product.show', [
            'product_name' => $product->slug,
            'product_id' => $product->id,
        ]);

        $capability = \Mockery::mock(SocialCommerceStorefrontCapability::class);
        $capability->shouldReceive('canPurchaseOnline')->withArgs(fn ($candidate) => $candidate->is($product))->andReturnTrue();
        $capability->shouldReceive('getEcommerceUrl')->withArgs(fn ($candidate) => $candidate->is($product))->andReturn($ecommerce);
        $this->app->instance(SocialCommerceStorefrontCapability::class, $capability);

        $response = $this->get($canonical);

        $response->assertOk();
        $response->assertSee(__('db.Buy online'));
        $response->assertSee('href="' . $ecommerce . '"', false);
        $this->assertSame($canonical, app(ProductLinkResolver::class)->resolve($product));
        $response->assertViewIs('socialcommerce::public.show');
    }

    public function test_capability_returns_null_when_ecommerce_route_is_not_available(): void
    {
        $product = $this->createProduct(['is_online' => true, 'slug' => 'route-missing-product']);
        $capability = \Mockery::mock(SocialCommerceStorefrontCapability::class)->makePartial();
        $capability->shouldReceive('isEcommerceAvailable')->andReturnTrue();

        Route::shouldReceive('has')->with('ecommerce.product.show')->andReturnFalse();

        $this->assertFalse($capability->canPurchaseOnline($product));
        $this->assertNull($capability->getEcommerceUrl($product));
    }

    public function test_unpublished_publication_returns_404(): void
    {
        $product = $this->createProduct();
        $this->publish($product, false);
        $this->get(route('socialcommerce.public.show', $product->id))->assertNotFound();
    }

    public function test_inactive_product_returns_404(): void
    {
        $product = $this->createProduct(['is_active' => false]);
        $this->publish($product);
        $this->get(route('socialcommerce.public.show', $product->id))->assertNotFound();
    }

    public function test_deleted_product_returns_404(): void
    {
        $product = $this->createProduct();
        $this->publish($product);
        $id = $product->id;
        $product->delete();
        $this->get('/social/' . $id)->assertNotFound();
    }

    public function test_invalid_and_non_numeric_identifiers_return_404(): void
    {
        $this->get('/social/999999999')->assertNotFound();
        $this->get('/social/reserved-word')->assertNotFound();
    }

    public function test_publication_first_lookup_does_not_expose_another_product(): void
    {
        $published = $this->createProduct(['name' => 'Published Corrective Product']);
        $other = $this->createProduct(['name' => 'Other Business Product']);
        $this->publish($published);
        $this->bindUnavailableCapability();

        $this->get(route('socialcommerce.public.show', $other->id))->assertNotFound();
        $this->get(route('socialcommerce.public.show', $published->id))
            ->assertOk()
            ->assertSee('Published Corrective Product')
            ->assertDontSee('Other Business Product');
    }

    public function test_duplicate_publication_is_rejected_by_the_database(): void
    {
        $product = $this->createProduct();
        $this->publish($product);

        $this->expectException(QueryException::class);
        $this->publish($product);
    }

    public function test_route_contract_is_numeric_and_owned_by_social_commerce(): void
    {
        $route = Route::getRoutes()->getByName('socialcommerce.public.show');
        $this->assertNotNull($route);
        $this->assertSame('social/{product}', $route->uri());
        $this->assertSame('Modules\\SocialCommerce\\Http\\Controllers\\PublicSocialController@show', $route->getActionName());
        $this->assertSame('[0-9]+', $route->wheres['product'] ?? null);

        $numeric = Route::getRoutes()->match(Request::create('/social/123', 'GET'));
        $this->assertSame('socialcommerce.public.show', $numeric->getName());

        $this->expectException(NotFoundHttpException::class);
        Route::getRoutes()->match(Request::create('/social/reserved-word', 'GET'));
    }

    public function test_share_and_qr_markup_reuse_the_canonical_url(): void
    {
        $product = $this->createProduct();
        $this->publish($product);
        $this->actingAs($this->createAuthorizedUser());
        $canonical = route('socialcommerce.public.show', $product->id);

        $response = $this->get(route('socialcommerce.catalog', ['filter' => 'published']));

        $response->assertOk();
        $response->assertSee($canonical, false);
        $response->assertSee(rawurlencode($canonical), false);
        $this->assertTrue(Route::has('socialcommerce.qr'));
        $this->get(route('socialcommerce.qr', $product->id))
            ->assertOk()
            ->assertHeader('content-type', 'image/svg+xml');
    }

    private function createProduct(array $overrides = []): Product
    {
        return Product::create(array_merge([
            'name' => 'Corrective Product ' . uniqid(),
            'code' => 'SC-' . strtoupper(uniqid()),
            'type' => 'standard',
            'slug' => 'corrective-product-' . uniqid(),
            'barcode_symbology' => 'C128',
            'unit_id' => $this->unit->id,
            'purchase_unit_id' => $this->unit->id,
            'sale_unit_id' => $this->unit->id,
            'category_id' => $this->category->id,
            'cost' => 10,
            'price' => 15,
            'qty' => 5,
            'image' => 'corrective-product.png',
            'product_details' => 'Corrective test details',
            'is_active' => true,
            'is_online' => false,
        ], $overrides));
    }

    private function publish(Product $product, bool $published = true): SocialProductSetting
    {
        return SocialProductSetting::create([
            'product_id' => $product->id,
            'is_published' => $published,
            'social_title' => $product->name,
            'social_description' => $product->product_details,
        ]);
    }

    private function createAuthorizedUser(): User
    {
        $permission = Permission::findOrCreate('manage social catalogue', 'web');
        $role = Role::create(['name' => 'social-corrective-' . uniqid(), 'guard_name' => 'web', 'is_active' => true]);
        $role->givePermissionTo($permission);

        $user = User::create([
            'name' => 'Social Corrective Admin',
            'email' => 'social-corrective-' . uniqid() . '@example.test',
            'password' => bcrypt('corrective-test-password'),
            'phone' => '1555' . random_int(1000000, 9999999),
            'role_id' => $role->id,
            'is_active' => true,
            'is_deleted' => false,
        ]);
        $user->assignRole($role);

        return $user;
    }

    private function bindUnavailableCapability(): void
    {
        $capability = \Mockery::mock(SocialCommerceStorefrontCapability::class);
        $capability->shouldReceive('canPurchaseOnline')->andReturnFalse();
        $capability->shouldReceive('getEcommerceUrl')->andReturnNull();
        $this->app->instance(SocialCommerceStorefrontCapability::class, $capability);
    }
}
