@php
    $bgPositions = [
        '' => '—',
        'center' => 'Center',
        'top' => 'Top',
        'bottom' => 'Bottom',
        'left' => 'Left',
        'right' => 'Right',
        'top-left' => 'Top Left',
        'top-right' => 'Top Right',
        'bottom-left' => 'Bottom Left',
        'bottom-right' => 'Bottom Right',
    ];
@endphp

<div class="card mb-3" id="theme_preset_preview_card">
    <div class="card-header d-flex align-items-center justify-content-between">
        <h5 class="mb-0">Theme Preset Preview</h5>
        <small class="text-muted">Live preview</small>
    </div>
    <div class="card-body">
        <style>
            #theme_preset_preview {
                position: relative;
                width: 100%;
                height: auto;
                aspect-ratio: 520 / 180;
                min-height: 190px;
                max-height: 320px;
                overflow: hidden;
                border: 3px solid rgba(255, 255, 255, 0.85);
                box-shadow: 0 10px 24px rgba(0, 0, 0, 0.15);
                transition: border-color 250ms ease, border-width 250ms ease, border-radius 250ms ease, background 250ms ease;

                /* Base spacing (gets overridden from JS using item_size) */
                --tpp-s: 16px;
            }

            #theme_preset_preview .tpp-overlay {
                position: absolute;
                inset: 0;
                background: linear-gradient(180deg, rgba(0,0,0,0.05), rgba(0,0,0,0.25));
                z-index: 1;
            }

            #theme_preset_preview .tpp-appearance {
                position: absolute;
                left: 50%;
                top: 56%;
                transform: translate(-50%, 0);
                display: flex;
                align-items: center;
                justify-content: center;
                gap: calc(var(--tpp-s) * 0.7);
                z-index: 3;
                pointer-events: none;
            }

            #theme_preset_preview .tpp-appearance .tpp-appearance-icon {
                width: calc(var(--tpp-s) * 2.6);
                height: calc(var(--tpp-s) * 2.6);
                border-radius: calc(var(--tpp-s) * 1.1);
                border: 2px solid rgba(255,255,255,0.55);
                background: rgba(255,255,255,0.12);
                display: inline-flex;
                align-items: center;
                justify-content: center;
                box-shadow: 0 10px 18px rgba(0,0,0,0.22);
                backdrop-filter: blur(2px);
            }

            #theme_preset_preview .tpp-appearance .tpp-appearance-icon .iconify {
                font-size: calc(var(--tpp-s) * 1.85);
                color: rgba(255,255,255,0.95);
            }

            #theme_preset_preview .tpp-mini-input {
                position: absolute;
                left: calc(var(--tpp-s) * 1);
                top: calc(var(--tpp-s) * 1);
                width: calc(var(--tpp-s) * 7);
                height: calc(var(--tpp-s) * 1.4);
                border-radius: 10px;
                background: rgba(255,255,255,0.18);
                z-index: 3;
            }

            #theme_preset_preview .tpp-icons {
                position: absolute;
                left: 50%;
                top: 44%;
                transform: translate(-50%, -50%);
                display: flex;
                align-items: center;
                gap: calc(var(--tpp-s) * 0.6);
                color: rgba(255,255,255,0.95);
                font-weight: 700;
                font-size: calc(var(--tpp-s) * 0.8);
                user-select: none;
                z-index: 3;
                pointer-events: none;
            }

            #theme_preset_preview .tpp-icons .tpp-icon {
                width: calc(var(--tpp-s) * 3.15);
                height: calc(var(--tpp-s) * 3.15);
                border-radius: calc(var(--tpp-s) * 1.15);
                border: 2px solid rgba(255,255,255,0.58);
                background: rgba(255,255,255,0.10);
                box-shadow: 0 14px 20px rgba(0,0,0,0.22);
                display: flex;
                align-items: center;
                justify-content: center;
                line-height: 1;
            }

            #theme_preset_preview .tpp-icons .tpp-icon .iconify {
                font-size: calc(var(--tpp-s) * 2.05);
            }

            #theme_preset_preview .tpp-icons .tpp-selected-dot {
                width: calc(var(--tpp-s) * 1.15);
                height: calc(var(--tpp-s) * 1.15);
                border-radius: 999px;
                background: rgba(255,255,255,0.95);
                box-shadow: 0 6px 10px rgba(0,0,0,0.25);
                margin-left: calc(var(--tpp-s) * 0.25);
            }

            #theme_preset_preview .tpp-title {
                position: absolute;
                left: calc(var(--tpp-s) * 1);
                bottom: calc(var(--tpp-s) * 1);
                right: calc(var(--tpp-s) * 8);
                color: #fff;
                z-index: 3;
            }

            #theme_preset_preview .tpp-initials {
                font-size: calc(var(--tpp-s) * 2.65);
                font-weight: 800;
                line-height: 1;
                letter-spacing: -0.5px;
            }

            #theme_preset_preview .tpp-initials .tpp-second {
                font-size: calc(var(--tpp-s) * 2.35);
                font-weight: 800;
            }

            #theme_preset_preview .tpp-font {
                margin-top: calc(var(--tpp-s) * 0.35);
                font-size: calc(var(--tpp-s) * 0.95);
                font-weight: 700;
                opacity: 0.92;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }

            #theme_preset_preview .tpp-badge {
                position: absolute;
                right: -26px;
                bottom: -14px;
                width: calc(var(--tpp-s) * 9.6);
                height: calc(var(--tpp-s) * 3.25);
                padding: calc(var(--tpp-s) * 0.65) calc(var(--tpp-s) * 0.9);
                border-radius: 14px;
                box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
                display: flex;
                align-items: center;
                justify-content: flex-start;
                gap: calc(var(--tpp-s) * 0.45);
                color: rgba(255,255,255,0.95);
                font-weight: 800;
                font-size: calc(var(--tpp-s) * 0.78);
                z-index: 3;
                display: none;
            }

            #theme_preset_preview .tpp-badge .tpp-chip {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                width: calc(var(--tpp-s) * 1.35);
                height: calc(var(--tpp-s) * 1.35);
                border-radius: 999px;
                border: 1px solid rgba(255,255,255,0.65);
                background: rgba(255,255,255,0.08);
                line-height: 1;
            }

            #theme_preset_preview .tpp-badge .tpp-chip .iconify {
                font-size: calc(var(--tpp-s) * 1.05);
                color: rgba(255,255,255,0.95);
            }
        </style>

        <div class="row">
            <div class="col-md-12">
                <div id="theme_preset_preview" class="w-100">
                    <div class="tpp-overlay"></div>
                    <div class="tpp-mini-input" id="theme_preset_preview_mini_input"></div>
                    <div class="tpp-icons" id="theme_preset_preview_icons">
                        <div class="tpp-icon" title="Home">
                            <span class="iconify" data-theme-preset-icon data-icon="solar:home-2-bold"></span>
                        </div>
                        <div class="tpp-icon" title="Settings">
                            <span class="iconify" data-theme-preset-icon data-icon="solar:settings-bold"></span>
                        </div>
                        <div class="tpp-icon" title="People">
                            <span class="iconify" data-theme-preset-icon data-icon="solar:users-group-rounded-bold"></span>
                        </div>
                    </div>
                    <div class="tpp-appearance" id="theme_preset_preview_appearance"></div>
                    <div class="tpp-title">
                        <div class="tpp-initials" id="theme_preset_preview_initials">Aa</div>
                        <div class="tpp-font" id="theme_preset_preview_font">Font</div>
                    </div>
                    <div class="tpp-badge" id="theme_preset_preview_badge"></div>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="card mb-3">
    <div class="card-header d-flex align-items-center justify-content-between">
        <h5 class="mb-0">Base Settings</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-4">
                <div class="form-group">
                    <label>Theme Name *</label>
                    <input type="text" name="name" value="{{ old('name', $theme->name) }}" class="form-control" placeholder="Theme name" required>
                </div>
            </div>

            <div class="col-md-4">
                <div class="form-group">
                    <label>Theme Appearance *</label>
                    <select name="theme_appearance" class="selectpicker form-control" required>
                        @php
                            $options = [
                                'system_both' => 'Both (Light/Dark with System Default)',
                                'both'        => 'Both (Light/Dark without System Default)',
                                'system'      => 'System Default',
                                'light'       => 'Light Only',
                                'dark'        => 'Dark Only',
                            ];
                            $currentValue = old('theme_appearance', $theme->theme_appearance);
                        @endphp

                        @foreach($options as $value => $label)
                            <option value="{{ $value }}" @selected($currentValue == $value)>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="col-md-4">
                <div class="form-group">
                    <label>Font Family * <x-info title="Choose a Correct Google Font with Proper Spelling. Failing to do so or spelling mistake may result in unexpected font rendering." type="info" /></label>
                    <input type="text" name="font_family" value="{{ old('font_family', $theme->font_family) }}" class="form-control" placeholder="Inter" id="font_family_input" required>
                    <small class="text-muted">Preview auto-loads from Google Fonts.</small>
                </div>
            </div>

            <div class="col-md-4">
                <div class="form-group">
                    <label>Icon Pack *</label>
                    <select name="icon_pack" class="selectpicker form-control" required>
                        <option value="">—</option>
                        <option value="solar" {{ old('icon_pack', $theme->icon_pack) == 'solar' ? 'selected' : '' }}>Solar</option>
                        <option value="fontawesome" {{ old('icon_pack', $theme->icon_pack) == 'fontawesome' ? 'selected' : '' }}>FontAwesome</option>
                        <option value="bootstrap" {{ old('icon_pack', $theme->icon_pack) == 'bootstrap' ? 'selected' : '' }}>Bootstrap</option>
                        <option value="heroicons" {{ old('icon_pack', $theme->icon_pack) == 'heroicons' ? 'selected' : '' }}>Heroicons</option>
                        <option value="material" {{ old('icon_pack', $theme->icon_pack) == 'material' ? 'selected' : '' }}>Material</option>
                        <option value="cupertino" {{ old('icon_pack', $theme->icon_pack) == 'cupertino' ? 'selected' : '' }}>Cupertino</option>
                    </select>
                </div>
            </div>

            <div class="col-md-2">
                <div class="form-group">
                    <label>Item Size * <x-info title="This will define how the system design will scale. If you want to make the system look bigger or smaller, use this option." type="info" /></label>
                    <select name="item_size" class="selectpicker form-control" required>
                        <option value="">—</option>
                        <option value="20" {{ old('item_size', $theme->item_size) == '20' ? 'selected' : '' }}>Extra Large</option>
                        <option value="18" {{ old('item_size', $theme->item_size) == '18' ? 'selected' : '' }}>Large</option>
                        <option value="16" {{ old('item_size', $theme->item_size) == '16' ? 'selected' : '' }}>Normal</option>
                        <option value="14" {{ old('item_size', $theme->item_size) == '14' ? 'selected' : '' }}>Small</option>
                        <option value="12" {{ old('item_size', $theme->item_size) == '12' ? 'selected' : '' }}>Extra Small</option>
                    </select>
                </div>
            </div>

            <div class="col-md-3">
                <div class="form-group">
                    <label>Border Radius * <x-info title="Choose the overall border radius for the system design." type="info" /></label>
                    <select name="border_radius" class="selectpicker form-control" required>
                        <option value="">—</option>
                        <option value="rounded-none" {{ old('border_radius', $theme->border_radius) == 'rounded-none' ? 'selected' : '' }}>None</option>
                        <option value="rounded" {{ old('border_radius', $theme->border_radius) == 'rounded' ? 'selected' : '' }}>Rounded</option>
                        <option value="rounded-lg" {{ old('border_radius', $theme->border_radius) == 'rounded-lg' ? 'selected' : '' }}>Rounded Large</option>
                        <option value="rounded-full" {{ old('border_radius', $theme->border_radius) == 'rounded-full' ? 'selected' : '' }}>Rounded Full</option>
                    </select>
                </div>
            </div>

            <div class="col-md-3">
                </div>
            </div>

            <div class="col-md-3">
                <div class="form-group">
                    <label>Input Design *</label>
                    <select name="input_design" class="selectpicker form-control" required>
                        <option value="">—</option>
                        <option value="outlined" {{ old('input_design', $theme->input_design) == 'outlined' ? 'selected' : '' }}>Outlined</option>
                        <option value="filled" {{ old('input_design', $theme->input_design) == 'filled' ? 'selected' : '' }}>Filled</option>
                    </select>
                </div>
            </div>

            <div class="col-md-4">
                <div class="form-group">
                    <label>Themed Logo (Light)</label>
                    @php($themedLogoLight = old('light_logo', $theme->light_logo))
                    @php($themedLogoLightRemove = (bool) old('light_logo_remove', false))
                    @php($themedLogoLightUrl = $themedLogoLight ? (\Illuminate\Support\Str::startsWith($themedLogoLight, ['http://', 'https://', '/']) ? $themedLogoLight : url($themedLogoLight)) : null)

                    <input type="file" name="light_logo" class="form-control-file theme-image-input" accept="image/*" data-preview-target="light_logo_preview" />
                    <div class="mt-2">
                        <img
                            id="light_logo_preview"
                            src="{{ (!$themedLogoLightRemove && $themedLogoLightUrl) ? $themedLogoLightUrl : '' }}"
                            data-original-src="{{ $themedLogoLightUrl ?? '' }}"
                            style="width: 100%; height: 120px; object-fit: cover; border-radius: 10px; {{ (!$themedLogoLightRemove && $themedLogoLightUrl) ? '' : 'display:none;' }}"
                            alt="Themed logo (light) preview"
                        />
                    </div>
                    <div class="mt-2">
                        <label class="checkbox-inline">
                            <input type="checkbox" name="light_logo_remove" value="1" {{ $themedLogoLightRemove ? 'checked' : '' }} class="theme-image-remove" data-preview-target="light_logo_preview" data-input-name="light_logo" /> Remove old one (if exist)
                        </label>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label>Themed Logo (Dark)</label>
                    @php($themedLogoDark = old('dark_logo', $theme->dark_logo))
                    @php($themedLogoDarkRemove = (bool) old('dark_logo_remove', false))
                    @php($themedLogoDarkUrl = $themedLogoDark ? (\Illuminate\Support\Str::startsWith($themedLogoDark, ['http://', 'https://', '/']) ? $themedLogoDark : url($themedLogoDark)) : null)

                    <input type="file" name="dark_logo" class="form-control-file theme-image-input" accept="image/*" data-preview-target="dark_logo_preview" />
                    <div class="mt-2">
                        <img
                            id="dark_logo_preview"
                            src="{{ (!$themedLogoDarkRemove && $themedLogoDarkUrl) ? $themedLogoDarkUrl : '' }}"
                            data-original-src="{{ $themedLogoDarkUrl ?? '' }}"
                            style="width: 100%; height: 120px; object-fit: cover; border-radius: 10px; {{ (!$themedLogoDarkRemove && $themedLogoDarkUrl) ? '' : 'display:none;' }}"
                            alt="Themed logo (dark) preview"
                        />
                    </div>
                    <div class="mt-2">
                        <label class="checkbox-inline">
                            <input type="checkbox" name="dark_logo_remove" value="1" {{ $themedLogoDarkRemove ? 'checked' : '' }} class="theme-image-remove" data-preview-target="dark_logo_preview" data-input-name="dark_logo" /> Remove old one (if exist)
                        </label>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header d-flex align-items-center justify-content-between">
        <h5 class="mb-0">Primary Color</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-4">
                <div class="form-group mb-0">
                    <label>Primary Color *</label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text" style="padding:0;">
                                <input type="color" class="hex-color-picker" id="theme_color_picker" value="{{ old('theme_color', $theme->theme_color ?? '#6366F1') }}" data-target-input="theme_color" style="width: 42px; height: 38px; border: none;" />
                            </span>
                        </div>
                        <input type="text" name="theme_color" value="{{ old('theme_color', $theme->theme_color) }}" required class="form-control hex-color-input" placeholder="#6366F1" data-picker-id="theme_color_picker" />
                    </div>
                    <small class="text-muted">Use HEX like <code>#6366F1</code></small>
                </div>
            </div>

            <div class="col-md-8">
                @php($initialPalette = $theme->theme_colors ?? [])
                <div class="mt-2 mt-md-0" id="primary_palette"
                     data-palette-endpoint="{{ route($routePrefix . 'themeSettings.palette') }}"
                     data-initial-palette='@json($initialPalette)'>
                    <div class="d-flex flex-wrap" id="primary_palette_swatches" style="gap: 8px;"></div>
                    <div class="d-flex align-items-center justify-content-between mt-2">
                        <small class="text-muted">Primary Color palette (generated). Click a swatch to copy.</small>
                        <label class="mb-0" style="user-select:none;">
                            <input type="checkbox" id="auto_apply_palette" checked>
                            <small>Auto-apply defaults</small>
                        </label>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card mb-3" id="button_settings">
    <div class="card-header d-flex align-items-center justify-content-between">
        <h5 class="mb-0">Button Settings</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-3">
                <div class="form-group">
                    <label>Button Height *</label>
                    <input type="number" name="button_height" value="{{ old('button_height', $theme->button_height) }}" required class="form-control" min="30" max="120" />
                </div>
            </div>

            <div class="col-md-4">
                <div class="form-group">
                    <label>Button Style *</label>
                    <select name="button_style" class="selectpicker form-control" required>
                        <option value="filled" {{ old('button_style', $theme->button_style) == 'filled' ? 'selected' : '' }}>Filled</option>
                        <option value="outlined" {{ old('button_style', $theme->button_style) == 'outlined' ? 'selected' : '' }}>Outlined</option>
                        <option value="gradient" {{ old('button_style', $theme->button_style) == 'gradient' ? 'selected' : '' }}>Gradient</option>
                        <option value="glassed" {{ old('button_style', $theme->button_style) == 'glassed' ? 'selected' : '' }}>Glassed</option>
                    </select>
                </div>
            </div>

            <div class="col-md-4">
                <div class="form-group">
                    <label>Button Preferred Width *</label>
                </div>
            </div>

            <div class="col-md-4">
                <div class="form-group">
                    <label>Button Preferred Width *</label>
                    <select name="button_preferred_width" class="selectpicker form-control" required>
                        <option value="fit" {{ old('button_preferred_width', $theme->button_preferred_width) == 'fit' ? 'selected' : '' }}>Fit</option>
                        <option value="full" {{ old('button_preferred_width', $theme->button_preferred_width) == 'full' ? 'selected' : '' }}>Full</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="row" id="button_colors_section">
            @php($buttonColorsOld = old('button_colors_json'))
            @php($buttonColorsDecoded = is_string($buttonColorsOld) ? json_decode($buttonColorsOld, true) : null)
            @php($buttonColors = is_array($buttonColorsDecoded) ? $buttonColorsDecoded : ($theme->button_colors ?? []))

            @php($buttonDarkColorsOld = old('button_dark_colors_json'))
            @php($buttonDarkColorsDecoded = is_string($buttonDarkColorsOld) ? json_decode($buttonDarkColorsOld, true) : null)
            @php($buttonDarkColors = is_array($buttonDarkColorsDecoded) ? $buttonDarkColorsDecoded : ($theme->button_dark_colors ?? []))

            <div class="col-md-6">
                <div class="form-group color-array-group" id="button_colors_group"
                     data-field="button_colors_json"
                     data-initial='@json($buttonColors)'
                       data-default='["#6366F1"]'
                       data-default-gradient='["#818CF8","#4338CA"]'
                >
                    <label>Button Colors</label>
                    <div class="d-flex align-items-center justify-content-between">
                        <small class="text-muted" id="button_colors_help">Used by the selected button style.</small>
                        <button type="button" class="btn btn-sm btn-info color-array-add">+ Add</button>
                    </div>
                    <div class="color-array-items mt-2"></div>
                    <textarea  name="button_colors_json" class="form-control d-none color-array-hidden" rows="2">{{ old('button_colors_json', $theme->button_colors ? json_encode($theme->button_colors) : '') }}</textarea>
                </div>
            </div>

            <div class="col-md-6">
                <div class="form-group color-array-group" id="button_dark_colors_group"
                     data-field="button_dark_colors_json"
                     data-initial='@json($buttonDarkColors)'
                       data-default='["#0F172A"]'
                       data-default-gradient='["#1E293B","#0F172A"]'
                >
                    <label>Button Dark Colors</label>
                    <div class="d-flex align-items-center justify-content-between">
                        <small class="text-muted">Optional dark-mode palette.</small>
                        <button type="button" class="btn btn-sm btn-info color-array-add">+ Add</button>
                    </div>
                    <div class="color-array-items mt-2"></div>
                    <textarea 
                        name="button_dark_colors_json" 
                        class="form-control d-none color-array-hidden" 
                        rows="2"
                    >{{ old('button_dark_colors_json', $theme->button_dark_colors ? json_encode($theme->button_dark_colors) : '') }}</textarea>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card mb-3" id="sidebar_settings">
    <div class="card-header d-flex align-items-center justify-content-between">
        <h5 class="mb-0">Sidebar Settings</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-4">
                <div class="form-group">
                    <label>Sidebar Style *</label>
                    <select name="sidebar_style" class="selectpicker form-control" required>
                        <option value="">Select Sidebar Style</option>
                        <option value="normal" {{ old('sidebar_style', $theme->sidebar_style) == 'normal' ? 'selected' : '' }}>Normal</option>
                        <option value="floating" {{ old('sidebar_style', $theme->sidebar_style) == 'floating' ? 'selected' : '' }}>Floating</option>
                    </select>
                </div>
            </div>

            <div class="col-md-4">
                <div class="form-group">
                    <label>Sidebar Corner *</label>
                    <select name="sidebar_corner" class="selectpicker form-control" required>
                        <option value="">Select Sidebar Corner</option>
                        <option value="rounded" {{ old('sidebar_corner', $theme->sidebar_corner) == 'rounded' ? 'selected' : '' }}>Rounded</option>
                        <option value="none" {{ old('sidebar_corner', $theme->sidebar_corner) == 'none' ? 'selected' : '' }}>None</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <h6 class="mt-2 mb-0">Sidebar Colors</h6>
                <small class="text-muted">Optional. Leave empty to use defaults.</small>
            </div>

            <?php
                $sidebarHexFields = [
                    ['name' => 'sidebar_item_inactive_color', 'label' => 'Item Inactive', 'placeholder' => '#94A3B8'],
                    ['name' => 'sidebar_item_active_color', 'label' => 'Item Active', 'placeholder' => '#6366F1'],
                    ['name' => 'sidebar_item_inactive_dark_color', 'label' => 'Item Inactive (Dark)', 'placeholder' => '#94A3B8'],
                    ['name' => 'sidebar_item_active_dark_color', 'label' => 'Item Active (Dark)', 'placeholder' => '#6366F1'],
                    ['name' => 'sidebar_subitem_inactive_color', 'label' => 'Subitem Inactive', 'placeholder' => '#94A3B8'],
                    ['name' => 'sidebar_subitem_active_color', 'label' => 'Subitem Active', 'placeholder' => '#6366F1'],
                    ['name' => 'sidebar_subitem_inactive_dark_color', 'label' => 'Subitem Inactive (Dark)', 'placeholder' => '#94A3B8'],
                    ['name' => 'sidebar_subitem_active_dark_color', 'label' => 'Subitem Active (Dark)', 'placeholder' => '#6366F1'],
                ];
            ?>
            @foreach($sidebarHexFields as $hex)
                @php($fieldName = $hex['name'])
                @php($pickerId = $fieldName . '_picker')
                @php($current = old($fieldName, $theme->{$fieldName} ?? ''))
                <div class="col-md-3">
                    <div class="form-group">
                        <label>{{ $hex['label'] }}</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text" style="padding:0;">
                                    <input type="color" class="hex-color-picker" id="{{ $pickerId }}" value="{{ $current ?: ($hex['placeholder'] ?? '#000000') }}" data-target-input="{{ $fieldName }}" style="width: 42px; height: 38px; border: none;" />
                                </span>
                            </div>
                            <input type="text" name="{{ $fieldName }}" value="{{ $current }}" class="form-control hex-color-input" placeholder="{{ $hex['placeholder'] }}" data-picker-id="{{ $pickerId }}" />
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>

