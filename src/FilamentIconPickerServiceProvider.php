<?php

declare(strict_types=1);

namespace AbdulrahmanDev22\FilamentIconPicker;

use AbdulrahmanDev22\FilamentIconPicker\Commands\ClearIconCacheCommand;
use AbdulrahmanDev22\FilamentIconPicker\Http\Controllers\IconController;
use AbdulrahmanDev22\FilamentIconPicker\IconSets\CustomIconSet;
use AbdulrahmanDev22\FilamentIconPicker\IconSets\DiskIconSet;
use AbdulrahmanDev22\FilamentIconPicker\IconSets\IconSetRegistry;
use AbdulrahmanDev22\FilamentIconPicker\Support\IconCache;
use BladeUI\Icons\Factory as BladeIconsFactory;
use Filament\Support\Assets\Css;
use Filament\Support\Facades\FilamentAsset;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class FilamentIconPickerServiceProvider extends PackageServiceProvider
{
    public static string $name = 'filament-icon-picker';

    public static string $composerPackage = 'abdulrahman-dev22/filament-icon-picker';

    public function configurePackage(Package $package): void
    {
        $package
            ->name(static::$name)
            ->hasConfigFile()
            ->hasViews()
            ->hasTranslations()
            ->hasCommand(ClearIconCacheCommand::class);
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(IconCache::class);

        $this->app->singleton(
            IconSetRegistry::class,
            fn (Application $app): IconSetRegistry => IconSetRegistry::fromConfig(
                $app,
                (array) $app['config']->get(static::$name, []),
            ),
        );
    }

    public function packageBooted(): void
    {
        FilamentAsset::register([
            Css::make(static::$name.'-styles', __DIR__.'/../resources/css/filament-icon-picker.css'),
        ], package: static::$composerPackage);

        $this->registerIconRoute();

        // Expose globally configured folders to Blade Icons as soon as it is
        // used, so "brand:logo" also works as the plain icon name "brand-logo"
        // on pages where no picker is rendered (tables, navigation, emails…).
        $this->callAfterResolving(BladeIconsFactory::class, function (BladeIconsFactory $factory): void {
            foreach ($this->app->make(IconSetRegistry::class)->all() as $set) {
                if ($set instanceof CustomIconSet || $set instanceof DiskIconSet) {
                    $set->registerWithBladeIcons($factory);
                }
            }
        });
    }

    /**
     * Optional `GET {prefix}/{set}/{name}.svg` endpoint so API clients can
     * load icons by URL.
     */
    protected function registerIconRoute(): void
    {
        $config = (array) $this->app['config']->get(static::$name.'.route', []);

        if (! ($config['enabled'] ?? false)) {
            return;
        }

        Route::middleware($config['middleware'] ?? [])
            ->prefix($config['prefix'] ?? 'filament-icon-picker')
            ->group(function (): void {
                Route::get('{set}/{name}.svg', IconController::class)
                    ->where(['set' => '[A-Za-z0-9_-]+', 'name' => '[A-Za-z0-9._-]+'])
                    ->name('filament-icon-picker.icon');
            });
    }
}
