<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use App\Helpers\PaletteGenerator;

class ThemeSetting extends Model
{
    use HasFactory;

    /**
     * Mass assignable attributes
     */
    protected $fillable = [
        'name',
        'theme_appearance',
        'theme_color',
        'font_family',
        'icon_pack',
        'light_logo',
        'dark_logo',
        'item_size',
        'border_radius',
        'input_design',
        'chart_colors',
        'button_style',
        'button_colors',
        'button_dark_colors',
        'button_preferred_width',
        'button_height',
        'sidebar_style',
        'sidebar_corner',
        'sidebar_item_inactive_color',
        'sidebar_item_active_color',
        'sidebar_item_inactive_dark_color',
        'sidebar_item_active_dark_color',
        'sidebar_subitem_inactive_color',
        'sidebar_subitem_active_color',
        'sidebar_subitem_inactive_dark_color',
        'sidebar_subitem_active_dark_color',
        'auth_background_type',
        'auth_themed_color',
        'auth_themed_dark_color',
        'auth_themed_gradient',
        'auth_themed_dark_gradient',
        'auth_themed_deg',
        'auth_themed_dark_deg',
        'auth_background_image',
        'auth_background_position',
        'auth_background_opacity',
        'auth_dark_background_image',
        'auth_dark_background_position',
        'auth_dark_background_opacity',
        'site_background',
        'site_themed_color',
        'site_themed_dark_color',
        'site_gradient',
        'site_dark_gradient',
        'dash_background',
        'dash_themed_color',
        'dash_themed_dark_color',
        'dash_background_image',
        'dash_background_opacity',
        'dash_background_position',
        'dash_dark_background_image',
        'dash_dark_background_opacity',
        'dash_dark_background_position',
        'is_active',
        'active_for',
        'app_platform',
        'is_deleted',
    ];

    /**
     * Attribute casting
     */
    protected $casts = [
        'is_active' => 'boolean',
        'is_deleted' => 'boolean',

        // JSON arrays
        'chart_colors' => 'array',
        'button_colors' => 'array',
        'button_dark_colors' => 'array',
        'auth_themed_gradient' => 'array',
        'auth_themed_dark_gradient' => 'array',
        'site_gradient' => 'array',
        'site_dark_gradient' => 'array',
        'active_for' => 'array',

        // Integers
        'item_size' => 'integer',
        'button_height' => 'integer',
        'auth_themed_deg' => 'integer',
        'auth_themed_dark_deg' => 'integer',
    ];

    /**
     * Append computed attributes
     */
    protected $appends = [
        'theme_colors',
    ];

    /**
     * Computed: Tailwind-inspired color palette
     */
    protected function themeColors(): Attribute
    {
        return Attribute::get(function () {
            if (empty($this->theme_color)) {
                return null;
            }

            return PaletteGenerator::generateModernPalette($this->theme_color);
        });
    }

    /**
     * Scope: Only active themes
     */
    public function scopeActive($query, ?string $platform = null)
    {
        $query->where('is_active', true)
            ->where('is_deleted', false);

        if (!empty($platform)) {
            $query->where(function ($q) use ($platform) {
                $q->whereNull('active_for')
                    ->orWhereJsonContains('active_for', $platform);
            });
        }

        return $query;
    }

    /**
     * Helper: Check if theme applies to context
     */
    public function appliesTo(string $context): bool
    {
        if (empty($this->active_for)) {
            return true;
        }

        return in_array($context, $this->active_for, true);
    }
}
