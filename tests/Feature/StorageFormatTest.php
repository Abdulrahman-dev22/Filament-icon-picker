<?php

declare(strict_types=1);

use AbdulrahmanDev22\FilamentIconPicker\Facades\FilamentIconPicker;
use AbdulrahmanDev22\FilamentIconPicker\Forms\Components\IconPicker;
use AbdulrahmanDev22\FilamentIconPicker\IconSets\CustomIconSet;
use AbdulrahmanDev22\FilamentIconPicker\Support\IconStorageFormat;
use AbdulrahmanDev22\FilamentIconPicker\Tests\Fixtures\Livewire\IconPickerFormV3;
use AbdulrahmanDev22\FilamentIconPicker\Tests\Fixtures\Livewire\IconPickerFormV4;
use AbdulrahmanDev22\FilamentIconPicker\Tests\TestCase;
use Livewire\Livewire;

function storageForm(): string
{
    return TestCase::isFilamentV3() ? IconPickerFormV3::class : IconPickerFormV4::class;
}

it('defaults to the reference format from config', function (): void {
    expect(IconPicker::make('icon')->getStorageFormat())->toBe(IconStorageFormat::Reference);

    config()->set('filament-icon-picker.storage_format', 'blade_icon');

    expect(IconPicker::make('icon')->getStorageFormat())->toBe(IconStorageFormat::BladeIcon)
        ->and(IconPicker::make('icon')->storeAsSvg()->getStorageFormat())->toBe(IconStorageFormat::Svg)
        ->and(IconPicker::make('icon')->storeAs('json')->getStorageFormat())->toBe(IconStorageFormat::Json);
});

it('hydrates any stored format into a reference and dehydrates back', function (string $format, string $stored, string $expectedSaved): void {
    $component = Livewire::test(storageForm(), ['initialIcon' => $stored, 'storageFormat' => $format])
        ->assertSet('data.icon', 'icons:star')
        ->call('save')
        ->assertHasNoErrors();

    $saved = $component->get('saved')['icon'];

    expect($format === 'json' ? json_decode($saved, true)['blade_icon'] : $saved)->toBe($expectedSaved);
})->with([
    'reference' => ['reference', 'icons:star', 'icons:star'],
    'blade icon' => ['blade_icon', 'icons-star', 'icons-star'],
    'blade icon stored as reference' => ['blade_icon', 'icons:star', 'icons-star'],
    'reference stored as blade icon' => ['reference', 'icons-star', 'icons:star'],
    'json' => ['json', '{"set":"icons","name":"star"}', 'icons-star'],
]);

it('dehydrates the array format as an array', function (): void {
    $component = Livewire::test(storageForm(), ['initialIcon' => 'icons:star', 'storageFormat' => 'array'])
        ->call('save')
        ->assertHasNoErrors();

    expect($component->get('saved')['icon'])->toMatchArray(['set' => 'icons', 'name' => 'star', 'label' => 'Star', 'blade_icon' => 'icons-star']);
});

it('handles the svg format end to end', function (): void {
    $svg = file_get_contents(TestCase::fixturePath('icons/star.svg'));

    $component = Livewire::test(storageForm(), ['initialIcon' => $svg, 'storageFormat' => 'svg'])
        ->assertSet('data.icon', 'icons:star')
        ->call('save')
        ->assertHasNoErrors();

    expect($component->get('saved')['icon'])->toStartWith('<svg');
});

it('exposes an api friendly array through the facade', function (): void {
    FilamentIconPicker::register(new CustomIconSet(TestCase::fixturePath('icons'), 'icons'));

    $data = FilamentIconPicker::toArray('icons:star');

    expect($data)->toMatchArray(['set' => 'icons', 'name' => 'star', 'label' => 'Star', 'blade_icon' => 'icons-star', 'url' => null])
        ->and($data['svg'])->toStartWith('<svg')
        ->and(FilamentIconPicker::toArray('icons-star')['name'])->toBe('star')
        ->and(FilamentIconPicker::toArray(['set' => 'icons', 'name' => 'bolt'])['name'])->toBe('bolt')
        ->and(FilamentIconPicker::toArray('icons:nope'))->toBeNull()
        ->and(FilamentIconPicker::url('icons:star'))->toBeNull();
});
