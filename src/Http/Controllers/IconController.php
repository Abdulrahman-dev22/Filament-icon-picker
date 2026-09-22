<?php

declare(strict_types=1);

namespace AbdulrahmanDev22\FilamentIconPicker\Http\Controllers;

use AbdulrahmanDev22\FilamentIconPicker\IconSets\IconSetRegistry;
use AbdulrahmanDev22\FilamentIconPicker\Support\IconReference;
use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Serves a globally registered icon as an SVG file, so API clients can load
 * icons by URL (see `filament-icon-picker.route`).
 */
class IconController
{
    public function __invoke(IconSetRegistry $registry, string $set, string $name): Response
    {
        $svg = $registry->render(IconReference::make($set, $name));

        if ($svg === null) {
            throw new NotFoundHttpException("Icon [{$set}:{$name}] not found.");
        }

        $maxAge = (int) config('filament-icon-picker.route.max_age', 31536000);

        return new Response($svg->toHtml(), 200, [
            'Content-Type' => 'image/svg+xml; charset=utf-8',
            'Cache-Control' => $maxAge > 0 ? "public, max-age={$maxAge}, immutable" : 'no-cache',
        ]);
    }
}
