<?php

declare(strict_types=1);

namespace AbdulrahmanDev22\FilamentIconPicker\Tests;

use AbdulrahmanDev22\FilamentIconPicker\FilamentIconPickerServiceProvider;
use BladeUI\Heroicons\BladeHeroiconsServiceProvider;
use BladeUI\Icons\BladeIconsServiceProvider;
use Filament\Actions\ActionsServiceProvider;
use Filament\Forms\FormsServiceProvider;
use Filament\Infolists\InfolistsServiceProvider;
use Filament\Notifications\NotificationsServiceProvider;
use Filament\Schemas\Schema;
use Filament\Schemas\SchemasServiceProvider;
use Filament\Support\SupportServiceProvider;
use Livewire\LivewireServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    /**
     * Providers are listed explicitly (guarded by class_exists) so the same
     * test suite boots against Filament 3, 4 and 5.
     */
    protected function getPackageProviders($app): array
    {
        return array_values(array_filter([
            LivewireServiceProvider::class,
            BladeIconsServiceProvider::class,
            BladeHeroiconsServiceProvider::class,
            SupportServiceProvider::class,
            SchemasServiceProvider::class,
            InfolistsServiceProvider::class,
            NotificationsServiceProvider::class,
            ActionsServiceProvider::class,
            FormsServiceProvider::class,
            FilamentIconPickerServiceProvider::class,
        ], 'class_exists'));
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('cache.default', 'array');
        $app['config']->set('filament-icon-picker.cache.enabled', false);
    }

    public static function fixturePath(string $path = ''): string
    {
        return rtrim(__DIR__.'/Fixtures/'.ltrim($path, '/'), '/');
    }

    public static function isFilamentV3(): bool
    {
        return ! class_exists(Schema::class);
    }
}
