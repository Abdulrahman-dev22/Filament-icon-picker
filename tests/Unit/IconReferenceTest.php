<?php

declare(strict_types=1);

use AbdulrahmanDev22\FilamentIconPicker\Support\IconReference;

it('parses a stored value into set and name', function (): void {
    $reference = IconReference::from('heroicons:o-academic-cap');

    expect($reference->set)->toBe('heroicons')
        ->and($reference->name)->toBe('o-academic-cap')
        ->and($reference->toString())->toBe('heroicons:o-academic-cap')
        ->and((string) $reference)->toBe('heroicons:o-academic-cap');
});

it('keeps everything after the first separator as the icon name', function (): void {
    expect(IconReference::from('custom:weird:name')->name)->toBe('weird:name');
});

it('returns null for blank or malformed values', function (mixed $value): void {
    expect(IconReference::tryFrom($value))->toBeNull();
})->with([
    'null' => [null],
    'empty string' => [''],
    'no separator' => ['academic-cap'],
    'leading separator' => [':academic-cap'],
    'trailing separator' => ['heroicons:'],
    'array' => [['heroicons', 'x']],
    'integer' => [12],
]);

it('throws for malformed values when parsing strictly', function (): void {
    IconReference::from('nope');
})->throws(InvalidArgumentException::class);

it('passes through existing references', function (): void {
    $reference = IconReference::make('a', 'b');

    expect(IconReference::tryFrom($reference))->toBe($reference);
});