<div class="card mb-3" id="chart_settings">
    <div class="card-header d-flex align-items-center justify-content-between">
        <h5 class="mb-0">Chart Settings</h5>
    </div>
    <div class="card-body">
        <div class="row">
    @php($chartColorsOld = old('chart_colors_json'))
    @php($chartColorsDecoded = is_string($chartColorsOld) ? json_decode($chartColorsOld, true) : null)
    @php($chartColors = is_array($chartColorsDecoded) ? $chartColorsDecoded : ($theme->chart_colors ?? []))

    <div class="col-md-12">
         <div class="form-group color-array-group" id="chart_colors_group"
             data-field="chart_colors_json"
             data-initial='@json($chartColors)'
               data-default='["#10B981","#25b8e9","#ffa424","#a542f5","#2af898","#f5ca2e"]'
        >
            <label>Chart Colors</label>
            <div class="d-flex align-items-center justify-content-between">
                <small class="text-muted">Click + Add to add more colors.</small>
                <button type="button" class="btn btn-sm btn-info color-array-add">+ Add</button>
            </div>
            <div class="color-array-items mt-2"></div>
            <textarea name="chart_colors_json" class="form-control d-none color-array-hidden" rows="2">{{ old('chart_colors_json', $theme->chart_colors ? json_encode($theme->chart_colors) : '') }}</textarea>
        </div>
    </div>
        </div>
    </div>
