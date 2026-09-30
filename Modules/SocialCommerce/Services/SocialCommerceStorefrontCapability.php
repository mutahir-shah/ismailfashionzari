<?php

namespace Modules\SocialCommerce\Services;

use App\Models\Product;
use Illuminate\Support\Facades\Route;
use Nwidart\Modules\Facades\Module;

class SocialCommerceStorefrontCapability
{
    /**
     * Determine if Ecommerce is physically available and enabled.
     */
    public function isEcommerceAvailable(): bool
    {
        try {
            $module = Module::find('Ecommerce');
            return $module !== null && $module->isEnabled();
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Determine if a product can be purchased online via Ecommerce.
     */
    public function canPurchaseOnline(Product $product): bool
    {
        try {
            return $this->isEcommerceAvailable()
                && Route::has('ecommerce.product.show')
                && (bool) ($product->is_online ?? false)
                && filled($product->slug)
                && $product->getKey() !== null;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Get the optional Ecommerce product URL.
     */
    public function getEcommerceUrl(Product $product): ?string
    {
        try {
            if (!$this->canPurchaseOnline($product)) {
                return null;
            }

            return route('ecommerce.product.show', [
                'product_name' => $product->slug,
                'product_id' => $product->getKey(),
            ]);
        } catch (\Throwable $e) {
            return null;
        }
    }
}
