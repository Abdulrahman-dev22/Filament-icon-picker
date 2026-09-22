<?php

declare(strict_types=1);

namespace AbdulrahmanDev22\FilamentIconPicker\Support;

/**
 * How an IconPicker writes its value to the model. Whatever the format, the
 * picker itself always works with an IconReference internally and converts
 * on hydration / dehydration.
 */
enum IconStorageFormat: string
{
    /** "brand:star" – compact, unambiguous, resolvable through the facade. */
    case Reference = 'reference';

    /** "heroicon-o-academic-cap" / "brand-star" – usable directly in `->icon()` and `@svg()`. */
    case BladeIcon = 'blade_icon';

    /** ['set' => …, 'name' => …, 'label' => …, 'blade_icon' => …, 'svg' => …, 'url' => …] – needs a JSON column with an array cast. */
    case Array = 'array';

    /** The same structure as Array, JSON-encoded into a string column. */
    case Json = 'json';

    /** The raw "<svg …>…</svg>" markup – self-contained for API clients, but heavier. */
    case Svg = 'svg';

    public static function fromValue(self|string $format): self
    {
        return $format instanceof self ? $format : self::from($format);
    }
}
