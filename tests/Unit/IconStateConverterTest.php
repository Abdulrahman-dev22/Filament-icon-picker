<?php

declare(strict_types=1);

use AbdulrahmanDev22\FilamentIconPicker\IconSets\CustomIconSet;
use AbdulrahmanDev22\FilamentIconPicker\IconSets\HeroiconsIconSet;
use AbdulrahmanDev22\FilamentIconPicker\Support\IconReference;
use AbdulrahmanDev22\FilamentIconPicker\Support\IconStateConverter;
use AbdulrahmanDev22\FilamentIconPicker\Support\IconStorageFormat;
use AbdulrahmanDev22\FilamentIconPicker\Tests\TestCase;

function converter(): IconStateConverter
{
    return IconStateConverter::for([
        'heroicons' => new HeroiconsIconSet,
        'icons' => new CustomIconSet(TestCase::fixturePath('icons'), 'icons'),
        'my-icons' => new CustomIconSet(TestCase::fixturePath('icons'), 'my-icons'),
    ]);
}

it('formats a reference in every storage format', function (): void {
    $reference = IconReference::make('heroicons', 'o-academic-cap');
    $converter = converter();

    expect($converter->fromReference($reference, IconStorageFormat::Reference))->toBe('heroicons:o-academic-cap')
        ->and($converter->fromReference($reference, IconStorageFormat::BladeIcon))->toBe('heroicon-o-academic-cap')
        ->and($converter->fromReference($reference, IconStorageFormat::Svg))->toStartWith('<svg')
        ->and($converter->fromReference($reference, IconStorageFormat::Array))->toMatchArray([
            'set' => 'heroicons',
            'name' => 'o-academic-cap',
            'label' => 'Academic Cap',
            'blade_icon' => 'heroicon-o-academic-cap',
            'url' => null,
        ])
        ->and(json_decode($converter->fromReference($reference, IconStorageFormat::Json), true)['name'])->toBe('o-academic-cap')
        ->and($converter->fromReference(IconReference::make('icons', 'star'), IconStorageFormat::BladeIcon))->toBe('icons-star')
        ->and($converter->fromReference(IconReference::make('my-icons', 'star'), IconStorageFormat::BladeIcon))->toBeNull()
        ->and($converter->fromReference(IconReference::make('my-icons', 'star'), IconStorageFormat::Svg))->toContain('<svg')
        ->and($converter->fromReference(IconReference::make('icons', 'missing'), IconStorageFormat::Reference))->toBeNull()
        ->and($converter->fromReference(null, IconStorageFormat::Reference))->toBeNull();
});

it('reads a reference back from every storage format', function (): void {
    $converter = converter();
    $reference = IconReference::make('icons', 'star');

    foreach (IconStorageFormat::cases() as $format) {
        $stored = $converter->fromReference($reference, $format);

        expect($converter->toReference($stored)?->toString())->toBe('icons:star', "format {$format->value}");
    }

    // Inline SVG from a set that cannot register with Blade Icons.
    $svg = $converter->fromReference(IconReference::make('my-icons', 'bolt'), IconStorageFormat::Svg);

    expect($converter->toReference($svg)?->toString())->toBeIn(['icons:bolt', 'my-icons:bolt']);
});

it('accepts partial arrays and ignores unknown values', function (): void {
    $converter = converter();

    expect($converter->toReference(['set' => 'heroicons', 'name' => 'o-bell'])?->toString())->toBe('heroicons:o-bell')
        ->and($converter->toReference(['icon' => 'heroicons:o-bell'])?->toString())->toBe('heroicons:o-bell')
        ->and($converter->toReference(['blade_icon' => 'heroicon-o-bell'])?->toString())->toBe('heroicons:o-bell')
        ->and($converter->toReference('heroicon-o-bell')?->toString())->toBe('heroicons:o-bell')
        ->and($converter->toReference('{"set":"icons","name":"star"}')?->toString())->toBe('icons:star')
        ->and($converter->toReference('heroicons:o-nope'))->toBeNull()
        ->and($converter->toReference('heroicon-o-nope'))->toBeNull()
        ->and($converter->toReference('<svg></svg>'))->toBeNull()
        ->and($converter->toReference(['set' => 'x']))->toBeNull()
        ->and($converter->toReference(''))->toBeNull()
        ->and($converter->toReference(null))->toBeNull()
        ->and($converter->toReference(42))->toBeNull();
});
