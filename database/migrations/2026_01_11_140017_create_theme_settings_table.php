<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('theme_settings', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // theme name
            $table->enum('theme_appearance', ['system_both', 'both', 'system', 'light', 'dark'])->default('system_both'); // system_both, both, system, light, dark
            $table->string('theme_color'); // primary color
            $table->string('font_family'); // font family
            $table->enum('icon_pack', ['solar', 'fontawesome', 'bootstrap', 'heroicons', 'material', 'cupertino'])->default('solar'); // solar, fontawesome, bootstrap, material
            $table->integer('item_size')->default(16); // 14, 16, 18
            $table->enum('border_radius', ['rounded-none', 'rounded', 'rounded-lg', 'rounded-full'])->default('rounded'); // rounded-none, rounded, rounded-lg, rounded-full

            $table->enum('input_design', ['outlined', 'filled'])->default('outlined'); // outlined, filled

            $table->json('chart_colors')->nullable(); // json array of colors for charts

            $table->enum('button_style', ['filled', 'outlined', 'gradient', 'glassed'])->default('filled'); // filled, outlined, gradient, glassed
            $table->json('button_colors')->nullable(); // json array of colors for button gradient style
            $table->json('button_dark_colors')->nullable(); // json array of colors for button gradient style in dark mode

            $table->enum('button_preferred_width', ['fit', 'full'])->default('fit'); // fit, full
            $table->integer('button_height')->default(52); // e.g. 52

            $table->enum('sidebar_style', ['floating', 'normal'])->default('normal'); // floating, normal
            $table->enum('sidebar_corner', ['rounded', 'none'])->default('rounded'); // rounded, none
            $table->string('sidebar_item_inactive_color')->nullable(); // sidebar item color
            $table->string('sidebar_item_active_color')->nullable(); // sidebar item color
            $table->string('sidebar_item_inactive_dark_color')->nullable(); // sidebar item dark_color
            $table->string('sidebar_item_active_dark_color')->nullable(); // sidebar item dark_color
            $table->string('sidebar_subitem_inactive_color')->nullable(); // sidebar item color
            $table->string('sidebar_subitem_active_color')->nullable(); // sidebar item color
            $table->string('sidebar_subitem_inactive_dark_color')->nullable(); // sidebar item dark_color
            $table->string('sidebar_subitem_active_dark_color')->nullable(); // sidebar item dark

            $table->enum('auth_background_type', ['black-and-white', 'themed', 'themed_gradient', 'image'])->default('themed_gradient'); // black-and-white, themed, themed_gradient, image

            $table->string('auth_themed_color')->nullable(); // for light mode and if auth_background_type is themed
            $table->string('auth_themed_dark_color')->nullable(); // for dark mode and if auth_background_type is themed

            $table->json('auth_themed_gradient')->nullable(); // for light mode and if auth_background_type is themed_gradient
            $table->json('auth_themed_dark_gradient')->nullable(); // for dark mode and if auth_background_type is themed_gradient
            $table->integer('auth_themed_deg')->nullable(); // for light mode and if auth_background_type is themed_gradient
            $table->integer('auth_themed_dark_deg')->nullable(); // for dark mode and if auth_background_type is themed_gradient

            $table->longText('auth_background_image')->nullable(); // for light mode and if auth_background_type is image
            $table->enum('auth_background_position', ['center', 'top', 'bottom', 'left', 'right', 'top-left', 'top-right', 'bottom-left', 'bottom-right'])->nullable(); // center, top, bottom, left, right, top-left, top-right, bottom-left, bottom-right
            $table->string('auth_background_opacity')->default('0.8'); // background image opacity
            $table->longText('auth_dark_background_image')->nullable(); // for dark mode and if auth_background_type is image
            $table->enum('auth_dark_background_position', ['center', 'top', 'bottom', 'left', 'right', 'top-left', 'top-right', 'bottom-left', 'bottom-right'])->nullable(); // background image opacity for dark mode
            $table->string('auth_dark_background_opacity')->default('0.8'); // background image opacity for dark mode

            $table->enum('site_background', ['solid', 'themed', 'gradient'])->default('solid'); // solid, themed
            $table->string('site_themed_color')->nullable(); // used if site_background is themed
            $table->string('site_themed_dark_color')->nullable(); // used if site_background is themed
            $table->json('site_gradient')->nullable(); // used if site_background is gradient
            $table->json('site_dark_gradient')->nullable(); // used if site_background is gradient

            $table->enum('dash_background', ['solid', 'themed', 'image'])->default('solid'); // solid, themed, image

            $table->string('dash_themed_color')->nullable(); // used if dash_background is themed
            $table->string('dash_themed_dark_color')->nullable(); // used if dash_background is themed

            $table->longText('dash_background_image')->nullable(); // for light mode and if dash_background is image
            $table->longText('dash_background_opacity')->nullable(); // for light mode and if dash_background is image
            $table->enum('dash_background_position', ['center', 'top', 'bottom', 'left', 'right', 'top-left', 'top-right', 'bottom-left', 'bottom-right'])->nullable(); // center, top, bottom, left, right, top-left, top-right, bottom-left, bottom-right
            $table->longText('dash_dark_background_image')->nullable(); // for dark mode and if dash_background is image
            $table->longText('dash_dark_background_opacity')->nullable(); // for dark mode and if dash_background is image
            $table->enum('dash_dark_background_position', ['center', 'top', 'bottom', 'left', 'right', 'top-left', 'top-right', 'bottom-left', 'bottom-right'])->nullable(); // center, top, bottom, left, right, top-left, top-right, bottom-left, bottom-right

            $table->boolean('is_active')->default(true); // to mark the active theme
            $table->json('active_for'); // site, dash, app
            $table->enum('app_platform', ['android', 'ios', 'both'])->default('both'); // android, ios, both
            $table->boolean('is_deleted')->default(false); // soft delete
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('theme_settings');
    }
};
