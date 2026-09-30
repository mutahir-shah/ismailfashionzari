<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationSetting extends Model
{
    protected $fillable = [
        'event', 
        'notify_in_app', 
        'notify_whatsapp', 
        'notify_sms', 
        'notify_mail', // 👈 Added
        'whatsapp_message', 
        'sms_message',       
        'mail_message', // 👈 Added
        'recipients'
    ];

    protected $casts = [
        'notify_in_app'   => 'boolean',
        'notify_whatsapp' => 'boolean',
        'notify_sms'      => 'boolean',
        'notify_mail'     => 'boolean',
        'recipients'      => 'array', // 👈 VERY IMPORTANT: Casts JSON string to array automatically
    ];

    public static function forEvent(string $event): ?self
    {
        return static::where('event', $event)->first();
    }
}
