<?php

declare(strict_types=1);

namespace AbdulrahmanDev22\FilamentIconPicker\IconSets;

use Illuminate\Contracts\Support\Htmlable;

/**
 * A named collection of icons that the picker renders as one tab.
 *
 * Implement this interface and register the class in the
 * `filament-icon-picker.icon_sets` config array (or pass an instance to
 * `IconPicker::icons()`) to add a completely custom icon source.
 */
interface IconSet
{
    /**
     * Unique key identifying the set. It is stored as the first half of the
     * field state ("key:icon-name"), so it must never contain a colon.
     * Lowercase letters, digits, dashes and underscores are recommended.
     */
    public function getKey(): string;

    /**
     * Human readable label used for the tab.
     */
    public function getLabel(): string;

    /**
     * All icons in the set.
     *
     * @return array<string, string> icon name => human readable label
     */
    public function getIcons(): array;

    /**
     * Something Filament can render for the given icon name: either a Blade
     * Icons name (e.g. "heroicon-o-academic-cap") or an Htmlable containing
     * raw SVG markup. Return null when the icon does not exist.
     */
    public function getIcon(string $name): string|Htmlable|null;
}
