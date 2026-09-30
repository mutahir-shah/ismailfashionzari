<?php

namespace App\Models;

use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Language extends Model
{
    use HasFactory;
    
    protected $fillable = ['language', 'name', 'is_default'];

    // Get Default Language (Cached)
    public static function getDefaultLanguage()
    {
        return Cache::rememberForever('default_language', function () {
            return self::where('is_default', true)->first();
        });
    }

    public static function forgetCachedLanguage()
    {
        Cache::forget('default_language');
        Cache::forget('languages_list');
    }

    // Set Default Language (and update cache)
    public static function setDefaultLanguage($id)
    {
        self::query()->update(['is_default' => false]);

        $language = self::findOrFail($id);
        $language->is_default = true;
        $language->save();

        setcookie('language', $language->language, time() + (86400 * 365), '/');
        Cache::forever('default_language', $language);
        
        app()->setLocale($language->language);
        config(['app.locale' => $language->language]);

        Artisan::call('cache:clear');
        Artisan::call('config:clear');
    }

    public function translations()
    {
        return $this->hasMany(Translation::class, 'locale');
    }
}
