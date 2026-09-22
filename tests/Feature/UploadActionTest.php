<?php

declare(strict_types=1);

use AbdulrahmanDev22\FilamentIconPicker\Forms\Components\IconPicker;
use AbdulrahmanDev22\FilamentIconPicker\IconSets\DiskIconSet;
use AbdulrahmanDev22\FilamentIconPicker\Tests\Fixtures\Livewire\IconPickerFormV3;
use AbdulrahmanDev22\FilamentIconPicker\Tests\Fixtures\Livewire\IconPickerFormV4;
use AbdulrahmanDev22\FilamentIconPicker\Tests\TestCase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

function uploadForm(): string
{
    return TestCase::isFilamentV3() ? IconPickerFormV3::class : IconPickerFormV4::class;
}

beforeEach(fn () => Storage::fake('icons'));

it('is not uploadable until a set accepting uploads is available', function (): void {
    expect(IconPicker::make('icon')->uploadable()->isUploadable())->toBeFalse()
        ->and(IconPicker::make('icon')->uploadable()->icons([new DiskIconSet('icons', 'icons', 'uploads')])->isUploadable())->toBeTrue()
        ->and(IconPicker::make('icon')->icons([new DiskIconSet('icons', 'icons', 'uploads')])->isUploadable())->toBeFalse()
        ->and(IconPicker::make('icon')->uploadable(fn (): bool => false)->icons([new DiskIconSet('icons', 'icons', 'uploads')])->isUploadable())->toBeFalse()
        ->and(IconPicker::make('icon')->uploadable(set: 'nope')->icons([new DiskIconSet('icons', 'icons', 'uploads')])->isUploadable())->toBeFalse()
        ->and(IconPicker::make('icon')->uploadable(set: 'uploads')->icons([new DiskIconSet('icons', 'icons', 'uploads')])->getUploadIconSet()?->getKey())->toBe('uploads');
});

it('never uploads into a folder set unless it is named explicitly', function (): void {
    $folder = IconPicker::make('icon')->uploadable()->customIconsPath(TestCase::fixturePath('icons'), 'folder');
    $mixed = IconPicker::make('icon')->uploadable()->customIconsPath(TestCase::fixturePath('icons'), 'folder')->icons([new DiskIconSet('icons', 'icons', 'uploads')]);
    $named = IconPicker::make('icon')->uploadable(set: 'folder')->customIconsPath(TestCase::fixturePath('icons'), 'folder');

    expect($folder->isUploadable())->toBeFalse()
        ->and($mixed->getUploadIconSet()?->getKey())->toBe('uploads')
        ->and($named->getUploadIconSet()?->getKey())->toBe('folder');
});

it('renders the upload button only when uploadable', function (): void {
    Livewire::test(uploadForm(), ['uploadable' => true])->assertSee('Upload icon');
    Livewire::test(uploadForm())->assertDontSee('Upload icon');
});

it('uploads a sanitised svg into the set and selects it', function (): void {
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" onload="alert(1)"><script>alert(2)</script><path d="M12 2l3 6"/></svg>';

    Livewire::test(uploadForm(), ['uploadable' => true])
        ->callFormComponentAction('icon', 'uploadIcon', data: [
            'file' => UploadedFile::fake()->createWithContent('My Star.svg', $svg),
            'name' => null,
        ])
        ->assertHasNoFormComponentActionErrors()
        ->assertSet('data.icon', 'uploads:my-star')
        ->call('save')
        ->assertHasNoErrors();

    Storage::disk('icons')->assertExists('icons/my-star.svg');

    expect(Storage::disk('icons')->get('icons/my-star.svg'))->toContain('<path d="M12 2l3 6"')->not->toContain('script', 'onload');
});

it('keeps the previous value when the upload is not an svg', function (): void {
    Livewire::test(uploadForm(), ['uploadable' => true, 'initialIcon' => 'icons:star'])
        ->callFormComponentAction('icon', 'uploadIcon', data: [
            'file' => UploadedFile::fake()->createWithContent('notes.svg', 'definitely not svg'),
        ])
        ->assertSet('data.icon', 'icons:star');

    expect(Storage::disk('icons')->allFiles())->toBe([]);
});