</div>

<div class="card mb-3" id="auth_background_settings">
    <div class="card-header d-flex align-items-center justify-content-between">
        <h5 class="mb-0">Auth Background</h5>
    </div>
    <div class="card-body">
        <div class="row">
    <div class="col-md-4">
        <div class="form-group">
            <label>Type *</label>
            <select name="auth_background_type" class="selectpicker form-control" required>
                <option value="">Select Background Type</option>
                <option value="black-and-white" {{ old('auth_background_type', $theme->auth_background_type) == 'black-and-white' ? 'selected' : '' }}>Black & White</option>
                <option value="themed" {{ old('auth_background_type', $theme->auth_background_type) == 'themed' ? 'selected' : '' }}>Themed</option>
                <option value="themed_gradient" {{ old('auth_background_type', $theme->auth_background_type) == 'themed_gradient' ? 'selected' : '' }}>Themed Gradient</option>
                <option value="image" {{ old('auth_background_type', $theme->auth_background_type) == 'image' ? 'selected' : '' }}>Image</option>
            </select>
        </div>
    </div>

    <div class="col-md-8" id="auth_themed_section">
        <div class="row">
            @php($authThemedColor = old('auth_themed_color', $theme->auth_themed_color ?? ''))
            @php($authThemedDarkColor = old('auth_themed_dark_color', $theme->auth_themed_dark_color ?? ''))

            <div class="col-md-6">
                <div class="form-group">
                    <label>Themed Color</label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text" style="padding:0;">
                                <input type="color" class="hex-color-picker" id="auth_themed_color_picker" value="{{ $authThemedColor ?: '#F8FAFC' }}" data-target-input="auth_themed_color" style="width: 42px; height: 38px; border: none;" />
                            </span>
                        </div>
                        <input type="text" name="auth_themed_color" value="{{ $authThemedColor }}" class="form-control hex-color-input" placeholder="#F8FAFC" data-picker-id="auth_themed_color_picker" />
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="form-group">
                    <label>Themed Dark Color</label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text" style="padding:0;">
                                <input type="color" class="hex-color-picker" id="auth_themed_dark_color_picker" value="{{ $authThemedDarkColor ?: '#0F172A' }}" data-target-input="auth_themed_dark_color" style="width: 42px; height: 38px; border: none;" />
                            </span>
                        </div>
                        <input type="text" name="auth_themed_dark_color" value="{{ $authThemedDarkColor }}" class="form-control hex-color-input" placeholder="#0F172A" data-picker-id="auth_themed_dark_color_picker" />
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-12" id="auth_gradient_section">
        <div class="row">
            @php($authGradientOld = old('auth_themed_gradient_json'))
            @php($authGradientDecoded = is_string($authGradientOld) ? json_decode($authGradientOld, true) : null)
            @php($authGradient = is_array($authGradientDecoded) ? $authGradientDecoded : ($theme->auth_themed_gradient ?? []))

            @php($authDarkGradientOld = old('auth_themed_dark_gradient_json'))
            @php($authDarkGradientDecoded = is_string($authDarkGradientOld) ? json_decode($authDarkGradientOld, true) : null)
            @php($authDarkGradient = is_array($authDarkGradientDecoded) ? $authDarkGradientDecoded : ($theme->auth_themed_dark_gradient ?? []))

            <div class="col-md-6">
                <div class="form-group color-array-group"
                     data-field="auth_themed_gradient_json"
                     data-initial='@json($authGradient)'
                     data-default='["#EEF2FF","#DBEAFE"]'
                >
                    <label>Themed Gradient</label>
                    <div class="d-flex align-items-center justify-content-between">
                        <small class="text-muted">Pick 2+ colors.</small>
                        <button type="button" class="btn btn-sm btn-info color-array-add">+ Add</button>
                    </div>
                    <div class="color-array-items mt-2"></div>
                    <textarea name="auth_themed_gradient_json" class="form-control d-none color-array-hidden" rows="2">{{ old('auth_themed_gradient_json', $theme->auth_themed_gradient ? json_encode($theme->auth_themed_gradient) : '') }}</textarea>
                </div>
            </div>

            <div class="col-md-6">
                <div class="form-group color-array-group"
                     data-field="auth_themed_dark_gradient_json"
                     data-initial='@json($authDarkGradient)'
                     data-default='["#0F172A","#312E81"]'
                >
                    <label>Themed Dark Gradient</label>
                    <div class="d-flex align-items-center justify-content-between">
                        <small class="text-muted">Pick 2+ colors.</small>
                        <button type="button" class="btn btn-sm btn-info color-array-add">+ Add</button>
                    </div>
                    <div class="color-array-items mt-2"></div>
                    <textarea name="auth_themed_dark_gradient_json" class="form-control d-none color-array-hidden" rows="2">{{ old('auth_themed_dark_gradient_json', $theme->auth_themed_dark_gradient ? json_encode($theme->auth_themed_dark_gradient) : '') }}</textarea>
                </div>
            </div>

            <div class="col-md-2">
                <div class="form-group">
                    <label>Deg</label>
                    <input type="number" name="auth_themed_deg" value="{{ old('auth_themed_deg', $theme->auth_themed_deg) }}" class="form-control" min="0" max="360" />
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label>Dark Deg</label>
                    <input type="number" name="auth_themed_dark_deg" value="{{ old('auth_themed_dark_deg', $theme->auth_themed_dark_deg) }}" class="form-control" min="0" max="360" />
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-12" id="auth_image_section">
        <div class="row">
            <div class="col-md-4">
                <div class="form-group">
                    <label>Image (Light)</label>
                    @php($authBg = old('auth_background_image', $theme->auth_background_image))
                    @php($authBgRemove = (bool) old('auth_background_image_remove', false))
                    @php($authBgUrl = $authBg ? (\Illuminate\Support\Str::startsWith($authBg, ['http://', 'https://', '/']) ? $authBg : url($authBg)) : null)

                    <input type="file" name="auth_background_image" class="form-control-file theme-image-input" accept="image/*" data-preview-target="auth_background_image_preview" />
                    <div class="mt-2">
                        <img
                            id="auth_background_image_preview"
                            src="{{ (!$authBgRemove && $authBgUrl) ? $authBgUrl : '' }}"
                            data-original-src="{{ $authBgUrl ?? '' }}"
                            style="width: 100%; height: 120px; object-fit: cover; border-radius: 10px; {{ (!$authBgRemove && $authBgUrl) ? '' : 'display:none;' }}"
                            alt="Auth background (light) preview"
                        />
                    </div>
                    <div class="mt-2">
                        <label class="checkbox-inline">
                            <input type="checkbox" name="auth_background_image_remove" value="1" {{ $authBgRemove ? 'checked' : '' }} class="theme-image-remove" data-preview-target="auth_background_image_preview" data-input-name="auth_background_image" /> Remove old one (if exist)
                        </label>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label>Image (Dark)</label>
                    @php($authBgDark = old('auth_dark_background_image', $theme->auth_dark_background_image))
                    @php($authBgDarkRemove = (bool) old('auth_dark_background_image_remove', false))
                    @php($authBgDarkUrl = $authBgDark ? (\Illuminate\Support\Str::startsWith($authBgDark, ['http://', 'https://', '/']) ? $authBgDark : url($authBgDark)) : null)

                    <input type="file" name="auth_dark_background_image" class="form-control-file theme-image-input" accept="image/*" data-preview-target="auth_dark_background_image_preview" />
                    <div class="mt-2">
                        <img
                            id="auth_dark_background_image_preview"
                            src="{{ (!$authBgDarkRemove && $authBgDarkUrl) ? $authBgDarkUrl : '' }}"
                            data-original-src="{{ $authBgDarkUrl ?? '' }}"
                            style="width: 100%; height: 120px; object-fit: cover; border-radius: 10px; {{ (!$authBgDarkRemove && $authBgDarkUrl) ? '' : 'display:none;' }}"
                            alt="Auth background (dark) preview"
                        />
                    </div>
                    <div class="mt-2">
                        <label class="checkbox-inline">
                            <input type="checkbox" name="auth_dark_background_image_remove" value="1" {{ $authBgDarkRemove ? 'checked' : '' }} class="theme-image-remove" data-preview-target="auth_dark_background_image_preview" data-input-name="auth_dark_background_image" /> Remove old one (if exist)
                        </label>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="form-group">
                    <label>Position (Light)</label>

                    <select name="auth_background_position" class="selectpicker form-control">
                        @foreach($bgPositions as $value => $label)
                            <option value="{{ $value }}" @selected(old('auth_background_position', $theme->auth_background_position) == $value)>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="col-md-4">
                <div class="form-group">
                    <label>Position (Dark)</label>
                    <select name="auth_dark_background_position" class="selectpicker form-control">
                        <option value="">Select Position</option>
                        <option value="center" {{ old('auth_dark_background_position', $theme->auth_dark_background_position) == 'center' ? 'selected' : '' }}>Center</option>
                        <option value="top" {{ old('auth_dark_background_position', $theme->auth_dark_background_position) == 'top' ? 'selected' : '' }}>Top</option>
                        <option value="bottom" {{ old('auth_dark_background_position', $theme->auth_dark_background_position) == 'bottom' ? 'selected' : '' }}>Bottom</option>
                        <option value="left" {{ old('auth_dark_background_position', $theme->auth_dark_background_position) == 'left' ? 'selected' : '' }}>Left</option>
                        <option value="right" {{ old('auth_dark_background_position', $theme->auth_dark_background_position) == 'right' ? 'selected' : '' }}>Right</option>
                        <option value="top-left" {{ old('auth_dark_background_position', $theme->auth_dark_background_position) == 'top-left' ? 'selected' : '' }}>Top Left</option>
                        <option value="top-right" {{ old('auth_dark_background_position', $theme->auth_dark_background_position) == 'top-right' ? 'selected' : '' }}>Top Right</option>
                        <option value="bottom-left" {{ old('auth_dark_background_position', $theme->auth_dark_background_position) == 'bottom-left' ? 'selected' : '' }}>Bottom Left</option>
                        <option value="bottom-right" {{ old('auth_dark_background_position', $theme->auth_dark_background_position) == 'bottom-right' ? 'selected' : '' }}>Bottom Right</option>
                    </select>
                </div>
            </div>

            <div class="col-md-2">
                <div class="form-group">
                    <label>Opacity (Light)</label>
                    <input type="text" name="auth_background_opacity" value="{{ old('auth_background_opacity', $theme->auth_background_opacity) }}" class="form-control" placeholder="0.8" />
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label>Opacity (Dark)</label>
                    <input type="text" name="auth_dark_background_opacity" value="{{ old('auth_dark_background_opacity', $theme->auth_dark_background_opacity) }}" class="form-control" placeholder="0.8" />
                </div>
            </div>
        </div>
    </div>
        </div>
    </div>
