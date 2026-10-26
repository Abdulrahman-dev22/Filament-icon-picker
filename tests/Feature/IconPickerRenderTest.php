<?php

declare(strict_types=1);

use AbdulrahmanDev22\FilamentIconPicker\Tests\Fixtures\Livewire\IconPickerFormV3;
use AbdulrahmanDev22\FilamentIconPicker\Tests\Fixtures\Livewire\IconPickerFormV4;
use AbdulrahmanDev22\FilamentIconPicker\Tests\TestCase;
use Livewire\Livewire;

function iconPickerForm(): string
{
    return TestCase::isFilamentV3() ? IconPickerFormV3::class : IconPickerFormV4::class;
}

it('renders a tab per icon set with a searchable grid', function (): void {
    Livewire::test(iconPickerForm())
        ->assertSee('Heroicons')
        ->assertSee('Custom Icons')
        ->assertSeeHtml('class="fi-icon-picker')
        ->assertSeeHtml('data-icon-picker-icon="heroicons:o-academic-cap"')
        ->assertSeeHtml('data-icon-picker-icon="icons:star"')
        ->assertSeeHtml('data-icon-picker-icon="icons:brand.logo"')
        ->assertSeeHtml('type="search"')
        ->assertSeeHtml('x-model.debounce.')
        ->assertDontSeeHtml('<x-filament::')
        ->assertDontSeeHtml('$getSearchPrompt')
        ->assertSeeHtml('--fi-icon-picker-columns-default: 4; --fi-icon-picker-columns-sm: 6; --fi-icon-picker-columns-lg: 8;');
});

it('keeps the alpine scope independent of the field state', function (bool $dropdown): void {
    $xData = function (?string $initialIcon) use ($dropdown): string {
        $html = Livewire::test(iconPickerForm(), ['initialIcon' => $initialIcon, 'dropdown' => $dropdown])
            ->assertSet('data.icon', $initialIcon)
            ->html();

        preg_match('/x-data="(\{\s*state: \$wire\..*?)"\s+class="fi-icon-picker/s', $html, $matches);

        return $matches[1] ?? '';
    };

    $withIcon = $xData('icons:star');

    expect($withIcon)->not->toBe('')
        ->and($withIcon)->toBe($xData(null))
        ->and($withIcon)->toBe($xData('heroicons:o-academic-cap'));
})->with([
    'inline' => [false],
    'dropdown' => [true],
]);

it('renders the grid inline by default', function (): void {
    Livewire::test(iconPickerForm())
        ->assertSeeHtml('data-icon-picker-icon="icons:star"')
        ->assertDontSeeHtml('fi-dropdown')
        ->assertSeeHtml('closeOnSelect: false');
});

it('renders the grid inside a dropdown panel in dropdown mode', function (): void {
    Livewire::test(iconPickerForm(), ['dropdown' => true, 'closeOnSelect' => true])
        ->assertSeeHtmlInOrder([
            'class="fi-icon-picker-selection fi-has-dropdown"',
            'fi-icon-picker-dropdown-trigger',
            'fi-dropdown-panel',
            'class="fi-icon-picker-panel"',
            'fi-icon-picker-tabs',
            'type="search"',
            'data-icon-picker-icon="icons:star"',
        ])
        // The open panel must survive Livewire morphs, e.g. on a live() field.
        ->assertSeeHtml('wire:ignore.self')
        ->assertSeeHtml('closeOnSelect: true');
});

it('disables the dropdown trigger of a disabled field', function (bool $disabled): void {
    $html = Livewire::test(iconPickerForm(), ['dropdown' => true, 'disabled' => $disabled])->html();

    expect((bool) preg_match('/<button\s+type="button"\s+disabled\s+class="fi-icon-picker-dropdown-trigger"/', $html))->toBe($disabled);
})->with([
    'enabled' => [false],
    'disabled' => [true],
]);

it('round-trips a valid selection through the form', function (): void {
    Livewire::test(iconPickerForm())
        ->set('data.icon', 'heroicons:o-academic-cap')
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('data.icon', 'heroicons:o-academic-cap')
        ->set('data.icon', 'icons:brand.logo')
        ->call('save')
        ->assertHasNoErrors()
        ->set('data.icon', null)
        ->call('save')
        ->assertHasNoErrors();
});

it('rejects values that are not offered by the field', function (string $value): void {
    Livewire::test(iconPickerForm())
        ->set('data.icon', $value)
        ->call('save')
        ->assertHasErrors(['data.icon']);
})->with([
    'unknown set' => ['nope:star'],
    'unknown icon' => ['icons:missing'],
    'style not enabled' => ['heroicons:s-academic-cap'],
    'no set prefix' => ['o-academic-cap'],
]);
