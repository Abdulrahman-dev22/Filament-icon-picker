<?php

declare(strict_types=1);

use AbdulrahmanDev22\FilamentIconPicker\Facades\FilamentIconPicker;
use AbdulrahmanDev22\FilamentIconPicker\IconSets\CustomIconSet;
use AbdulrahmanDev22\FilamentIconPicker\Tests\TestCase;
use BladeUI\Icons\Factory;
use Illuminate\Support\Facades\File;
use Illuminate\Support\HtmlString;

it('turns every svg file in the directory into an icon', function (): void {
    $icons = (new CustomIconSet(TestCase::fixturePath('icons'), 'icons'))->getIcons();

    expect($icons)->toBe([
        'bolt' => 'Bolt',
        'brand.logo' => 'Brand Logo',
        'star' => 'Star',
    ]);
});

it('uses the given key and label', function (): void {
    $set = new CustomIconSet(TestCase::fixturePath('icons'), 'brand', 'Brand Icons');

    expect($set->getKey())->toBe('brand')
        ->and($set->getLabel())->toBe('Brand Icons')
        ->and($set->getPath())->toBe(TestCase::fixturePath('icons'));
});

it('falls back to a translated default label', function (): void {
    expect((new CustomIconSet(TestCase::fixturePath('icons')))->getLabel())->toBe('Custom Icons');
});

it('rejects keys that are not safe to store', function (): void {
    new CustomIconSet(TestCase::fixturePath('icons'), 'not:allowed');
})->throws(InvalidArgumentException::class);

it('returns no icons for a missing directory', function (): void {
    expect((new CustomIconSet('/definitely/not/here', 'gone'))->getIcons())->toBe([]);
});

it('registers itself with blade icons so the value works as a plain icon name', function (): void {
    $set = new CustomIconSet(TestCase::fixturePath('icons'), 'fixtureicons');

    expect($set->getIcon('star'))->toBe('fixtureicons-star')
        ->and($set->getIcon('brand.logo'))->toBe('fixtureicons-brand.logo')
        ->and(app(Factory::class)->all())->toHaveKey('fixtureicons')
        ->and(svg('fixtureicons-star')->toHtml())->toContain('<svg');
});

it('renders inline svg when the key cannot be a blade icons prefix', function (): void {
    $set = new CustomIconSet(TestCase::fixturePath('icons'), 'my-icons');

    $icon = $set->getIcon('star');

    expect($icon)->toBeInstanceOf(HtmlString::class)
        ->and($icon->toHtml())->toContain('<svg')
        ->and(app(Factory::class)->all())->not->toHaveKey('my-icons');
});

it('returns null for unknown or unsafe icon names', function (): void {
    $set = new CustomIconSet(TestCase::fixturePath('icons'), 'icons');

    expect($set->getIcon('missing'))->toBeNull()
        ->and($set->getIcon('../icons/star'))->toBeNull()
        ->and($set->getIcon('brand/logo'))->toBeNull()
        ->and($set->getIcon(''))->toBeNull();
});

it('caches the icon list when caching is enabled and can be cleared', function (): void {
    config()->set('filament-icon-picker.cache.enabled', true);

    $directory = sys_get_temp_dir().'/filament-icon-picker-'.uniqid();
    File::ensureDirectoryExists($directory);
    File::copy(TestCase::fixturePath('icons/star.svg'), "{$directory}/star.svg");

    try {
        expect((new CustomIconSet($directory, 'tmp'))->getIcons())->toHaveKeys(['star']);

        File::copy(TestCase::fixturePath('icons/bolt.svg'), "{$directory}/bolt.svg");

        expect((new CustomIconSet($directory, 'tmp'))->getIcons())->toHaveKeys(['star'])
            ->not->toHaveKey('bolt');

        FilamentIconPicker::clearCache();

        expect((new CustomIconSet($directory, 'tmp'))->getIcons())->toHaveKeys(['bolt', 'star']);
    } finally {
        File::deleteDirectory($directory);
    }
});

it('does not cache when caching is disabled', function (): void {
    config()->set('filament-icon-picker.cache.enabled', false);

    $directory = sys_get_temp_dir().'/filament-icon-picker-'.uniqid();
    File::ensureDirectoryExists($directory);
    File::copy(TestCase::fixturePath('icons/star.svg'), "{$directory}/star.svg");

    try {
        expect((new CustomIconSet($directory, 'tmp'))->getIcons())->toHaveCount(1);

        File::copy(TestCase::fixturePath('icons/bolt.svg'), "{$directory}/bolt.svg");

        expect((new CustomIconSet($directory, 'tmp'))->getIcons())->toHaveCount(2);
    } finally {
        File::deleteDirectory($directory);
    }
});