</div>

<div class="card mb-3" id="site_background_settings">
    <div class="card-header d-flex align-items-center justify-content-between">
        <h5 class="mb-0">Site Background</h5>
    </div>
    <div class="card-body">
        <div class="row">
    <div class="col-md-4">
        <div class="form-group">
            <label>Type *</label>
            <select name="site_background" class="selectpicker form-control" required>
                <option value="">Select Type</option>
                <option value="solid" {{ old('site_background', $theme->site_background) == 'solid' ? 'selected' : '' }}>Solid</option>
                <option value="themed" {{ old('site_background', $theme->site_background) == 'themed' ? 'selected' : '' }}>Themed</option>
                <option value="gradient" {{ old('site_background', $theme->site_background) == 'gradient' ? 'selected' : '' }}>Gradient</option>
            </select>
        </div>
    </div>

    <div class="col-md-8" id="site_themed_section">
        <div class="row">
            @php($siteThemedColor = old('site_themed_color', $theme->site_themed_color ?? ''))
            @php($siteThemedDarkColor = old('site_themed_dark_color', $theme->site_themed_dark_color ?? ''))

            <div class="col-md-6">
                <div class="form-group">
                    <label>Themed Color</label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text" style="padding:0;">
                                <input type="color" class="hex-color-picker" id="site_themed_color_picker" value="{{ $siteThemedColor ?: '#F8FAFC' }}" data-target-input="site_themed_color" style="width: 42px; height: 38px; border: none;" />
                            </span>
                        </div>
                        <input type="text" name="site_themed_color" value="{{ $siteThemedColor }}" class="form-control hex-color-input" placeholder="#F8FAFC" data-picker-id="site_themed_color_picker" />
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="form-group">
                    <label>Themed Dark Color</label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text" style="padding:0;">
                                <input type="color" class="hex-color-picker" id="site_themed_dark_color_picker" value="{{ $siteThemedDarkColor ?: '#0F172A' }}" data-target-input="site_themed_dark_color" style="width: 42px; height: 38px; border: none;" />
                            </span>
                        </div>
                        <input type="text" name="site_themed_dark_color" value="{{ $siteThemedDarkColor }}" class="form-control hex-color-input" placeholder="#0F172A" data-picker-id="site_themed_dark_color_picker" />
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-12" id="site_gradient_section">
        <div class="row">
            @php($siteGradientOld = old('site_gradient_json'))
            @php($siteGradientDecoded = is_string($siteGradientOld) ? json_decode($siteGradientOld, true) : null)
            @php($siteGradient = is_array($siteGradientDecoded) ? $siteGradientDecoded : ($theme->site_gradient ?? []))

            @php($siteDarkGradientOld = old('site_dark_gradient_json'))
            @php($siteDarkGradientDecoded = is_string($siteDarkGradientOld) ? json_decode($siteDarkGradientOld, true) : null)
            @php($siteDarkGradient = is_array($siteDarkGradientDecoded) ? $siteDarkGradientDecoded : ($theme->site_dark_gradient ?? []))

            <div class="col-md-6">
                <div class="form-group color-array-group"
                     data-field="site_gradient_json"
                     data-initial='@json($siteGradient)'
                     data-default='["#F8FAFC","#EEF2FF"]'
                >
                    <label>Gradient</label>
                    <div class="d-flex align-items-center justify-content-between">
                        <small class="text-muted">Pick 2+ colors.</small>
                        <button type="button" class="btn btn-sm btn-info color-array-add">+ Add</button>
                    </div>
                    <div class="color-array-items mt-2"></div>
                    <textarea name="site_gradient_json" class="form-control d-none color-array-hidden" rows="2">{{ old('site_gradient_json', $theme->site_gradient ? json_encode($theme->site_gradient) : '') }}</textarea>
                </div>
            </div>

            <div class="col-md-6">
                <div class="form-group color-array-group"
                     data-field="site_dark_gradient_json"
                     data-initial='@json($siteDarkGradient)'
                     data-default='["#0F172A","#020617"]'
                >
                    <label>Dark Gradient</label>
                    <div class="d-flex align-items-center justify-content-between">
                        <small class="text-muted">Pick 2+ colors.</small>
                        <button type="button" class="btn btn-sm btn-info color-array-add">+ Add</button>
                    </div>
                    <div class="color-array-items mt-2"></div>
                    <textarea name="site_dark_gradient_json" class="form-control d-none color-array-hidden" rows="2">{{ old('site_dark_gradient_json', $theme->site_dark_gradient ? json_encode($theme->site_dark_gradient) : '') }}</textarea>
                </div>
            </div>
        </div>
    </div>
        </div>
    </div>
