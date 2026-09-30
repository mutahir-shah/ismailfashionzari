<?php

namespace App\Helpers;

class PaletteGenerator
{

    /**
     * Generate a full modern palette from a base 500 color
     */
    public static function generateModernPalette(string $hex500): array
    {
        [$h, $s, $l] = self::hexToHsl($hex500);

        // Perceptual lightness ramp (UI-tuned)
        $lightnessRamp = [
            50  => $l + 0.28,
            100 => $l + 0.20,
            200 => $l + 0.16,
            300 => $l + 0.06,
            400 => $l + 0.02,
            500 => $l,
            600 => $l - 0.1,
            700 => $l - 0.14,
            800 => $l - 0.25,
            900 => $l - 0.40,
        ];

        $palette = [];

        foreach ($lightnessRamp as $shade => $targetL) {

            // Saturation curve (prevents chalky lights & muddy darks)
            if ($shade <= 100) {
                $sat = $s * 0.35;
            } elseif ($shade <= 300) {
                $sat = $s * 0.65;
            } elseif ($shade <= 600) {
                $sat = $s;
            } elseif ($shade <= 800) {
                $sat = $s * 0.90;
            } else {
                $sat = $s * 0.80;
            }

            // Subtle hue drift for depth (Tailwind-like)
            $hueShift = match (true) {
                $shade >= 800 => 1.5,
                $shade >= 600 => 1.0,
                $shade <= 100 => -0.8,
                default => 0.0,
            };

            $palette[$shade] = self::hslToHex(
                $h + $hueShift,
                self::clamp($sat),
                self::clamp($targetL)
            );
        }

        return $palette;
    }

    /**
     * Convert HEX → HSL
     */
    public static function hexToHsl(string $hex): array
    {
        $hex = ltrim($hex, '#');

        if (strlen($hex) !== 6) {
            throw new \InvalidArgumentException('Invalid HEX color.');
        }

        $r = hexdec(substr($hex, 0, 2)) / 255;
        $g = hexdec(substr($hex, 2, 2)) / 255;
        $b = hexdec(substr($hex, 4, 2)) / 255;

        $max = max($r, $g, $b);
        $min = min($r, $g, $b);
        $delta = $max - $min;

        $l = ($max + $min) / 2;

        if ($delta == 0) {
            $h = 0;
            $s = 0;
        } else {
            $s = $delta / (1 - abs(2 * $l - 1));

            switch ($max) {
                case $r:
                    $h = 60 * fmod((($g - $b) / $delta), 6);
                    break;
                case $g:
                    $h = 60 * ((($b - $r) / $delta) + 2);
                    break;
                default:
                    $h = 60 * ((($r - $g) / $delta) + 4);
            }
        }

        if ($h < 0) $h += 360;

        return [$h, $s, $l];
    }

    /**
     * Convert HSL → HEX
     */
    public static function hslToHex(float $h, float $s, float $l): string
    {
        $c = (1 - abs(2 * $l - 1)) * $s;
        $x = $c * (1 - abs(fmod($h / 60, 2) - 1));
        $m = $l - $c / 2;

        if ($h < 60) {
            [$r, $g, $b] = [$c, $x, 0];
        } elseif ($h < 120) {
            [$r, $g, $b] = [$x, $c, 0];
        } elseif ($h < 180) {
            [$r, $g, $b] = [0, $c, $x];
        } elseif ($h < 240) {
            [$r, $g, $b] = [0, $x, $c];
        } elseif ($h < 300) {
            [$r, $g, $b] = [$x, 0, $c];
        } else {
            [$r, $g, $b] = [$c, 0, $x];
        }

        return sprintf(
            '#%02x%02x%02x',
            round(($r + $m) * 255),
            round(($g + $m) * 255),
            round(($b + $m) * 255)
        );
    }

    /**
     * Clamp value between 0 and 1
     */
    public static function clamp(float $value): float
    {
        return max(0.0, min(1.0, $value));
    }
}
