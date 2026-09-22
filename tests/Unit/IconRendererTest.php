<?php

declare(strict_types=1);

use AbdulrahmanDev22\FilamentIconPicker\Support\IconRenderer;
use Illuminate\Support\HtmlString;

it('renders blade icon names', function (): void {
    expect(IconRenderer::render('heroicon-o-academic-cap', ['class' => 'x', 'aria-hidden' => 'true'])->toHtml())
        ->toContain('<svg', 'class="x"', 'aria-hidden="true"');
});

it('returns null for unknown blade icons and blank input', function (): void {
    expect(IconRenderer::render('heroicon-o-nope'))->toBeNull()
        ->and(IconRenderer::render(null))->toBeNull()
        ->and(IconRenderer::render(''))->toBeNull()
        ->and(IconRenderer::render(new HtmlString('<p>not svg</p>')))->toBeNull();
});

it('injects attributes into raw svg markup ahead of existing ones', function (): void {
    $svg = new HtmlString('<?xml version="1.0"?><svg class="old" viewBox="0 0 1 1"></svg>');

    expect(IconRenderer::render($svg, ['class' => 'new', 'width' => 16, 'hidden' => false, 'title' => null])->toHtml())
        ->toBe('<?xml version="1.0"?><svg class="new" width="16" class="old" viewBox="0 0 1 1"></svg>');
});

it('leaves raw svg untouched without attributes', function (): void {
    expect(IconRenderer::render(new HtmlString('<svg></svg>'))->toHtml())->toBe('<svg></svg>');
});