</div>

<div class="card mb-3" id="dashboard_background_settings">
    <div class="card-header d-flex align-items-center justify-content-between">
        <h5 class="mb-0">Dashboard Background</h5>
    </div>
    <div class="card-body">
        <div class="row">
    <div class="col-md-4">
        <div class="form-group">
            <label>Type *</label>
            <select name="dash_background" class="selectpicker form-control" required>
                <option value="">Select Type</option>
                <option value="solid" {{ old('dash_background', $theme->dash_background) == 'solid' ? 'selected' : '' }}>Solid</option>
                <option value="themed" {{ old('dash_background', $theme->dash_background) == 'themed' ? 'selected' : '' }}>Themed</option>
                <option value="image" {{ old('dash_background', $theme->dash_background) == 'image' ? 'selected' : '' }}>Image</option>
            </select>
        </div>
    </div>

    <div class="col-md-8" id="dash_themed_section">
        <div class="row">
            @php($dashThemedColor = old('dash_themed_color', $theme->dash_themed_color ?? ''))
            @php($dashThemedDarkColor = old('dash_themed_dark_color', $theme->dash_themed_dark_color ?? ''))

            <div class="col-md-6">
                <div class="form-group">
                    <label>Themed Color</label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text" style="padding:0;">
                                <input type="color" class="hex-color-picker" id="dash_themed_color_picker" value="{{ $dashThemedColor ?: '#F8FAFC' }}" data-target-input="dash_themed_color" style="width: 42px; height: 38px; border: none;" />
                            </span>
                        </div>
                        <input type="text" name="dash_themed_color" value="{{ $dashThemedColor }}" class="form-control hex-color-input" placeholder="#F8FAFC" data-picker-id="dash_themed_color_picker" />
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="form-group">
                    <label>Themed Dark Color</label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text" style="padding:0;">
                                <input type="color" class="hex-color-picker" id="dash_themed_dark_color_picker" value="{{ $dashThemedDarkColor ?: '#0F172A' }}" data-target-input="dash_themed_dark_color" style="width: 42px; height: 38px; border: none;" />
                            </span>
                        </div>
                        <input type="text" name="dash_themed_dark_color" value="{{ $dashThemedDarkColor }}" class="form-control hex-color-input" placeholder="#0F172A" data-picker-id="dash_themed_dark_color_picker" />
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-12" id="dash_image_section">
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label>Image (Light)</label>
                    @php($dashBg = old('dash_background_image', $theme->dash_background_image))
                    @php($dashBgRemove = (bool) old('dash_background_image_remove', false))
                    @php($dashBgUrl = $dashBg ? (\Illuminate\Support\Str::startsWith($dashBg, ['http://', 'https://', '/']) ? $dashBg : url($dashBg)) : null)

                    <input type="file" name="dash_background_image" class="form-control-file theme-image-input" accept="image/*" data-preview-target="dash_background_image_preview" />
                    <div class="mt-2">
                        <img
                            id="dash_background_image_preview"
                            src="{{ (!$dashBgRemove && $dashBgUrl) ? $dashBgUrl : '' }}"
                            data-original-src="{{ $dashBgUrl ?? '' }}"
                            style="width: 100%; height: 140px; object-fit: cover; border-radius: 10px; {{ (!$dashBgRemove && $dashBgUrl) ? '' : 'display:none;' }}"
                            alt="Dashboard background (light) preview"
                        />
                    </div>
                    <div class="mt-2">
                        <label class="checkbox-inline">
                            <input type="checkbox" name="dash_background_image_remove" value="1" {{ $dashBgRemove ? 'checked' : '' }} class="theme-image-remove" data-preview-target="dash_background_image_preview" data-input-name="dash_background_image" /> Remove old one (if exist)
                        </label>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label>Image (Dark)</label>
                    @php($dashBgDark = old('dash_dark_background_image', $theme->dash_dark_background_image))
                    @php($dashBgDarkRemove = (bool) old('dash_dark_background_image_remove', false))
                    @php($dashBgDarkUrl = $dashBgDark ? (\Illuminate\Support\Str::startsWith($dashBgDark, ['http://', 'https://', '/']) ? $dashBgDark : url($dashBgDark)) : null)

                    <input type="file" name="dash_dark_background_image" class="form-control-file theme-image-input" accept="image/*" data-preview-target="dash_dark_background_image_preview" />
                    <div class="mt-2">
                        <img
                            id="dash_dark_background_image_preview"
                            src="{{ (!$dashBgDarkRemove && $dashBgDarkUrl) ? $dashBgDarkUrl : '' }}"
                            data-original-src="{{ $dashBgDarkUrl ?? '' }}"
                            style="width: 100%; height: 140px; object-fit: cover; border-radius: 10px; {{ (!$dashBgDarkRemove && $dashBgDarkUrl) ? '' : 'display:none;' }}"
                            alt="Dashboard background (dark) preview"
                        />
                    </div>
                    <div class="mt-2">
                        <label class="checkbox-inline">
                            <input type="checkbox" name="dash_dark_background_image_remove" value="1" {{ $dashBgDarkRemove ? 'checked' : '' }} class="theme-image-remove" data-preview-target="dash_dark_background_image_preview" data-input-name="dash_dark_background_image" /> Remove old one (if exist)
                        </label>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="form-group">
                    <label>Position (Light)</label>
                    <select name="dash_background_position" class="selectpicker form-control">
                        @foreach($bgPositions as $value => $label)
                            <option value="{{ $value }}" @selected(old('dash_background_position', $theme->dash_background_position) == $value)>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="col-md-4">
                <div class="form-group">
                    <label>Position (Dark)</label>
                    <select name="dash_dark_background_position" class="selectpicker form-control">
                        @foreach($bgPositions as $value => $label)
                            <option value="{{ $value }}" @selected(old('dash_dark_background_position', $theme->dash_dark_background_position) == $value)>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="col-md-2">
                <div class="form-group">
                    <label>Opacity (Light)</label>
                    <input type="text" name="dash_background_opacity" value="{{ old('dash_background_opacity', $theme->dash_background_opacity) }}" class="form-control" placeholder="0.8" />
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label>Opacity (Dark)</label>
                    <input type="text" name="dash_dark_background_opacity" value="{{ old('dash_dark_background_opacity', $theme->dash_dark_background_opacity) }}" class="form-control" placeholder="0.8" />
                </div>
            </div>
        </div>
    </div>
        </div>
    </div>
</div>

<div class="card mb-3" id="meta_settings">
    <div class="card-header d-flex align-items-center justify-content-between">
        <h5 class="mb-0">Meta</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-4">
                <div class="form-group">
                    <label>App Platform *</label>
                    <select name="app_platform" class="selectpicker form-control" required>
                        <option value="">—</option>
                        <option value="both" {{ old('app_platform', $theme->app_platform) == 'both' ? 'selected' : '' }}>Both</option>
                        <option value="android" {{ old('app_platform', $theme->app_platform) == 'android' ? 'selected' : '' }}>Android</option>
                        <option value="ios" {{ old('app_platform', $theme->app_platform) == 'ios' ? 'selected' : '' }}>iOS</option>
                    </select>
                </div>
            </div>

            <div class="col-md-4">
                <div class="form-group mt-4">
                    <label class="d-block">Active?</label>
                    <label class="checkbox-inline">
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', (bool) $theme->is_active) ? 'checked' : '' }} /> Active
                    </label>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label class="d-block">Active For (initial)</label>
                    @php($activeFor = old('active_for', $theme->active_for ?? ['app']))
                    <label class="checkbox-inline mr-3"><input type="checkbox" name="active_for[]" value="app" {{ in_array('app', $activeFor, true) ? 'checked' : '' }} /> App</label>
                    <label class="checkbox-inline mr-3"><input type="checkbox" name="active_for[]" value="dash" {{ in_array('dash', $activeFor, true) ? 'checked' : '' }} /> Dashboard</label>
                    <label class="checkbox-inline mr-3"><input type="checkbox" name="active_for[]" value="site" {{ in_array('site', $activeFor, true) ? 'checked' : '' }} /> Site</label>
                    <div><small class="text-muted">You can also change this later from the list using the <strong>Active</strong> action.</small></div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://code.iconify.design/3/3.1.1/iconify.min.js"></script>
