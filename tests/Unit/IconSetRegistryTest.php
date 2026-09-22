<?php

declare(strict_types=1);

use AbdulrahmanDev22\FilamentIconPicker\Facades\FilamentIconPicker;
use AbdulrahmanDev22\FilamentIconPicker\IconSets\CustomIconSet;
use AbdulrahmanDev22\FilamentIconPicker\IconSets\HeroiconsIconSet;
use AbdulrahmanDev22\FilamentIconPicker\IconSets\IconSet;
use AbdulrahmanDev22\FilamentIconPicker\IconSets\IconSetRegistry;
use AbdulrahmanDev22\FilamentIconPicker\Tests\TestCase;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Blade;

final class ProgrammaticIconSet implements IconSet
{
    public function getKey(): string
    {
        return 'programmatic';
    }

    public function getLabel(): string
    {
        return 'Programmatic';
    }

    public function getIcons(): array
    {
        return ['one' => 'One', 'two' => 'Two'];
    }

    public function getIcon(string $name): ?string
    {
        return isset($this->getIcons()[$name]) ? 'heroicon-o-hashtag' : null;
    }
}

it('registers heroicons by default', function (): void {
    $registry = app(IconSetRegistry::class);

    expect($registry->has('heroicons'))->toBeTrue()
        ->and($registry->get('heroicons'))->toBeInstanceOf(HeroiconsIconSet::class)
        ->and(array_keys($registry->all()))->toBe(['heroicons']);
});

it('can disable heroicons globally through config', function (): void {
    config()->set('filament-icon-picker.register_heroicons', false);

    expect(IconSetRegistry::fromConfig(app(), config('filament-icon-picker'))->all())->toBe([]);
});

it('builds custom sets and class-based sets from config in order', function (): void {
    config()->set('filament-icon-picker.custom_icon_sets', [
        'brand' => ['path' => TestCase::fixturePath('icons'), 'label' => 'Brand'],
        'shorthand' => TestCase::fixturePath('icons'),
    ]);
    config()->set('filament-icon-picker.icon_sets', [ProgrammaticIconSet::class]);

    $registry = IconSetRegistry::fromConfig(app(), config('filament-icon-picker'));

    expect(array_keys($registry->all()))->toBe(['heroicons', 'brand', 'shorthand', 'programmatic'])
        ->and($registry->get('brand'))->toBeInstanceOf(CustomIconSet::class)
        ->and($registry->get('brand')->getLabel())->toBe('Brand')
        ->and($registry->get('shorthand')->getPath())->toBe(TestCase::fixturePath('icons'))
        ->and($registry->get('programmatic'))->toBeInstanceOf(ProgrammaticIconSet::class);
});

it('registers, overrides and forgets sets at runtime', function (): void {
    FilamentIconPicker::register(ProgrammaticIconSet::class, new CustomIconSet(TestCase::fixturePath('icons'), 'icons'));

    expect(array_keys(FilamentIconPicker::all()))->toBe(['heroicons', 'programmatic', 'icons']);

    FilamentIconPicker::register(new HeroiconsIconSet(label: 'Replaced'));

    expect(array_keys(FilamentIconPicker::all()))->toBe(['heroicons', 'programmatic', 'icons'])
        ->and(FilamentIconPicker::get('heroicons')->getLabel())->toBe('Replaced');

    FilamentIconPicker::forget('programmatic');

    expect(FilamentIconPicker::has('programmatic'))->toBeFalse();
});

it('rejects classes that are not icon sets', function (): void {
    FilamentIconPicker::register(stdClass::class);
})->throws(InvalidArgumentException::class);

it('resolves and renders stored values', function (): void {
    FilamentIconPicker::register(new CustomIconSet(TestCase::fixturePath('icons'), 'my-icons'));

    expect(FilamentIconPicker::findSet('heroicons:o-academic-cap'))->toBeInstanceOf(HeroiconsIconSet::class)
        ->and(FilamentIconPicker::resolveIcon('heroicons:o-academic-cap'))->toBe('heroicon-o-academic-cap')
        ->and(FilamentIconPicker::resolveIcon('my-icons:star'))->toBeInstanceOf(Htmlable::class)
        ->and(FilamentIconPicker::resolveIcon('unknown:star'))->toBeNull()
        ->and(FilamentIconPicker::resolveIcon(null))->toBeNull()
        ->and(FilamentIconPicker::render('heroicons:o-academic-cap', ['class' => 'h-5 w-5'])->toHtml())->toContain('<svg', 'class="h-5 w-5"')
        ->and(FilamentIconPicker::render('my-icons:star', ['class' => 'h-5 w-5'])->toHtml())->toContain('<svg class="h-5 w-5"')
        ->and(FilamentIconPicker::render('heroicons:o-nope'))->toBeNull();
});

it('renders through the blade component', function (): void {
    FilamentIconPicker::register(new CustomIconSet(TestCase::fixturePath('icons'), 'my-icons'));

    $html = Blade::render(
        '<x-filament-icon-picker::icon :icon="$icon" class="h-4 w-4" data-test="1" />',
        ['icon' => 'my-icons:bolt'],
    );

    expect($html)->toContain('<svg class="h-4 w-4" data-test="1"');
});
