<?php

namespace App\Http\Controllers\Api;

use App\Models\ThemeSetting;

trait ProvidesThemeBackgrounds
{
    protected function withAuthBackground(array $payload, ?string $platform = null): array
    {
        return $this->withBackground($payload, 'auth', $platform);
    }

    protected function withDashBackground(array $payload, ?string $platform = null): array
    {
        return $this->withBackground($payload, 'dash', $platform);
    }

    protected function withBackground(array $payload, string $context, ?string $platform = null): array
    {
        if (!array_key_exists('debug_bar', $payload)) {
            $payload['debug_bar'] = env('APP_DEBUG', false) ? true : false;
        }

        if (!array_key_exists('background', $payload)) {
            $themeSetting = $this->activeThemeSetting($platform);
            $payload['background'] = $context === 'auth'
                ? $this->buildAuthBackground($themeSetting)
                : $this->buildDashBackground($themeSetting);
        }

        return $payload;
    }

    protected function activeThemeSetting(?string $platform = null): ?ThemeSetting
    {
        try {
            return ThemeSetting::active($platform)->first();
        } catch (\Throwable $e) {
            return null;
        }
    }

    protected function buildAuthBackground(?ThemeSetting $themeSetting): array
    {
        if ($themeSetting === null) {
            return $this->fallbackSolidBackground();
        }

        if ($themeSetting->auth_background_type === 'image') {
            $light = $this->normalizeImagePath($themeSetting->auth_background_image);
            $dark = $this->normalizeImagePath($themeSetting->auth_dark_background_image);

            if ($light !== null || $dark !== null) {
                return [
                    'image' => [
                        'light' => $light,
                        'dark' => $dark,
                    ],
                ];
            }
        }

        if ($themeSetting->auth_background_type === 'themed_gradient') {
            $lightColors = $this->normalizeHexColors($themeSetting->auth_themed_gradient);
            $darkColors = $this->normalizeHexColors($themeSetting->auth_themed_dark_gradient);
            $deg = $themeSetting->auth_themed_deg ?? 0;

            if (!empty($lightColors) || !empty($darkColors)) {
                return $this->gradientBackground(
                    !empty($lightColors) ? $lightColors : $darkColors,
                    !empty($darkColors) ? $darkColors : $lightColors,
                    $deg,
                );
            } else {
                return $this->gradientBackground(
                    [$themeSetting->theme_colors[50] ?? '#FFFFFF', $themeSetting->theme_colors[100] ?? '#F3F4F6'],
                    [$themeSetting->theme_colors[900] ?? '#0B1022', $themeSetting->theme_colors[800] ?? '#000000'],
                    120,
                );
            }
        }

        if ($themeSetting->auth_background_type === 'black-and-white') {
            return $this->gradientBackground(
                ['#FFFFFF', '#F3F4F6'],
                ['#0B1022', '#000000'],
                180,
            );
        }

        if ($themeSetting->auth_background_type === 'themed') {
            $light = $this->normalizeHexColor($themeSetting->auth_themed_color)
                ?? $this->normalizeHexColor($themeSetting->theme_colors[50])
                ?? '#FFFFFF';
            $dark = $this->normalizeHexColor($themeSetting->auth_themed_dark_color)
                ?? $this->normalizeHexColor($themeSetting->theme_colors[900])
                ?? '#0B1022';

            return $this->solidBackground($light, $dark);
        }

        // Default: emit a solid background
        $light = $this->normalizeHexColor($themeSetting->theme_colors[50]) ?? '#FFFFFF';
        $dark = $this->normalizeHexColor($themeSetting->theme_colors[900]) ?? '#0B1022';

        return $this->solidBackground($light, $dark);
    }

    protected function buildDashBackground(?ThemeSetting $themeSetting): array
    {
        if ($themeSetting === null) {
            return $this->fallbackSolidBackground();
        }

        if ($themeSetting->dash_background === 'image') {
            $light = $this->normalizeImagePath($themeSetting->dash_background_image);
            $dark = $this->normalizeImagePath($themeSetting->dash_dark_background_image);

            if ($light !== null || $dark !== null) {
                return [
                    'image' => [
                        'light' => $light,
                        'dark' => $dark,
                    ],
                ];
            }
        }

        if ($themeSetting->dash_background === 'themed') {
            $light = $this->normalizeHexColor($themeSetting->dash_themed_color)
                ?? $this->normalizeHexColor($themeSetting->theme_color)
                ?? '#FFFFFF';
            $dark = $this->normalizeHexColor($themeSetting->dash_themed_dark_color)
                ?? '#0B1022';

            return $this->solidBackground($light, $dark);
        }

        // solid (or unknown)
        return $this->fallbackSolidBackground();
    }

    private function fallbackSolidBackground(): array
    {
        return $this->solidBackground('#FFFFFF', '#0B1022');
    }

    private function solidBackground(string $light, string $dark): array
    {
        return [
            'light' => $light,
            'dark' => $dark,
        ];
    }

    private function gradientBackground(array $lightColors, array $darkColors, int $deg): array
    {
        return [
            'gradient' => [
                'light' => [
                    'colors' => array_values($lightColors),
                ],
                'dark' => [
                    'colors' => array_values($darkColors),
                ],
                'deg' => $deg,
            ],
        ];
    }

    private function normalizeHexColors($value): array
    {
        if (!is_array($value)) {
            return [];
        }

        $colors = [];
        foreach ($value as $color) {
            $normalized = $this->normalizeHexColor($color);
            if ($normalized !== null) {
                $colors[] = $normalized;
            }
        }

        return $colors;
    }

    private function normalizeHexColor($value): ?string
    {
        if ($value === null) {
            return null;
        }

        $raw = trim((string) $value);
        if ($raw === '') {
            return null;
        }

        if (str_starts_with($raw, '#')) {
            $hex = substr($raw, 1);
        } elseif (str_starts_with(strtolower($raw), '0x')) {
            $hex = substr($raw, 2);
        } else {
            $hex = $raw;
        }

        $hex = preg_replace('/[^0-9a-fA-F]/', '', $hex) ?? '';

        // If ARGB/RGBA, keep last 6 (RGB)
        if (strlen($hex) === 8) {
            $hex = substr($hex, 2);
        }

        if (strlen($hex) !== 6) {
            return null;
        }

        return '#' . strtoupper($hex);
    }

    private function normalizeImagePath($value): ?string
    {
        if ($value === null) {
            return null;
        }

        $raw = trim((string) $value);
        if ($raw === '') {
            return null;
        }

        if (str_starts_with($raw, 'http://') || str_starts_with($raw, 'https://')) {
            $path = parse_url($raw, PHP_URL_PATH);
            if (is_string($path) && $path !== '') {
                return str_starts_with($path, '/') ? $path : '/' . $path;
            }

            return null;
        }

        return str_starts_with($raw, '/') ? $raw : '/' . $raw;
    }
}
