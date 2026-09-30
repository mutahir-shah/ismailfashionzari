<?php

namespace Modules\SocialCommerce\Services;

use App\Models\Product;
use Modules\SocialCommerce\Entities\SocialCommerceSetting;

class ProductLinkResolver
{
    /**
     * Resolve the best available public URL for a product.
     *
     * @param Product $product
     * @return string
     */
    public function resolve(Product $product): string
    {
        return route('socialcommerce.public.show', $product->id);
    }
}
