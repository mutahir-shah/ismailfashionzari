<?php

namespace App\Http\Controllers;

use App\Helpers\PaletteGenerator;
use App\Models\ThemeSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ThemeSettingController extends Controller
{
    private const ACTIVE_FOR_OPTIONS = ['app', 'dash', 'site'];

    public function index(): View
    {
        $themeSettings = ThemeSetting::query()
            ->where('is_deleted', false)
            ->orderBy('id', 'asc')
            ->get();

        return view('backend.setting.theme_settings.index', compact('themeSettings'));
    }

    public function create(): View
    {
        $baseHex = '#6366F1';
        $palette = PaletteGenerator::generateModernPalette($baseHex);

        $theme = new ThemeSetting([
            'theme_appearance' => 'system_both',
            'theme_color' => $baseHex,
            // Match app defaults more closely
            'font_family' => 'Jost',
            'icon_pack' => 'solar',
            'item_size' => 16,
            'border_radius' => 'rounded-lg',
            'input_design' => 'filled',
            'button_style' => 'filled',
            'button_preferred_width' => 'fit',
            'button_height' => 52,

            // Button colors (derived from primary)
            'button_colors' => [$palette[600] ?? $baseHex],
            'button_dark_colors' => [$palette[400] ?? $baseHex],

            'sidebar_style' => 'normal',
            'sidebar_corner' => 'rounded',

            // Sidebar colors (derived from primary)
            'sidebar_item_inactive_color' => $palette[400] ?? null,
            'sidebar_item_active_color' => $palette[600] ?? null,
            'sidebar_item_inactive_dark_color' => $palette[500] ?? null,
            'sidebar_item_active_dark_color' => $palette[300] ?? null,
            'sidebar_subitem_inactive_color' => $palette[400] ?? null,
            'sidebar_subitem_active_color' => $palette[600] ?? null,
            'sidebar_subitem_inactive_dark_color' => $palette[500] ?? null,
            'sidebar_subitem_active_dark_color' => $palette[300] ?? null,

            // Auth defaults (derived from primary)
            'auth_background_type' => 'themed_gradient',
            'auth_themed_color' => $palette[50] ?? null,
            'auth_themed_dark_color' => $palette[900] ?? null,
            'auth_themed_gradient' => [
                $palette[50] ?? '#EEF2FF',
                $palette[100] ?? '#DBEAFE',
            ],
            'auth_themed_dark_gradient' => [
                $palette[900] ?? '#0F172A',
                $palette[700] ?? '#312E81',
            ],
            'auth_themed_deg' => 120,
            'auth_themed_dark_deg' => 120,
            'auth_background_opacity' => '0.8',
            'auth_dark_background_opacity' => '0.8',

            // Site/Dash defaults (derived from primary)
            'site_background' => 'themed',
            'site_themed_color' => $palette[50] ?? null,
            'site_themed_dark_color' => $palette[900] ?? null,
            'site_gradient' => [
                $palette[50] ?? '#F8FAFC',
                $palette[200] ?? '#EEF2FF',
            ],
            'site_dark_gradient' => [
                $palette[900] ?? '#0F172A',
                $palette[950] ?? '#020617',
            ],

            'dash_background' => 'themed',
            'dash_themed_color' => $palette[50] ?? null,
            'dash_themed_dark_color' => $palette[900] ?? null,

            'chart_colors' => ['#6366F1', '#3B82F6', '#10B981', '#F97316', '#F43F5E', '#8B5CF6'],

            'app_platform' => 'both',
            'is_active' => true,
            'active_for' => ['app'],
            'is_deleted' => false,
        ]);

        return view('backend.setting.theme_settings.create', compact('theme'));
    }

    public function palette(Request $request): JsonResponse
    {
        $hex = $request->query('hex', '');

        try {
            $normalized = $this->normalizeHexColor((string) $hex);

            return response()->json([
                'success' => true,
                'debug_bar' => env('APP_DEBUG', false) ? true : false,
                'data' => [
                    'hex' => $normalized,
                    'palette' => PaletteGenerator::generateModernPalette($normalized),
                ],
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'debug_bar' => env('APP_DEBUG', false) ? true : false,
                'message' => 'Invalid color value.',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    public function store(Request $request): RedirectResponse
    {
        if (!config('app.user_verified')) {
            return redirect()->back()->with('not_permitted', __('db.This feature is disable for demo!'));
        }

        if (config('database.connections.saleprosaas_landlord') && !tenant()) {
            $routePrefix = 'superadminSetting.';
        } else {
            $routePrefix = 'setting.';
        }

        $validated = $this->validateThemePayload($request);
        $validated = $this->processBackgroundImageUploads($request, $validated);

        DB::beginTransaction();
        try {
            ThemeSetting::create($validated);
            DB::commit();

            return redirect()
                ->route($routePrefix . 'themeSettings.index')
                ->with('message', 'Theme setting created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()->withInput()->with('not_permitted', $e->getMessage());
        }
    }

    public function edit(ThemeSetting $themeSetting): View
    {
        return view('backend.setting.theme_settings.edit', compact('themeSetting'));
    }

    public function update(Request $request, ThemeSetting $themeSetting): RedirectResponse
    {
        if (!config('app.user_verified')) {
            return redirect()->back()->with('not_permitted', __('db.This feature is disable for demo!'));
        }

        if (config('database.connections.saleprosaas_landlord') && !tenant()) {
            $routePrefix = 'superadminSetting.';
        } else {
            $routePrefix = 'setting.';
        }

        $validated = $this->validateThemePayload($request, $themeSetting->id);
        $validated = $this->processBackgroundImageUploads($request, $validated, $themeSetting);

        DB::beginTransaction();
        try {
            $themeSetting->update($validated);
            DB::commit();

            return redirect()
                ->route($routePrefix . 'themeSettings.index')
                ->with('message', 'Theme setting updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()->withInput()->with('not_permitted', $e->getMessage());
        }
    }


    public function destroy(ThemeSetting $themeSetting): RedirectResponse
    {
        if (!config('app.user_verified')) {
            return redirect()->back()->with('not_permitted', __('db.This feature is disable for demo!'));
        }

        if (config('database.connections.saleprosaas_landlord') && !tenant()) {
            $routePrefix = 'superadminSetting.';
        } else {
            $routePrefix = 'setting.';
        }

        $themeSetting->is_deleted = true;
        $themeSetting->is_active = false;
        $themeSetting->save();

        return redirect()
            ->route($routePrefix . 'themeSettings.index')
            ->with('message', 'Theme setting deleted successfully.');
    }

    public function updateActiveFor(Request $request, ThemeSetting $themeSetting): JsonResponse
    {
        if (!config('app.user_verified')) {
            return response()->json([
                'success' => false,
                'debug_bar' => env('APP_DEBUG', false) ? true : false,
                'message' => __('db.This feature is disable for demo!'),
            ], 403);
        }

        $validated = $request->validate([
            'active_for' => ['required', 'array', 'min:1'],
            'active_for.*' => ['required', 'string', 'in:' . implode(',', self::ACTIVE_FOR_OPTIONS)],
        ]);

        $activeFor = array_values(array_unique($validated['active_for']));

        $themeSetting->active_for = $activeFor;
        $themeSetting->save();

        return response()->json([
            'success' => true,
            'debug_bar' => env('APP_DEBUG', false) ? true : false,
            'message' => 'Active targets updated successfully.',
            'active_for' => $activeFor,
            'data' => [
                'id' => $themeSetting->id,
                'active_for' => $activeFor,
            ],
        ]);
    }

    private function validateThemePayload(Request $request, ?int $ignoreId = null): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'theme_appearance' => ['required', 'in:system_both,both,system,light,dark'],
            'theme_color' => ['required', 'string', 'max:20'],
            'font_family' => ['required', 'string', 'max:255'],
            'icon_pack' => ['required', 'in:solar,fontawesome,bootstrap,heroicons,material,cupertino'],
            'item_size' => ['required', 'integer', 'min:10', 'max:40'],
            'border_radius' => ['required', 'in:rounded-none,rounded,rounded-lg,rounded-full'],
            'input_design' => ['required', 'in:outlined,filled'],
            'light_logo' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp,gif', 'max:5120'],
            'light_logo_remove' => ['nullable', 'boolean'],
            'dark_logo' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp,gif', 'max:5120'],
            'dark_logo_remove' => ['nullable', 'boolean'],

            'chart_colors_json' => ['nullable', 'string'],

            'button_style' => ['required', 'in:filled,outlined,gradient,glassed'],
            'button_colors_json' => ['nullable', 'string'],
            'button_dark_colors_json' => ['nullable', 'string'],
            'button_preferred_width' => ['required', 'in:fit,full'],
            'button_height' => ['required', 'integer', 'min:30', 'max:120'],

            'sidebar_style' => ['required', 'in:floating,normal'],
            'sidebar_corner' => ['required', 'in:rounded,none'],
            'sidebar_item_inactive_color' => ['nullable', 'string', 'max:20'],
            'sidebar_item_active_color' => ['nullable', 'string', 'max:20'],
            'sidebar_item_inactive_dark_color' => ['nullable', 'string', 'max:20'],
            'sidebar_item_active_dark_color' => ['nullable', 'string', 'max:20'],
            'sidebar_subitem_inactive_color' => ['nullable', 'string', 'max:20'],
            'sidebar_subitem_active_color' => ['nullable', 'string', 'max:20'],
            'sidebar_subitem_inactive_dark_color' => ['nullable', 'string', 'max:20'],
            'sidebar_subitem_active_dark_color' => ['nullable', 'string', 'max:20'],

            'auth_background_type' => ['required', 'in:black-and-white,themed,themed_gradient,image'],
            'auth_themed_color' => ['nullable', 'string', 'max:20'],
            'auth_themed_dark_color' => ['nullable', 'string', 'max:20'],
            'auth_themed_gradient_json' => ['nullable', 'string'],
            'auth_themed_dark_gradient_json' => ['nullable', 'string'],
            'auth_themed_deg' => ['nullable', 'integer', 'min:0', 'max:360'],
            'auth_themed_dark_deg' => ['nullable', 'integer', 'min:0', 'max:360'],
            'auth_background_image' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp,gif', 'max:5120'],
            'auth_background_image_remove' => ['nullable', 'boolean'],
            'auth_background_position' => ['nullable', 'in:center,top,bottom,left,right,top-left,top-right,bottom-left,bottom-right'],
            'auth_background_opacity' => ['nullable', 'string', 'max:10'],
            'auth_dark_background_image' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp,gif', 'max:5120'],
            'auth_dark_background_image_remove' => ['nullable', 'boolean'],
            'auth_dark_background_position' => ['nullable', 'in:center,top,bottom,left,right,top-left,top-right,bottom-left,bottom-right'],
            'auth_dark_background_opacity' => ['nullable', 'string', 'max:10'],

            'site_background' => ['required', 'in:solid,themed,gradient'],
            'site_themed_color' => ['nullable', 'string', 'max:20'],
            'site_themed_dark_color' => ['nullable', 'string', 'max:20'],
            'site_gradient_json' => ['nullable', 'string'],
            'site_dark_gradient_json' => ['nullable', 'string'],

            'dash_background' => ['required', 'in:solid,themed,image'],
            'dash_themed_color' => ['nullable', 'string', 'max:20'],
            'dash_themed_dark_color' => ['nullable', 'string', 'max:20'],
            'dash_background_image' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp,gif', 'max:5120'],
            'dash_background_image_remove' => ['nullable', 'boolean'],
            'dash_background_opacity' => ['nullable', 'string', 'max:10'],
            'dash_background_position' => ['nullable', 'in:center,top,bottom,left,right,top-left,top-right,bottom-left,bottom-right'],
            'dash_dark_background_image' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp,gif', 'max:5120'],
            'dash_dark_background_image_remove' => ['nullable', 'boolean'],
            'dash_dark_background_opacity' => ['nullable', 'string', 'max:10'],
            'dash_dark_background_position' => ['nullable', 'in:center,top,bottom,left,right,top-left,top-right,bottom-left,bottom-right'],

            'app_platform' => ['required', 'in:android,ios,both'],
            'active_for' => ['nullable', 'array'],
            'active_for.*' => ['string', 'in:' . implode(',', self::ACTIVE_FOR_OPTIONS)],
            'is_active' => ['nullable'],
        ]);

        $validated['theme_color'] = $this->normalizeHexColor($validated['theme_color']);

        foreach (
            [
                'sidebar_item_inactive_color',
                'sidebar_item_active_color',
                'sidebar_item_inactive_dark_color',
                'sidebar_item_active_dark_color',
                'sidebar_subitem_inactive_color',
                'sidebar_subitem_active_color',
                'sidebar_subitem_inactive_dark_color',
                'sidebar_subitem_active_dark_color',
                'auth_themed_color',
                'auth_themed_dark_color',
                'site_themed_color',
                'site_themed_dark_color',
                'dash_themed_color',
                'dash_themed_dark_color',
            ] as $hexField
        ) {
            if (!empty($validated[$hexField])) {
                $validated[$hexField] = $this->normalizeHexColor($validated[$hexField]);
            }
        }

        $validated['chart_colors'] = $this->decodeJsonArray(Arr::pull($validated, 'chart_colors_json'), 'chart_colors_json');
        $validated['button_colors'] = $this->decodeJsonArray(Arr::pull($validated, 'button_colors_json'), 'button_colors_json');
        $validated['button_dark_colors'] = $this->decodeJsonArray(Arr::pull($validated, 'button_dark_colors_json'), 'button_dark_colors_json');
        $validated['auth_themed_gradient'] = $this->decodeJsonArray(Arr::pull($validated, 'auth_themed_gradient_json'), 'auth_themed_gradient_json');
        $validated['auth_themed_dark_gradient'] = $this->decodeJsonArray(Arr::pull($validated, 'auth_themed_dark_gradient_json'), 'auth_themed_dark_gradient_json');
        $validated['site_gradient'] = $this->decodeJsonArray(Arr::pull($validated, 'site_gradient_json'), 'site_gradient_json');
        $validated['site_dark_gradient'] = $this->decodeJsonArray(Arr::pull($validated, 'site_dark_gradient_json'), 'site_dark_gradient_json');

        $validated['active_for'] = $validated['active_for'] ?? ['app'];
        $validated['is_active'] = $request->boolean('is_active');
        $validated['is_deleted'] = false;

        return $validated;
    }

    private function processBackgroundImageUploads(Request $request, array $validated, ?ThemeSetting $themeSetting = null): array
    {
        $fields = [
            'light_logo',
            'dark_logo',
            'auth_background_image',
            'auth_dark_background_image',
            'dash_background_image',
            'dash_dark_background_image',
        ];

        foreach ($fields as $field) {
            $removeKey = $field . '_remove';
            $previous = $themeSetting ? (string) ($themeSetting->{$field} ?? '') : '';
            $previous = $previous !== '' ? $previous : null;

            if ($request->boolean($removeKey)) {
                $validated[$field] = null;
                $this->deleteLocalThemeImageIfPossible($previous);
                unset($validated[$removeKey]);
                continue;
            }

            unset($validated[$removeKey]);

            if (!$request->hasFile($field)) {
                continue;
            }

            $file = $request->file($field);
            if ($file === null) {
                continue;
            }

            $validated[$field] = $this->storeThemeImageToPublic($file);
            $this->deleteLocalThemeImageIfPossible($previous);
        }

        return $validated;
    }

    private function storeThemeImageToPublic($file): string
    {
        $directory = public_path('images/theme_settings');
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $extension = strtolower((string) $file->getClientOriginalExtension());
        $extension = $extension !== '' ? $extension : 'png';

        $filename = 'theme_bg_' . date('YmdHis') . '_' . Str::random(10) . '.' . $extension;
        $file->move($directory, $filename);

        return 'images/theme_settings/' . $filename;
    }

    private function deleteLocalThemeImageIfPossible(?string $path): void
    {
        if ($path === null || trim($path) === '') {
            return;
        }

        $relative = trim($path);

        if (Str::startsWith($relative, ['http://', 'https://'])) {
            $parsed = parse_url($relative, PHP_URL_PATH);
            if (is_string($parsed) && $parsed !== '') {
                $relative = ltrim($parsed, '/');
            }
        }

        $relative = ltrim($relative, '/');
        if (!Str::startsWith($relative, 'images/theme_settings/')) {
            return;
        }

        $absolute = public_path($relative);
        if (is_file($absolute)) {
            @unlink($absolute);
        }
    }

    private function normalizeHexColor(string $color): string
    {
        $trimmed = trim($color);
        if ($trimmed === '') {
            return $trimmed;
        }

        if ($trimmed[0] !== '#') {
            $trimmed = '#' . $trimmed;
        }

        return $trimmed;
    }

    /**
     * @return array<int, mixed>|null
     */
    private function decodeJsonArray(?string $value, string $fieldName): ?array
    {
        $value = $value === null ? null : trim($value);

        if ($value === null || $value === '') {
            return null;
        }

        $decoded = json_decode($value, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw ValidationException::withMessages([
                $fieldName => ['Invalid JSON: please provide a JSON array.'],
            ]);
        }

        if (!is_array($decoded)) {
            throw ValidationException::withMessages([
                $fieldName => ['Invalid JSON: value must be an array.'],
            ]);
        }

        return $decoded;
    }
}
