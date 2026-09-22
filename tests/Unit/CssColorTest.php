<?php

declare(strict_types=1);

use AbdulrahmanDev22\FilamentIconPicker\Support\CssColor;

it('wraps rgb channel triplets and passes colour functions through', function (): void {
    expect(CssColor::fromFilament('255, 251, 235'))->toBe('rgb(255, 251, 235)')
        ->and(CssColor::fromFilament('255 251 235'))->toBe('rgb(255 251 235)')
        ->and(CssColor::fromFilament('oklch(0.666 0.179 58.318)'))->toBe('oklch(0.666 0.179 58.318)')
        ->and(CssColor::fromFilament('#ff0000'))->toBe('#ff0000')
        ->and(CssColor::fromFilament(''))->toBeNull()
        ->and(CssColor::fromFilament(null))->toBeNull();
});

it('resolves a shade of the registered primary colour', function (): void {
    expect(CssColor::shade('primary', 600))->toBeString()->not->toBeEmpty()
        ->and(CssColor::shade('primary', 600))->toMatch('/^(rgb\(|oklch\(|#)/')
        ->and(CssColor::shade('nope', 600))->toBeNull();
});