<script>
    (function () {
        var touched = Object.create(null);
        var autoApplied = Object.create(null);
        var autoAppliedArrays = Object.create(null);
        var isAutoApplying = false;
        var currentPalette = null;

        function normalizeHex(value) {
            if (!value) return null;
            var v = String(value).trim();
            if (v === '') return null;
            if (v[0] !== '#') v = '#' + v;
            var m3 = /^#([0-9a-fA-F]{3})$/.exec(v);
            if (m3) {
                var s = m3[1];
                return '#' + s[0] + s[0] + s[1] + s[1] + s[2] + s[2];
            }
            var m6 = /^#([0-9a-fA-F]{6})$/.exec(v);
            if (m6) return '#' + m6[1].toUpperCase();
            return null;
        }

        function markTouched(key) {
            if (!key) return;
            touched[key] = true;
        }

        function safeParseJsonArray(text) {
            if (!text) return null;
            try {
                var parsed = JSON.parse(text);
                return Array.isArray(parsed) ? parsed : null;
            } catch (e) {
                return null;
            }
        }

        function safeParseJsonObject(text) {
            if (!text) return null;
            try {
                var parsed = JSON.parse(text);
                return parsed && typeof parsed === 'object' && !Array.isArray(parsed) ? parsed : null;
            } catch (e) {
                return null;
            }
        }

        function hexToRgb(hex) {
            var h = normalizeHex(hex);
            if (!h) return null;
            var v = h.slice(1);
            var r = parseInt(v.slice(0, 2), 16);
            var g = parseInt(v.slice(2, 4), 16);
            var b = parseInt(v.slice(4, 6), 16);
            if ([r, g, b].some(function (n) { return Number.isNaN(n); })) return null;
            return { r: r, g: g, b: b };
        }

        function rgbaFromHex(hex, alpha) {
            var rgb = hexToRgb(hex);
            if (!rgb) return null;
            var a = typeof alpha === 'number' ? alpha : 1;
            return 'rgba(' + rgb.r + ',' + rgb.g + ',' + rgb.b + ',' + a + ')';
        }

        function bindHexPairSync() {
            document.querySelectorAll('.hex-color-picker').forEach(function (picker) {
                picker.addEventListener('input', function () {
                    var targetName = picker.getAttribute('data-target-input');
                    if (!targetName) return;
                    var input = document.querySelector('input[name="' + targetName + '"]');
                    if (!input) return;
                    input.value = picker.value;
                    input.dispatchEvent(new Event('input', { bubbles: true }));
                });
            });

            document.querySelectorAll('.hex-color-input').forEach(function (input) {
                input.addEventListener('input', function () {
                    var pickerId = input.getAttribute('data-picker-id');
                    if (!pickerId) return;
                    var picker = document.getElementById(pickerId);
                    if (!picker) return;

                    var hex = normalizeHex(input.value);
                    if (!hex) return;
                    picker.value = hex;
                });

                input.addEventListener('change', function () {
                    if (!isAutoApplying) {
                        markTouched(input.getAttribute('name'));
                    }
                });
            });
        }

        function createColorRow(initialValue, onChange, onTouched) {
            var row = document.createElement('div');
            row.className = 'color-array-item d-flex align-items-center mb-2';

            var picker = document.createElement('input');
            picker.type = 'color';
            picker.className = 'hex-color-picker';
            picker.style.width = '42px';
            picker.style.height = '38px';
            picker.style.border = 'none';
            picker.style.padding = '0';

            var input = document.createElement('input');
            input.type = 'text';
            input.className = 'form-control hex-color-input ml-2';
            input.style.maxWidth = '160px';
            input.placeholder = '#FFFFFF';

            var removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.className = 'btn btn-sm btn-outline-danger ml-2 color-array-remove';
            removeBtn.innerHTML = '&times;';

            var hex = normalizeHex(initialValue) || '#FFFFFF';
            picker.value = hex;
            input.value = hex;

            picker.addEventListener('input', function () {
                if (onTouched && !isAutoApplying) onTouched();
                input.value = picker.value;
                onChange();
            });
            input.addEventListener('input', function () {
                if (onTouched && !isAutoApplying) onTouched();
                var v = normalizeHex(input.value);
                if (v) picker.value = v;
                onChange();
            });
            removeBtn.addEventListener('click', function () {
                if (onTouched && !isAutoApplying) onTouched();
                row.remove();
                onChange();
            });

            row.appendChild(picker);
            row.appendChild(input);
            row.appendChild(removeBtn);
            return row;
        }

        function serializeGroup(group) {
            var hidden = group.querySelector('.color-array-hidden');
            if (!hidden) return;

            var colors = [];
            group.querySelectorAll('.color-array-item .hex-color-input').forEach(function (input) {
                var v = normalizeHex(input.value);
                if (v) colors.push(v);
            });

            hidden.value = JSON.stringify(colors);
            hidden.dispatchEvent(new Event('input', { bubbles: true }));
            hidden.dispatchEvent(new Event('change', { bubbles: true }));
        }

        function readGroupInitialColors(group) {
            var hidden = group.querySelector('.color-array-hidden');
            var fromHidden = hidden ? safeParseJsonArray(hidden.value) : null;
            if (fromHidden && fromHidden.length) return fromHidden;

            var fromDataInitial = safeParseJsonArray(group.getAttribute('data-initial'));
            if (fromDataInitial && fromDataInitial.length) return fromDataInitial;

            var fromDefault = safeParseJsonArray(group.getAttribute('data-default'));
            if (fromDefault && fromDefault.length) return fromDefault;

            return [];
        }

        function setGroupColors(group, colors) {
            var container = group.querySelector('.color-array-items');
            if (!container) return;
            container.innerHTML = '';

            var list = Array.isArray(colors) ? colors : [];
            if (!list.length) list = readGroupInitialColors(group);

            var onChange = function () {
                serializeGroup(group);
            };

            var groupField = group.getAttribute('data-field');
            var onTouched = function () {
                if (groupField) markTouched(groupField);
            };

            list.forEach(function (c) {
                container.appendChild(createColorRow(c, onChange, onTouched));
            });

            if (!container.children.length) {
                container.appendChild(createColorRow('#FFFFFF', onChange, onTouched));
            }

            serializeGroup(group);
        }

        function initColorArrayGroups() {
            document.querySelectorAll('.color-array-group').forEach(function (group) {
                var initial = readGroupInitialColors(group);
                setGroupColors(group, initial);

                var addBtn = group.querySelector('.color-array-add');
                if (addBtn) {
                    addBtn.addEventListener('click', function () {
                        var container = group.querySelector('.color-array-items');
                        if (!container) return;
                        var onChange = function () {
                            serializeGroup(group);
                        };
                        var groupField = group.getAttribute('data-field');
                        var onTouched = function () {
                            if (groupField) markTouched(groupField);
                        };
                        if (!isAutoApplying) onTouched();
                        container.appendChild(createColorRow('#FFFFFF', onChange, onTouched));
                        serializeGroup(group);
                    });
                }
            });
        }

        function setHexFieldValue(fieldName, hex) {
            var normalized = normalizeHex(hex);
            if (!normalized) return;

            var input = document.querySelector('input[name="' + fieldName + '"]');
            if (!input) return;

            var current = normalizeHex(input.value);
            if (current === normalized) return;

            input.value = normalized;
            input.dispatchEvent(new Event('input', { bubbles: true }));
            input.dispatchEvent(new Event('change', { bubbles: true }));
        }

        function setArrayFieldValue(fieldName, colors) {
            var group = document.querySelector('.color-array-group[data-field="' + fieldName + '"]');
            if (!group) return;

            isAutoApplying = true;
            try {
                setGroupColors(group, colors);
            } finally {
                isAutoApplying = false;
            }
        }

        function normalizePaletteKeys(palette) {
            if (!palette) return null;
            var out = {};
            ['50','100','200','300','400','500','600','700','800','900'].forEach(function (k) {
                if (palette[k] || palette[parseInt(k, 10)]) {
                    out[k] = normalizeHex(palette[k] || palette[parseInt(k, 10)]);
                }
            });
            return out;
        }

        function renderPaletteSwatches(palette) {
            var host = document.getElementById('primary_palette_swatches');
            if (!host) return;
            host.innerHTML = '';

            var ordered = ['50','100','200','300','400','500','600','700','800','900'];
            ordered.forEach(function (shade) {
                var hex = palette ? palette[shade] : null;
                if (!hex) return;

                var btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'btn btn-sm';
                btn.style.padding = '0';
                btn.style.border = '1px solid rgba(0,0,0,0.1)';
                btn.style.width = '48px';
                btn.style.height = '40px';
                btn.style.borderRadius = '8px';
                btn.style.background = hex;
                btn.title = shade + '  ' + hex;

                var label = document.createElement('div');
                label.style.fontSize = '11px';
                label.style.textAlign = 'center';
                label.style.marginTop = '4px';
                label.textContent = shade;

                var wrap = document.createElement('div');
                wrap.style.display = 'flex';
                wrap.style.flexDirection = 'column';
                wrap.style.alignItems = 'center';
                wrap.appendChild(btn);
                wrap.appendChild(label);

                btn.addEventListener('click', function () {
                    if (navigator.clipboard && navigator.clipboard.writeText) {
                        navigator.clipboard.writeText(hex);
                    }
                });

                host.appendChild(wrap);
            });
        }

        function computeDerivedDefaults(palette) {
            var p = normalizePaletteKeys(palette);
            if (!p) return null;

            var styleSelect = document.querySelector('select[name="button_style"]');
            var buttonStyle = styleSelect ? styleSelect.value : 'filled';

            var derived = {
                hex: {
                    // Sidebar
                    sidebar_item_inactive_color: p['900'],
                    sidebar_item_active_color: p['700'],
                    sidebar_item_inactive_dark_color: p['100'],
                    sidebar_item_active_dark_color: p['300'],
                    sidebar_subitem_inactive_color: p['900'],
                    sidebar_subitem_active_color: p['500'],
                    sidebar_subitem_inactive_dark_color: p['200'],
                    sidebar_subitem_active_dark_color: p['300'],

                    // Auth/Site/Dash themed
                    auth_themed_color: p['50'],
                    auth_themed_dark_color: p['900'],
                    site_themed_color: p['50'],
                    site_themed_dark_color: p['900'],
                    dash_themed_color: p['50'],
                    dash_themed_dark_color: p['900'],
                },
                arrays: {
                    button_colors_json: buttonStyle === 'gradient'
                        ? [p['400'], p['700']]
                        : [p['600']],
                    button_dark_colors_json: buttonStyle === 'gradient'
                        ? [p['300'], p['800']]
                        : [p['400']],

                    auth_themed_gradient_json: [p['50'], p['100']],
                    auth_themed_dark_gradient_json: [p['900'], p['700']],
                    site_gradient_json: [p['50'], p['200']],
                    site_dark_gradient_json: [p['900'], p['950'] || p['900']],
                },
            };

            return derived;
        }

        function shouldApplyField(fieldName, nextValue) {
            if (!fieldName) return false;
            var input = document.querySelector('input[name="' + fieldName + '"]');
            if (!input) return false;

            var current = normalizeHex(input.value);
            var next = normalizeHex(nextValue);
            if (!next) return false;

            if (!touched[fieldName]) return true;

            var prev = autoApplied[fieldName] ? normalizeHex(autoApplied[fieldName]) : null;
            return prev && current === prev;
        }

        function shouldApplyArray(fieldName, nextColors) {
            if (!fieldName) return false;
            var group = document.querySelector('.color-array-group[data-field="' + fieldName + '"]');
            if (!group) return false;

            var hidden = group.querySelector('.color-array-hidden');
            var current = hidden ? safeParseJsonArray(hidden.value) : null;
            var next = Array.isArray(nextColors) ? nextColors.map(normalizeHex).filter(Boolean) : [];

            if (!touched[fieldName]) return true;

            var prev = autoAppliedArrays[fieldName];
            if (!prev) return false;

            try {
                return JSON.stringify(current || []) === JSON.stringify(prev || []);
            } catch (e) {
                return false;
            }
        }

        function applyDerivedDefaults(derived) {
            if (!derived) return;
            var autoApply = document.getElementById('auto_apply_palette');
            if (autoApply && !autoApply.checked) return;

            isAutoApplying = true;
            try {
                Object.keys(derived.hex).forEach(function (field) {
                    var value = derived.hex[field];
                    if (!value) return;
                    if (!shouldApplyField(field, value)) return;
                    setHexFieldValue(field, value);
                    autoApplied[field] = value;
                });

                Object.keys(derived.arrays).forEach(function (field) {
                    var colors = derived.arrays[field] || [];
                    if (!colors.length) return;
                    if (!shouldApplyArray(field, colors)) return;
                    setArrayFieldValue(field, colors);
                    autoAppliedArrays[field] = colors;
                });

                // Update button group defaults so future “+ Add” and gradient enforcement stay consistent.
                var p = normalizePaletteKeys(currentPalette);
                if (p) {
                    var btn = document.getElementById('button_colors_group');
                    if (btn) {
                        btn.setAttribute('data-default', JSON.stringify([p['600']]));
                        btn.setAttribute('data-default-gradient', JSON.stringify([p['400'], p['700']]));
                    }
                    var btnDark = document.getElementById('button_dark_colors_group');
                    if (btnDark) {
                        btnDark.setAttribute('data-default', JSON.stringify([p['400']]));
                        btnDark.setAttribute('data-default-gradient', JSON.stringify([p['300'], p['800']]));
                    }
                }
            } finally {
                isAutoApplying = false;
            }
        }

        function fetchPaletteFor(hex) {
            var paletteHost = document.getElementById('primary_palette');
            if (!paletteHost) return Promise.resolve(null);

            var endpoint = paletteHost.getAttribute('data-palette-endpoint');
            if (!endpoint) return Promise.resolve(null);

            var normalized = normalizeHex(hex);
            if (!normalized) return Promise.resolve(null);

            var url = endpoint + '?hex=' + encodeURIComponent(normalized);
            return fetch(url, { headers: { 'Accept': 'application/json' } })
                .then(function (r) { return r.json(); })
                .then(function (json) {
                    if (!json || json.success !== true) return null;
                    var palette = (json.data || {}).palette || null;
                    return palette;
                })
                .catch(function () { return null; });
        }

        function initPrimaryPalette() {
            var paletteHost = document.getElementById('primary_palette');
            if (!paletteHost) return;

            currentPalette = safeParseJsonObject(paletteHost.getAttribute('data-initial-palette')) || null;
            if (currentPalette) {
                var normalized = normalizePaletteKeys(currentPalette);
                currentPalette = normalized;
                renderPaletteSwatches(normalized);
            }

            var themeColorInput = document.querySelector('input[name="theme_color"]');
            if (!themeColorInput) return;

            var timer = null;
            var handle = function () {
                if (timer) clearTimeout(timer);
                timer = setTimeout(function () {
                    var hex = normalizeHex(themeColorInput.value);
                    if (!hex) return;

                    fetchPaletteFor(hex).then(function (palette) {
                        if (!palette) return;
                        var normalized = normalizePaletteKeys(palette);
                        currentPalette = normalized;
                        renderPaletteSwatches(normalized);
                        applyDerivedDefaults(computeDerivedDefaults(normalized));
                        updateThemePresetPreview();
                    });
                }, 250);
            };

            themeColorInput.addEventListener('change', function () {
                markTouched('theme_color');
                handle();
            });
            themeColorInput.addEventListener('input', function () {
                handle();
            });

            if(!window.location.pathname.endsWith('/edit')) {
                // Apply once on initial load (for create screen, most fields are still “untouched”).
                applyDerivedDefaults(computeDerivedDefaults(currentPalette));
            }

            updateThemePresetPreview();
        }

        function toggleSection(el, show) {
            if (!el) return;
            el.style.display = show ? '' : 'none';
        }

        function applyButtonStyleRules() {
            var styleSelect = document.querySelector('select[name="button_style"]');
            if (!styleSelect) return;
            var style = styleSelect.value;

            var help = document.getElementById('button_colors_help');
            if (help) {
                help.textContent = style === 'gradient'
                    ? 'Gradient style: use 2+ colors.'
                    : 'Used by the selected button style.';
            }

            if (style !== 'gradient') return;

            ['button_colors_group', 'button_dark_colors_group'].forEach(function (id) {
                var group = document.getElementById(id);
                if (!group) return;
                var current = safeParseJsonArray((group.querySelector('.color-array-hidden') || {}).value) || [];

                if (current.length >= 2) return;
                var gradientDefaults = safeParseJsonArray(group.getAttribute('data-default-gradient')) || [];
                var fallbackDefaults = safeParseJsonArray(group.getAttribute('data-default')) || [];

                if (!current.length) {
                    var next = gradientDefaults.length ? gradientDefaults : (fallbackDefaults.length ? fallbackDefaults : ['#6366F1', '#8B5CF6']);
                    setGroupColors(group, next);
                    return;
                }

                var second = gradientDefaults[1] || gradientDefaults[0] || fallbackDefaults[0] || '#8B5CF6';
                setGroupColors(group, [current[0], second]);
            });

            updateThemePresetPreview();
        }

        function applyDynamicSections() {
            var authType = (document.querySelector('select[name="auth_background_type"]') || {}).value;
            toggleSection(document.getElementById('auth_themed_section'), authType === 'themed');
            toggleSection(document.getElementById('auth_gradient_section'), authType === 'themed_gradient');
            toggleSection(document.getElementById('auth_image_section'), authType === 'image');

            var siteType = (document.querySelector('select[name="site_background"]') || {}).value;
            toggleSection(document.getElementById('site_themed_section'), siteType === 'themed');
            toggleSection(document.getElementById('site_gradient_section'), siteType === 'gradient');

            var dashType = (document.querySelector('select[name="dash_background"]') || {}).value;
            toggleSection(document.getElementById('dash_themed_section'), dashType === 'themed');
            toggleSection(document.getElementById('dash_image_section'), dashType === 'image');
        }

        function bindDynamicSelects() {
            ['button_style', 'auth_background_type', 'site_background', 'dash_background'].forEach(function (name) {
                var el = document.querySelector('select[name="' + name + '"]');
                if (!el) return;
                el.addEventListener('change', function () {
                    applyDynamicSections();
                    applyButtonStyleRules();
                });
            });
        }

        function getBorderRadiusBySetting(setting, intensity, baseSpacing) {
            var s = typeof baseSpacing === 'number' && baseSpacing > 0 ? baseSpacing : 16;

            if (setting === 'rounded-none') return 0;

            if (setting === 'rounded-full') {
                return 999;
            }

            if (setting === 'rounded-lg') {
                if (intensity === 'low') return Math.round(s * 0.75);
                if (intensity === 'medium') return Math.round(s * 1.1);
                return Math.round(s * 1.6);
            }

            // rounded
            if (intensity === 'low') return Math.round(s * 0.6);
            if (intensity === 'medium') return Math.round(s * 0.9);
            return Math.round(s * 1.25);
        }

        function appearanceKeys(raw) {
            switch (raw) {
                case 'system_both':
                    return ['system', 'light', 'dark'];
                case 'system':
                    return ['system'];
                case 'both':
                    return ['light', 'dark'];
                case 'light':
                    return ['light'];
                case 'dark':
                    return ['dark'];
                default:
                    return ['system'];
            }
        }

        function updateThemePresetPreview() {
            var preview = document.getElementById('theme_preset_preview');
            if (!preview) return;

            var themeName = ((document.querySelector('input[name="name"]') || {}).value) || '';
            var themeColor = normalizeHex(((document.querySelector('input[name="theme_color"]') || {}).value)) || '#6366F1';
            var fontFamily = ((document.querySelector('input[name="font_family"]') || {}).value) || 'Inter';
            var iconPack = ((document.querySelector('select[name="icon_pack"]') || {}).value) || 'solar';
            var appearance = ((document.querySelector('select[name="theme_appearance"]') || {}).value) || 'system_both';
            var borderRadius = ((document.querySelector('select[name="border_radius"]') || {}).value) || 'rounded-lg';
            var inputDesign = ((document.querySelector('select[name="input_design"]') || {}).value) || 'filled';
            var buttonStyle = ((document.querySelector('select[name="button_style"]') || {}).value) || 'filled';
            var itemSizeRaw = ((document.querySelector('select[name="item_size"]') || {}).value) || '16';
            var baseSpacing = parseInt(itemSizeRaw, 10);
            if (Number.isNaN(baseSpacing) || baseSpacing <= 0) baseSpacing = 16;

            // Drive CSS sizing like the app spacing scale
            preview.style.setProperty('--tpp-s', baseSpacing + 'px');

            var palette200 = currentPalette ? normalizeHex(currentPalette['200'] || currentPalette[200]) : null;
            var palette500 = currentPalette ? normalizeHex(currentPalette['500'] || currentPalette[500]) : null;
            var palette900 = currentPalette ? normalizeHex(currentPalette['900'] || currentPalette[900]) : null;

            var buttonColorsText = ((document.querySelector('textarea[name="button_colors_json"]') || {}).value) || '';
            var buttonColors = safeParseJsonArray(buttonColorsText) || [];
            buttonColors = buttonColors
                .map(function (c) { return normalizeHex(c); })
                .filter(Boolean);
            if (!buttonColors.length) buttonColors = [palette500 || themeColor];

            var isGradient = buttonColors.length >= 2;
            var outlinedButton = buttonStyle === 'outlined';
            var outlinedInput = inputDesign === 'outlined';

            // Match Flutter isSelected=true behavior
            var isSelected = true;

            var highRadius = getBorderRadiusBySetting(borderRadius, 'high', baseSpacing);
            var radius = isSelected
                ? (highRadius > baseSpacing * 5 ? highRadius * 0.05 : highRadius)
                : (baseSpacing * 1.25);
            radius = Math.max(0, Math.round(radius));

            preview.style.borderRadius = radius + 'px';
            preview.style.borderWidth = isSelected ? '3px' : '1px';
            preview.style.borderColor = isSelected
                ? (rgbaFromHex(palette200 || '#FFFFFF', 0.95) || 'rgba(255,255,255,0.95)')
                : 'rgba(255,255,255,0.18)';

            if (isGradient) {
                preview.style.background = 'linear-gradient(135deg, ' + buttonColors.join(', ') + ')';
            } else {
                preview.style.background = buttonColors[0];
            }

            var initialsEl = document.getElementById('theme_preset_preview_initials');
            var fontEl = document.getElementById('theme_preset_preview_font');

            var first = themeName.length >= 1 ? themeName.substring(0, 1).toUpperCase() : '';
            var second = themeName.length >= 2 ? themeName.substring(1, 2).toLowerCase() : '';

            if (initialsEl) {
                initialsEl.style.fontFamily = fontFamily;
                initialsEl.innerHTML = '<span class="tpp-first">' + first + '</span>' + (second ? '<span class="tpp-second">' + second + '</span>' : '');
            }
            if (fontEl) {
                fontEl.style.fontFamily = fontFamily;
                fontEl.textContent = fontFamily;
            }

            // Font CSS is loaded via initFontFamilyAutoLoader (debounced) to avoid
            // injecting many <link> tags while typing.

            var miniInput = document.getElementById('theme_preset_preview_mini_input');
            if (miniInput) {
                var lowRadius = getBorderRadiusBySetting(borderRadius, 'low', baseSpacing);
                miniInput.style.borderRadius = Math.round(lowRadius) + 'px';
                miniInput.style.background = outlinedInput ? 'transparent' : 'rgba(255,255,255,0.18)';
                miniInput.style.border = outlinedInput ? '1px solid rgba(255,255,255,0.65)' : 'none';
            }

            function iconPackToPreviewIcons(pack) {
                switch (pack) {
                    case 'solar':
                        return ['solar:widget-6-bold', 'solar:settings-bold', 'solar:users-group-rounded-bold'];
                    case 'fontawesome':
                        return ['fa6-solid:gauge-high', 'fa6-solid:gear', 'fa6-solid:users'];
                    case 'bootstrap':
                        return ['bi:speedometer2', 'bi:gear-fill', 'bi:people-fill'];
                    case 'heroicons':
                        return ['heroicons:squares-2x2-solid', 'heroicons:cog-6-tooth-solid', 'heroicons:users-solid'];
                    case 'material':
                        return ['material-symbols:dashboard-rounded', 'material-symbols:settings-rounded', 'material-symbols:group-rounded'];
                    case 'cupertino':
                        return ['ion:apps', 'ion:settings', 'ion:people'];
                    default:
                        return ['solar:widget-6-bold', 'solar:settings-bold', 'solar:users-group-rounded-bold'];
                }
            }

            var iconKeys = iconPackToPreviewIcons(iconPack);
            var icons = document.querySelectorAll('#theme_preset_preview_icons [data-theme-preset-icon]');
            icons.forEach(function (el, idx) {
                el.setAttribute('data-icon', iconKeys[idx] || iconKeys[0]);
                el.setAttribute('title', 'Icon Pack: ' + iconPack);
            });

            if (window.Iconify && typeof window.Iconify.scan === 'function') {
                window.Iconify.scan(preview);
            }

            // Render appearance (system/light/dark) icons in the center.
            var appearanceEl = document.getElementById('theme_preset_preview_appearance');
            if (appearanceEl) {
                function iconPackToAppearanceIcons(pack) {
                    switch (pack) {
                        case 'solar':
                            return {
                                system: 'solar:devices-bold',
                                light: 'solar:sun-bold',
                                dark: 'solar:moon-bold'
                            };
                        case 'fontawesome':
                            return {
                                system: 'fa6-solid:desktop',
                                light: 'fa6-solid:sun',
                                dark: 'fa6-solid:moon'
                            };
                        case 'bootstrap':
                            return {
                                system: 'bi:pc-display',
                                light: 'bi:brightness-high-fill',
                                dark: 'bi:moon-stars-fill'
                            };
                        case 'heroicons':
                            return {
                                system: 'heroicons:computer-desktop-solid',
                                light: 'heroicons:sun-solid',
                                dark: 'heroicons:moon-solid'
                            };
                        case 'material':
                            return {
                                system: 'material-symbols:devices-rounded',
                                light: 'material-symbols:light-mode-rounded',
                                dark: 'material-symbols:dark-mode-rounded'
                            };
                        case 'cupertino':
                            return {
                                system: 'ion:laptop',
                                light: 'ion:sunny',
                                dark: 'ion:moon'
                            };
                        default:
                            return {
                                system: 'material-symbols:devices-rounded',
                                light: 'material-symbols:light-mode-rounded',
                                dark: 'material-symbols:dark-mode-rounded'
                            };
                    }
                }

                var appearanceMap = iconPackToAppearanceIcons(iconPack);
                appearanceEl.innerHTML = appearanceKeys(appearance)
                    .map(function (key) {
                        var icon = appearanceMap[key] || appearanceMap.system;
                        return '<span class="tpp-appearance-icon"><span class="iconify" data-icon="' + icon + '"></span></span>';
                    })
                    .join('');
            }

            if (window.Iconify && typeof window.Iconify.scan === 'function') {
                window.Iconify.scan(preview);
            }
        }

        function initThemePresetPreview() {
            var selectors = [
                'input[name="name"]',
                'input[name="theme_color"]',
                'input[name="font_family"]',
                'select[name="theme_appearance"]',
                'select[name="icon_pack"]',
                'select[name="item_size"]',
                'select[name="border_radius"]',
                'select[name="input_design"]',
                'select[name="button_style"]',
                'textarea[name="button_colors_json"]',
            ];

            var debouncedUpdate = debounce(updateThemePresetPreview, 150);
            selectors.forEach(function (sel) {
                var el = document.querySelector(sel);
                if (!el) return;

                // Typing in text inputs should not spam heavy preview work.
                if (sel === 'input[name="name"]' || sel === 'input[name="font_family"]') {
                    el.addEventListener('input', debouncedUpdate);
                    el.addEventListener('change', updateThemePresetPreview);
                    return;
                }

                el.addEventListener('input', updateThemePresetPreview);
                el.addEventListener('change', updateThemePresetPreview);
            });

            updateThemePresetPreview();
        }

        function loadGoogleFontCss(fontFamily) {
            var name = (fontFamily || '').trim();
            if (!name) return;

            var id = 'google_font_css_' + name.toLowerCase().replace(/[^a-z0-9]+/g, '_');
            if (document.getElementById(id)) return;

            var href = 'https://fonts.googleapis.com/css2?family=' + encodeURIComponent(name).replace(/%20/g, '+') + ':wght@400;700;800&display=swap';
            var link = document.createElement('link');
            link.id = id;
            link.rel = 'stylesheet';
            link.href = href;
            document.head.appendChild(link);
        }

        function debounce(fn, wait) {
            var t = null;
            return function () {
                var ctx = this;
                var args = arguments;
                if (t) window.clearTimeout(t);
                t = window.setTimeout(function () {
                    fn.apply(ctx, args);
                }, wait);
            };
        }

        function initFontFamilyAutoLoader() {
            var input = document.getElementById('font_family_input');
            if (!input) return;

            // Load current value on first paint so preview reliably renders the intended font.
            loadGoogleFontCss((input.value || 'Inter'));

            var debouncedLoad = debounce(function () {
                loadGoogleFontCss((input.value || 'Inter'));
            }, 450);

            input.addEventListener('input', debouncedLoad);
            input.addEventListener('change', function () {
                loadGoogleFontCss((input.value || 'Inter'));
            });
        }

        function initThemeImagePreviews() {
            document.querySelectorAll('.theme-image-input').forEach(function (input) {
                input.addEventListener('change', function () {
                    var targetId = input.getAttribute('data-preview-target');
                    if (!targetId) return;
                    var img = document.getElementById(targetId);
                    if (!img) return;
                    var file = input.files && input.files[0] ? input.files[0] : null;
                    if (!file) return;

                    var url = URL.createObjectURL(file);
                    img.src = url;
                    img.style.display = '';

                    var removeCb = document.querySelector('.theme-image-remove[data-input-name="' + input.name + '"]');
                    if (removeCb) removeCb.checked = false;
                });
            });

            document.querySelectorAll('.theme-image-remove').forEach(function (cb) {
                cb.addEventListener('change', function () {
                    var targetId = cb.getAttribute('data-preview-target');
                    var inputName = cb.getAttribute('data-input-name');
                    if (!targetId || !inputName) return;

                    var img = document.getElementById(targetId);
                    var fileInput = document.querySelector('input[type="file"][name="' + inputName + '"]');
                    if (cb.checked) {
                        if (fileInput) fileInput.value = '';
                        if (img) {
                            img.src = '';
                            img.style.display = 'none';
                        }
                        return;
                    }

                    if (img) {
                        var original = img.getAttribute('data-original-src') || '';
                        if (original) {
                            img.src = original;
                            img.style.display = '';
                        }
                    }
                });
            });
        }

        bindHexPairSync();
        applyDynamicSections();
        bindDynamicSelects();
        initFontFamilyAutoLoader();
        initThemePresetPreview();
        initThemeImagePreviews();

        if (!window.location.pathname.endsWith('/edit')) {
            initColorArrayGroups();
            applyButtonStyleRules();
            initPrimaryPalette();
        } else {
            initPrimaryPalette();
            initColorArrayGroups();
        }

    })();
</script>
@endpush
