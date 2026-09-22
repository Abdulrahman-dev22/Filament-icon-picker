<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Bundled Heroicons
    |--------------------------------------------------------------------------
    |
    | Heroicons ship with Filament itself (via blade-ui-kit/blade-heroicons),
    | so the picker can offer them with zero configuration. Set
    | `register_heroicons` to false to remove the tab globally, or call
    | `->withoutIconSet('heroicons')` on an individual field.
    |
    | `styles` controls which Heroicon variants are listed. Each style is a
    | separate icon, so listing all four quadruples the size of the grid.
    | Supported: 'o' (outline), 's' (solid), 'm' (mini), 'c' (micro).
    |
    */

    'register_heroicons' => true,

    'heroicons' => [
        'label' => 'Heroicons',
        'styles' => ['o'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom icon sets loaded from a directory
    |--------------------------------------------------------------------------
    |
    | Every `.svg` file in the directory becomes one icon. The file name
    | (without extension) is the icon name; nested folders are joined with a
    | dot ("brand/logo.svg" => "brand.logo"). Each entry becomes a tab, and
    | the array key becomes the set key stored in the field state.
    |
    | 'brand' => [
    |     'path' => resource_path('svg/brand'),
    |     'label' => 'Brand Icons',
    | ],
    |
    */

    'custom_icon_sets' => [
        //
    ],

    /*
    |--------------------------------------------------------------------------
    | Icon sets stored on a filesystem disk
    |--------------------------------------------------------------------------
    |
    | Like custom_icon_sets, but read from a Laravel disk (public, s3, gcs…)
    | so icons uploaded at runtime survive deployments and are shared between
    | servers. Use these as the target of `IconPicker::uploadable()`.
    |
    | 'uploads' => [
    |     'disk' => 's3',               // null = uploads.disk below
    |     'directory' => 'icon-picker',
    |     'label' => 'Uploaded Icons',
    | ],
    |
    */

    'disk_icon_sets' => [
        //
    ],

    /*
    |--------------------------------------------------------------------------
    | Uploads
    |--------------------------------------------------------------------------
    |
    | Defaults for the field's "Upload icon" action (`->uploadable()`).
    | `set` is the key of the set new icons are written to (null = the first
    | disk-backed set; folder sets must be named explicitly). `disk` is the
    | default disk of DiskIconSet.
    | `max_size` is in kilobytes. Every upload is sanitised: scripts, event
    | handlers, embedded documents and external references are stripped.
    |
    */

    'uploads' => [
        'set' => null,
        'disk' => env('FILAMENT_ICON_PICKER_DISK'),
        'max_size' => 256,
    ],

    /*
    |--------------------------------------------------------------------------
    | Additional icon set classes
    |--------------------------------------------------------------------------
    |
    | Register any class implementing
    | \AbdulrahmanDev22\FilamentIconPicker\IconSets\IconSet here. Classes are resolved
    | through the container, so constructor dependencies are injected.
    |
    | \App\Support\Icons\ProjectIconSet::class,
    |
    */

    'icon_sets' => [
        //
    ],

    /*
    |--------------------------------------------------------------------------
    | Storage format
    |--------------------------------------------------------------------------
    |
    | How IconPicker fields write the selected icon to the model unless a
    | field overrides it with ->storeAs*(). The picker converts back on load,
    | so switching format later is safe.
    |
    | 'reference'  => "brand:star"                  (default, string column)
    | 'blade_icon' => "brand-star" / "heroicon-o-x" (string column, works in ->icon())
    | 'array'      => [set, name, label, blade_icon, svg, url] (json column + array cast)
    | 'json'       => same as array, JSON-encoded  (text column)
    | 'svg'        => raw <svg> markup             (text column)
    |
    */

    'storage_format' => 'reference',

    /*
    |--------------------------------------------------------------------------
    | Icon URL route
    |--------------------------------------------------------------------------
    |
    | When enabled, every globally registered icon is served at
    | GET {prefix}/{set}/{name}.svg, and `url` is filled in array/json
    | storage and in FilamentIconPicker::toArray(). Handy for mobile / SPA
    | clients that want to load icons by URL and cache them.
    |
    */

    'route' => [
        'enabled' => env('FILAMENT_ICON_PICKER_ROUTE', false),
        'prefix' => 'filament-icon-picker',
        'middleware' => [],
        'max_age' => 31536000,
    ],

    /*
    |--------------------------------------------------------------------------
    | Caching
    |--------------------------------------------------------------------------
    |
    | Scanning icon directories on every request is wasteful in production.
    | When enabled, the resolved icon list of each set is stored in the
    | given cache store (null = default store) for `ttl` seconds (null =
    | forever). Set `enabled` to null to cache in production only.
    |
    | Clear it with `php artisan filament-icon-picker:clear` or `cache:clear`.
    |
    */

    'cache' => [
        'enabled' => env('FILAMENT_ICON_PICKER_CACHE'),
        'store' => env('FILAMENT_ICON_PICKER_CACHE_STORE'),
        'ttl' => null,
        'prefix' => 'filament-icon-picker',
    ],

];
