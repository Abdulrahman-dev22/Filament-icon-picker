<?php

declare(strict_types=1);

use AbdulrahmanDev22\FilamentIconPicker\Exceptions\InvalidSvgException;
use AbdulrahmanDev22\FilamentIconPicker\Support\SvgSanitizer;

afterEach(fn () => SvgSanitizer::using(null));

it('strips scripts, event handlers and dangerous references but keeps the drawing', function (): void {
    $dirty = <<<'SVG'
    <?xml version="1.0"?>
    <!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.1//EN" "http://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd">
    <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 24 24" onload="alert(1)">
        <style>.a{fill:red} .b{background:url(http://evil/x)}</style>
        <script>alert('x')</script>
        <foreignObject><body xmlns="http://www.w3.org/1999/xhtml">hi</body></foreignObject>
        <defs><path id="p" d="M0 0h24v24H0z"/></defs>
        <use xlink:href="#p" onclick="alert(2)"/>
        <use xlink:href="http://evil/remote.svg#x"/>
        <a href="javascript:alert(3)"><circle cx="12" cy="12" r="4" class="a" style="fill:url(#g);stroke:red"/></a>
        <image href="data:image/png;base64,AAAA" width="1" height="1"/>
        <rect width="1" height="1" style="fill:blue"/>
    </svg>
    SVG;

    $clean = (new SvgSanitizer)->sanitize($dirty);

    expect($clean)->toStartWith('<svg')
        ->and($clean)->not->toContain('<?xml', 'DOCTYPE', '<script', 'alert', 'onload', 'onclick', '<foreignObject', 'http://evil', '<style')
        ->and($clean)->toContain('<path id="p"', '<use xlink:href="#p"', '<circle', '<a>', 'data:image/png;base64,AAAA', 'style="fill:blue"')
        ->and($clean)->not->toContain('style="fill:url');
});

it('rejects content that is not an svg document', function (string $input): void {
    (new SvgSanitizer)->sanitize($input);
})->with([
    'empty' => [''],
    'html' => ['<div><svg></svg></div>'],
    'broken xml' => ['<svg><path d="M0 0"></svg>'],
    'plain text' => ['hello'],
])->throws(InvalidSvgException::class);

it('can be replaced with a custom sanitizer', function (): void {
    SvgSanitizer::using(fn (string $svg): string => '<svg data-custom="1"/>');

    expect((new SvgSanitizer)->sanitize('<svg><script/></svg>'))->toBe('<svg data-custom="1"/>');
});
