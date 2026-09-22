<?php

declare(strict_types=1);

namespace AbdulrahmanDev22\FilamentIconPicker\Facades;

use AbdulrahmanDev22\FilamentIconPicker\IconSets\IconSetRegistry;
use Illuminate\Support\Facades\Facade;

/**
 * @method static IconSetRegistry register(\AbdulrahmanDev22\FilamentIconPicker\IconSets\IconSet|string ...$sets)
 * @method static IconSetRegistry forget(string $key)
 * @method static bool has(string $key)
 * @method static \AbdulrahmanDev22\FilamentIconPicker\IconSets\IconSet|null get(string $key)
 * @method static array<string, \AbdulrahmanDev22\FilamentIconPicker\IconSets\IconSet> all()
 * @method static \AbdulrahmanDev22\FilamentIconPicker\IconSets\IconSet|null findSet(\AbdulrahmanDev22\FilamentIconPicker\Support\IconReference|string|null $icon)
 * @method static string|\Illuminate\Contracts\Support\Htmlable|null resolveIcon(\AbdulrahmanDev22\FilamentIconPicker\Support\IconReference|string|null $icon)
 * @method static \Illuminate\Contracts\Support\Htmlable|null render(\AbdulrahmanDev22\FilamentIconPicker\Support\IconReference|string|null $icon, array<string, mixed> $attributes = [])
 * @method static array{set: string, name: string, label: string|null, blade_icon: string|null, svg: string|null, url: string|null}|null toArray(\AbdulrahmanDev22\FilamentIconPicker\Support\IconReference|string|array|null $icon)
 * @method static string|null url(\AbdulrahmanDev22\FilamentIconPicker\Support\IconReference|string|array|null $icon)
 * @method static void clearCache()
 *
 * @see IconSetRegistry
 */
class FilamentIconPicker extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return IconSetRegistry::class;
    }
}
