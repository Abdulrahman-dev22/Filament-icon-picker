<?php

declare(strict_types=1);

namespace AbdulrahmanDev22\FilamentIconPicker\Support;

use Filament\Support\Facades\FilamentColor;

/**
 * Turns a shade from Filament's colour registry into a value that is valid
 * inside a CSS custom property, whatever format the installed Filament
 * version uses (Filament 3 stores "r, g, b" channel triplets, Filament 4+
 * stores complete colour functions such as "oklch(…)").
 */
final class CssColor
{
    public static function fromFilament(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        $value = trim($value);

        if (preg_match('/^\d{1,3}\s*[, ]\s*\d{1,3}\s*[, ]\s*\d{1,3}$/', $value)) {
            return "rgb({$value})";
        }

        return $value;
    }

    /**
     * A shade of a registered Filament colour (e.g. "primary", 600) as CSS.
     */
    public static function shade(string $color, int $shade): ?string
    {
        $shades = FilamentColor::getColors()[$color] ?? [];

        $value = $shades[$shade] ?? $shades[(string) $shade] ?? null;

        return self::fromFilament(is_string($value) ? $value : null);
    }
}
