<?php

declare(strict_types=1);

namespace AbdulrahmanDev22\FilamentIconPicker\Tests\Feature;

use AbdulrahmanDev22\FilamentIconPicker\Tests\TestCase;
use BladeUI\Icons\Factory;

/**
 * Written as a class so the config can be defined before the application boots.
 */
class GlobalCustomSetBladeIconsTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('filament-icon-picker.custom_icon_sets', [
            'brandtest' => ['path' => self::fixturePath('icons'), 'label' => 'Brand'],
        ]);
    }

    public function test_globally_configured_folders_are_usable_as_plain_blade_icon_names(): void
    {
        $this->assertArrayHasKey('brandtest', $this->app->make(Factory::class)->all());
        $this->assertStringContainsString('<svg', svg('brandtest-star')->toHtml());
        $this->assertStringContainsString('<svg', svg('brandtest-brand.logo')->toHtml());
    }
}
