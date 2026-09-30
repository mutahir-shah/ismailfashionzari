<?php

namespace Modules\AIAssistant\Entities;

use Illuminate\Database\Eloquent\Model;

class AIProviderSetting extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ai_provider_settings';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tenant_id',
        'provider',
        'api_key',
        'base_url',
        'model',
        'settings',
        'is_enabled',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'api_key',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'api_key' => 'encrypted',
        'settings' => 'array',
        'is_enabled' => 'boolean',
    ];
}
