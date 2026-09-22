<?php

declare(strict_types=1);

use AbdulrahmanDev22\FilamentIconPicker\Facades\FilamentIconPicker;
use AbdulrahmanDev22\FilamentIconPicker\Forms\Components\IconPicker;
use AbdulrahmanDev22\FilamentIconPicker\IconSets\CustomIconSet;
use AbdulrahmanDev22\FilamentIconPicker\IconSets\HeroiconsIconSet;
use AbdulrahmanDev22\FilamentIconPicker\Tests\TestCase;

it('starts from the global registry', function (): void {
    expect(array_keys(IconPicker::make('icon')->getIconSets()))->toBe(['heroicons']);
});

it('adds a custom path as an extra tab', function (): void {
    $field = IconPicker::make('icon')->customIconsPath(TestCase::fixturePath('icons'));

    $sets = $field->getIconSets();

    expect(array_keys($sets))->toBe(['heroicons', 'custom'])
        ->and($sets['custom'])->toBeInstanceOf(CustomIconSet::class)
        ->and($sets['custom']->getLabel())->toBe('Custom Icons');
});

it('supports several custom paths with their own keys and labels', function (): void {
    $field = IconPicker::make('icon')
        ->customIconsPath(TestCase::fixturePath('icons'), 'brand', 'Brand')
        ->customIconsPath(fn (): string => TestCase::fixturePath('icons/brand'), 'logos', fn (): string => 'Logos');

    $sets = $field->getIconSets();

    expect(array_keys($sets))->toBe(['heroicons', 'brand', 'logos'])
        ->and($sets['brand']->getLabel())->toBe('Brand')
        ->and($sets['logos']->getLabel())->toBe('Logos')
        ->and($sets['logos']->getIcons())->toBe(['logo' => 'Logo']);
});

it('excludes globally registered sets', function (): void {
    FilamentIconPicker::register(new CustomIconSet(TestCase::fixturePath('icons'), 'global'));

    expect(array_keys(IconPicker::make('icon')->withoutIconSet('heroicons')->getIconSets()))->toBe(['global'])
        ->and(array_keys(IconPicker::make('icon')->withoutHeroicons()->getIconSets()))->toBe(['global'])
        ->and(array_keys(IconPicker::make('icon')->withoutIconSet('heroicons', 'global')->getIconSets()))->toBe([]);
});

it('registers and overrides sets per field', function (): void {
    $field = IconPicker::make('icon')->icons([
        new HeroiconsIconSet(styles: ['s'], label: 'Solid only'),
        new CustomIconSet(TestCase::fixturePath('icons'), 'icons'),
    ]);

    $sets = $field->getIconSets();

    expect(array_keys($sets))->toBe(['heroicons', 'icons'])
        ->and($sets['heroicons']->getLabel())->toBe('Solid only')
        ->and(FilamentIconPicker::get('heroicons')->getLabel())->toBe('Heroicons');
});

it('can ignore the global registry entirely', function (): void {
    $only = IconPicker::make('icon')->icons([new CustomIconSet(TestCase::fixturePath('icons'), 'icons')], merge: false);
    $none = IconPicker::make('icon')->withoutGlobalIconSets();

    expect(array_keys($only->getIconSets()))->toBe(['icons'])
        ->and($none->getIconSets())->toBe([]);
});

it('knows which values it can offer', function (): void {
    $field = IconPicker::make('icon')->customIconsPath(TestCase::fixturePath('icons'), 'icons');

    expect($field->hasIcon('heroicons:o-academic-cap'))->toBeTrue()
        ->and($field->hasIcon('icons:star'))->toBeTrue()
        ->and($field->hasIcon('icons:missing'))->toBeFalse()
        ->and($field->hasIcon('heroicons:s-academic-cap'))->toBeFalse()
        ->and($field->hasIcon('nope:star'))->toBeFalse()
        ->and($field->hasIcon('star'))->toBeFalse()
        ->and($field->hasIcon(null))->toBeFalse();
});

it('is searchable and deselectable by default', function (): void {
    $field = IconPicker::make('icon');

    expect($field->isSearchable())->toBeTrue()
        ->and($field->isDeselectable())->toBeTrue()
        ->and(IconPicker::make('icon')->searchable(false)->isSearchable())->toBeFalse()
        ->and(IconPicker::make('icon')->deselectable(false)->isDeselectable())->toBeFalse();
});

it('normalises grid columns', function (): void {
    expect(IconPicker::make('icon')->getGridColumns())->toBe(['default' => 4, 'sm' => 6, 'lg' => 8])
        ->and(IconPicker::make('icon')->columns(10)->getGridColumns())->toBe(['default' => 10])
        ->and(IconPicker::make('icon')->columns(['md' => 6, 'xl' => 12])->getGridColumns())->toBe(['md' => 6, 'xl' => 12, 'default' => 1])
        ->and(IconPicker::make('icon')->columns(fn (): int => 3)->getGridColumns())->toBe(['default' => 3])
        ->and(IconPicker::make('icon')->columns(5)->gridMaxHeight('30rem')->getGridStyle())
        ->toStartWith('--fi-icon-picker-columns-default: 5; --fi-icon-picker-max-height: 30rem; --fi-icon-picker-primary-light: ')
        ->toContain('--fi-icon-picker-primary-dark: ', '--fi-icon-picker-primary-soft-light: ');
});

it('rejects invalid grid columns', function (): void {
    IconPicker::make('icon')->columns(['huge' => 4])->getGridColumns();
})->throws(InvalidArgumentException::class);
