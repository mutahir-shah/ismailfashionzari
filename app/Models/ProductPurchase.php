<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductPurchase extends Model
{
    protected $table = 'product_purchases';
    protected $fillable =[

        "purchase_id", "product_id", "product_batch_id", "variant_id", "imei_number", "qty", "recieved", "return_qty", "purchase_unit_id", "net_unit_cost", "net_unit_price", "net_unit_margin", "net_unit_margin_type", "discount", "tax_rate", "tax", "total"
    ];

    /**
     * Get the purchase that this product purchase belongs to
     */
    public function purchase()
    {
        return $this->belongsTo(Purchase::class, 'purchase_id');
    }

    /**
     * Get the product for this purchase
     */
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
