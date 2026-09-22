<?php

declare(strict_types=1);

use AbdulrahmanDev22\FilamentIconPicker\Exceptions\InvalidSvgException;
use AbdulrahmanDev22\FilamentIconPicker\IconSets\CustomIconSet;
use AbdulrahmanDev22\FilamentIconPicker\IconSets\DiskIconSet;
use AbdulrahmanDev22\FilamentIconPicker\Support\IconUploader;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

beforeEach(fn () => Storage::fake('icons'));

it('sanitises, names and stores an uploaded svg', function (): void {
    $set = new DiskIconSet(disk: 'icons', directory: 'icons', key: 'uploads');

    $reference = app(IconUploader::class)->upload($set, '<svg xmlns="http://www.w3.org/2000/svg"><script>x</script><path d="M0 0"/></svg>', name: 'My Star!', originalFilename: 'whatever.svg');

    expect($reference->toString())->toBe('uploads:my-star')
        ->and(Storage::disk('icons')->get('icons/my-star.svg'))->toContain('<path')->not->toContain('<script')
        ->and($set->getIcons())->toHaveKey('my-star');
});

it('derives the name from the file name and keeps names unique', function (): void {
    $set = new DiskIconSet(disk: 'icons', directory: 'icons', key: 'uploads');
    $uploader = app(IconUploader::class);

    expect($uploader->upload($set, '<svg/>', originalFilename: 'Shield Tick.svg')->name)->toBe('shield-tick')
        ->and($uploader->upload($set, '<svg/>', originalFilename: 'shield-tick.svg')->name)->toBe('shield-tick-2')
        ->and($uploader->upload($set, '<svg/>', originalFilename: 'shield-tick.svg')->name)->toBe('shield-tick-3')
        ->and($uploader->upload($set, '<svg/>', name: 'shield-tick', overwrite: true)->name)->toBe('shield-tick')
        ->and($uploader->upload($set, '<svg/>')->name)->toBe('icon');
});

it('rejects invalid or oversized files', function (): void {
    $set = new DiskIconSet(disk: 'icons', directory: 'icons', key: 'uploads');
    $uploader = app(IconUploader::class);

    expect(fn () => $uploader->upload($set, 'not svg'))->toThrow(InvalidSvgException::class)
        ->and(fn () => $uploader->upload($set, '<svg>'.str_repeat('<path d="M0 0"/>', 200).'</svg>', maxBytes: 100))->toThrow(InvalidSvgException::class)
        ->and(Storage::disk('icons')->allFiles())->toBe([]);
});

it('can upload into a folder based set as well', function (): void {
    $directory = sys_get_temp_dir().'/filament-icon-picker-'.uniqid();
    File::ensureDirectoryExists($directory);

    try {
        $set = new CustomIconSet($directory, 'folder');

        $reference = app(IconUploader::class)->upload($set, '<svg><path d="M0 0"/></svg>', name: 'bolt');

        expect($reference->toString())->toBe('folder:bolt')
            ->and(is_file("{$directory}/bolt.svg"))->toBeTrue()
            ->and($set->getIcons())->toHaveKey('bolt');

        $set->deleteIcon('bolt');

        expect(is_file("{$directory}/bolt.svg"))->toBeFalse();
    } finally {
        File::deleteDirectory($directory);
    }
});
