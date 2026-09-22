<?php

declare(strict_types=1);

namespace AbdulrahmanDev22\FilamentIconPicker\Support;

use BladeUI\Icons\Exceptions\SvgNotFound;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;
use Illuminate\View\ComponentAttributeBag;

/**
 * Renders what an IconSet::getIcon() returns as inline SVG markup, whichever
 * shape it has (Blade Icons name or raw SVG Htmlable).
 */
final class IconRenderer
{
    /**
     * @param  array<string, mixed>  $attributes  HTML attributes for the `<svg>` element.
     */
    public static function render(string|Htmlable|null $icon, array $attributes = []): ?Htmlable
    {
        if ($icon === null || $icon === '') {
            return null;
        }

        if ($icon instanceof Htmlable) {
            return self::renderRawSvg($icon->toHtml(), $attributes);
        }

        try {
            return svg($icon, $attributes['class'] ?? '', array_diff_key($attributes, ['class' => true]));
        } catch (SvgNotFound) {
            return null;
        }
    }

    /**
     * Inject attributes into the opening `<svg>` tag of raw markup. Injected
     * attributes come first, so they win over duplicates already in the file
     * (the same behaviour Blade Icons has).
     *
     * @param  array<string, mixed>  $attributes
     */
    private static function renderRawSvg(string $markup, array $attributes): ?HtmlString
    {
        $markup = trim($markup);

        if ($markup === '' || ! preg_match('/<svg\b/i', $markup)) {
            return null;
        }

        $attributes = array_filter($attributes, fn (mixed $value): bool => $value !== null && $value !== false && $value !== '');

        if ($attributes === []) {
            return new HtmlString($markup);
        }

        $rendered = (new ComponentAttributeBag($attributes))->toHtml();

        return new HtmlString((string) preg_replace('/<svg\b/i', "<svg {$rendered}", $markup, 1));
    }
}
