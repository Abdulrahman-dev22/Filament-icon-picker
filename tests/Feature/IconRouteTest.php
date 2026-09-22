<?php

declare(strict_types=1);

namespace AbdulrahmanDev22\FilamentIconPicker\Tests\Feature;

use AbdulrahmanDev22\FilamentIconPicker\Facades\FilamentIconPicker;
use AbdulrahmanDev22\FilamentIconPicker\Tests\TestCase;

class IconRouteTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('filament-icon-picker.route.enabled', true);
        $app['config']->set('filament-icon-picker.route.prefix', 'icons');
        $app['config']->set('filament-icon-picker.custom_icon_sets', [
            'brand' => ['path' => self::fixturePath('icons')],
        ]);
    }

    public function test_icons_are_served_as_svg_by_url(): void
    {
        $url = FilamentIconPicker::url('brand:brand.logo');

        $this->assertSame(url('/icons/brand/brand.logo.svg'), $url);

        $response = $this->get($url)
            ->assertOk()
            ->assertHeader('Content-Type', 'image/svg+xml; charset=utf-8')
            ->assertSee('<svg', escape: false);

        $this->assertStringContainsString('max-age=31536000', (string) $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('public', (string) $response->headers->get('Cache-Control'));

        $this->get('/icons/heroicons/o-academic-cap.svg')->assertOk();
        $this->get('/icons/brand/missing.svg')->assertNotFound();
        $this->get('/icons/nope/star.svg')->assertNotFound();

        $this->assertSame($url, FilamentIconPicker::toArray('brand:brand.logo')['url']);
    }
}
