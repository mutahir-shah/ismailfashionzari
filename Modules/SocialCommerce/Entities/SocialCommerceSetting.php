<?php

namespace Modules\SocialCommerce\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\SocialCommerce\Database\factories\SocialCommerceSettingFactory;

class SocialCommerceSetting extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'is_active',
        'link_target',
        'allow_fallback',
        'whatsapp_number',
        'share_template',
        'wa_template_order_confirmation',
        'wa_template_payment_request',
        'wa_template_delivery_update',
        'feed_token',
        'feed_last_generated',
        'feed_pending_refresh',
        'feed_last_failed',
        'feed_latest_error'
    ];
    
    protected static function newFactory(): SocialCommerceSettingFactory
    {
        //return SocialCommerceSettingFactory::new();
    }
}
