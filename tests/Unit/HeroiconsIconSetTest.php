<?php

declare(strict_types=1);

use AbdulrahmanDev22\FilamentIconPicker\IconSets\HeroiconsIconSet;

it('lists outline heroicons by default', function (): void {
    $icons = (new HeroiconsIconSet)->getIcons();

    expect($icons)->not->toBeEmpty()
        ->and($icons)->toHaveKey('o-academic-cap')
        ->and($icons['o-academic-cap'])->toBe('Academic Cap')
        ->and(array_keys($icons))->each->toStartWith('o-');
});

it('lists the requested styles in order and labels them', function (): void {
    $icons = (new HeroiconsIconSet(styles: ['s', 'o']))->getIcons();
    $keys = array_keys($icons);

    expect($keys[0])->toStartWith('s-')
        ->and(end($keys))->toStartWith('o-')
        ->and($icons['s-academic-cap'])->toBe('Academic Cap (Solid)')
        ->and($icons['o-academic-cap'])->toBe('Academic Cap (Outline)');
});

it('reads its styles from the config when none are given', function (): void {
    config()->set('filament-icon-picker.heroicons.styles', ['m']);

    expect(array_keys((new HeroiconsIconSet)->getIcons()))->each->toStartWith('m-');
});

it('ignores unknown styles', function (): void {
    expect((new HeroiconsIconSet(styles: ['x', 'c']))->getStyles())->toBe(['c']);
});

it('resolves icons to blade icon names', function (): void {
    $set = new HeroiconsIconSet;

    expect($set->getIcon('o-academic-cap'))->toBe('heroicon-o-academic-cap')
        ->and($set->getIcon('s-academic-cap'))->toBe('heroicon-s-academic-cap')
        ->and($set->getIcon('o-does-not-exist'))->toBeNull()
        ->and($set->getIcon('../../etc/passwd'))->toBeNull();
});

it('has a stable key and configurable label', function (): void {
    expect((new HeroiconsIconSet)->getKey())->toBe('heroicons')
        ->and((new HeroiconsIconSet)->getLabel())->toBe('Heroicons')
        ->and((new HeroiconsIconSet(label: 'Standard'))->getLabel())->toBe('Standard');
});
