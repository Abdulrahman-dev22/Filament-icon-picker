<?php

declare(strict_types=1);

use AbdulrahmanDev22\FilamentIconPicker\IconSets\Contracts\AcceptsUploads;
use AbdulrahmanDev22\FilamentIconPicker\IconSets\DiskIconSet;
use AbdulrahmanDev22\FilamentIconPicker\IconSets\IconSetRegistry;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;

beforeEach(function (): void {
    Storage::fake('icons');
    Storage::disk('icons')->put('icon-picker/star.svg', '<svg><path d="M1 1"/></svg>');
    Storage::disk('icons')->put('icon-picker/brand/logo.svg', '<svg><circle r="1"/></svg>');
    Storage::disk('icons')->put('icon-picker/notes.txt', 'ignored');
});

it('lists svg files on the disk, nested folders joined with a dot', function (): void {
    $set = new DiskIconSet(disk: 'icons', directory: 'icon-picker', key: 'uploads');

    expect($set)->toBeInstanceOf(AcceptsUploads::class)
        ->and($set->getIcons())->toBe(['brand.logo' => 'Brand Logo', 'star' => 'Star'])
        ->and($set->getLabel())->toBe('Uploaded Icons')
        ->and($set->getDisk())->toBe('icons')
        ->and($set->getDirectory())->toBe('icon-picker');
});

it('resolves icons through blade icons using the disk', function (): void {
    $set = new DiskIconSet(disk: 'icons', directory: 'icon-picker', key: 'diskicons');

    expect($set->getIcon('star'))->toBe('diskicons-star')
        ->and(svg('diskicons-star')->toHtml())->toContain('<svg')
        ->and(svg('diskicons-brand.logo')->toHtml())->toContain('<circle')
        ->and($set->getIcon('missing'))->toBeNull()
        ->and($set->getIcon('../star'))->toBeNull();
});

it('falls back to inline svg when the key cannot be a blade icons prefix', function (): void {
    $icon = (new DiskIconSet(disk: 'icons', directory: 'icon-picker', key: 'my-uploads'))->getIcon('star');

    expect($icon)->toBeInstanceOf(HtmlString::class)
        ->and($icon->toHtml())->toContain('<path');
});

it('stores and deletes icons', function (): void {
    $set = new DiskIconSet(disk: 'icons', directory: 'icon-picker', key: 'uploads');
    $set->getIcons();

    $set->storeIcon('bolt', '<svg><path d="M2 2"/></svg>');

    Storage::disk('icons')->assertExists('icon-picker/bolt.svg');
    expect($set->getIcons())->toHaveKeys(['bolt', 'brand.logo', 'star']);

    $set->deleteIcon('bolt');

    Storage::disk('icons')->assertMissing('icon-picker/bolt.svg');
    expect($set->getIcons())->not->toHaveKey('bolt');
});

it('uses the configured default disk and is buildable from config', function (): void {
    config()->set('filament-icon-picker.uploads.disk', 'icons');
    config()->set('filament-icon-picker.disk_icon_sets', [
        'uploads' => ['directory' => 'icon-picker', 'label' => 'Mine'],
    ]);

    $registry = IconSetRegistry::fromConfig(app(), config('filament-icon-picker'));

    expect(array_keys($registry->all()))->toBe(['heroicons', 'uploads'])
        ->and($registry->get('uploads'))->toBeInstanceOf(DiskIconSet::class)
        ->and($registry->get('uploads')->getDisk())->toBe('icons')
        ->and($registry->get('uploads')->getLabel())->toBe('Mine')
        ->and($registry->get('uploads')->getIcons())->toHaveKey('star');
});
