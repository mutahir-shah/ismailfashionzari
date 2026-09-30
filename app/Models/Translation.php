<?php

namespace App\Models;


use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Translation extends Model
{
    use HasFactory;
    
    protected $fillable = ['locale', 'group', 'key', 'value'];

    // Get Default Language (Cached)
    public static function getTrnaslactionsByLocale(string $locale)
    {
        return Cache::rememberForever("translations_{$locale}", function () use ($locale) {
            return self::where('locale', $locale)
                ->get()
                ->mapWithKeys(function ($item) {
                    return [$item->group . '.' . $item->key => $item->value];
                })
                ->toArray();
        });
    }

    public static function forgetCachedTranslations()
    {
        Cache::forget('translations_by_locale');
        Cache::forget('languages_list');
        Cache::forget('translations_en');
        if (\Illuminate\Support\Facades\Schema::hasTable('translations')) {
            $locales = self::query()->distinct()->pluck('locale')->all();
            foreach ($locales as $loc) {
                Cache::forget("translations_{$loc}");
            }
        }
        if (\Illuminate\Support\Facades\Schema::hasTable('languages')) {
            $locales = Language::pluck('language')->all();
            foreach ($locales as $loc) {
                Cache::forget("translations_{$loc}");
            }
        }
    }

    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class, 'locale');
    }
}
